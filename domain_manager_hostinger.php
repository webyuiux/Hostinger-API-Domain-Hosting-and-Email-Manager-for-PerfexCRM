<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Hostinger Manager
Description: Manage domains with expiry tracking and client/project linking.
Version: 1.0.1
Requires at least: 3.0.0
Author: Virrat global
Author URI: https://virratglobal.com/
*/

define('DOMAIN_MANAGER_MODULE_NAME', 'domain_manager_hostinger');
define('VERSION_DOMAIN_MANAGER', 100);
define('DOMAIN_MANAGER_MODULE_NAME_ITEM_ID', 'Domain Manager');

// Hooks
hooks()->add_action('admin_init', 'domain_manager_init_menu_items');
hooks()->add_action('admin_init', 'domain_manager_define_permissions');
hooks()->add_filter('module_domain_manager_hostinger_action_links', 'domain_manager_add_action_links');
hooks()->add_action('app_admin_head', 'domain_manager_add_head_components');

// Two independent cron hooks — sync and notifications run on separate 24h timers
hooks()->add_action('after_cron_run', 'domain_manager_automated_sync');
hooks()->add_action('after_cron_run', 'domain_manager_automated_notifications');

/**
 * Automated API sync via Perfex Cron.
 * Runs once every 24 hours to pull domain/website data from Hostinger.
 * Completely independent of notifications — they have their own timer.
 */
function domain_manager_automated_sync() {
    $last_run = get_option('domain_manager_last_cron_sync');
    if (empty($last_run) || (time() - $last_run) > 86400) {
        $CI = &get_instance();
        $CI->load->model(DOMAIN_MANAGER_MODULE_NAME . '/hostinger_api_model');
        $CI->hostinger_api_model->sync_domains();
        $CI->hostinger_api_model->sync_websites();
        update_option('domain_manager_last_cron_sync', time());
    }
}

/**
 * Automated expiry notification emails via Perfex Cron.
 *
 * Runs once per day INDEPENDENTLY of the API sync.
 * This means emails fire even if:
 *   - Hostinger API token is not configured
 *   - Sync was already run manually today
 *   - Domains were entered manually (not synced from Hostinger)
 *
 * Uses its own timestamp: domain_manager_last_notification_run
 */
function domain_manager_automated_notifications() {
    $last_run = get_option('domain_manager_last_notification_run');
    // Run once every 24 hours, independent of sync
    if (!empty($last_run) && (time() - $last_run) < 86400) {
        return;
    }

    $CI = &get_instance();

    $notify_recipients = get_option('domain_manager_notify_recipients');
    if (empty($notify_recipients)) {
        $notify_recipients = "Customer's Contact Email + Staff Assigned to Customer";
    }

    $specific_staff_ids_raw = get_option('domain_manager_notify_specific_staff');
    $specific_staff_ids = !empty($specific_staff_ids_raw) ? array_filter(array_map('intval', explode(',', $specific_staff_ids_raw))) : [];

    $additional_emails_raw = get_option('domain_manager_notification_emails');
    $additional_emails = !empty($additional_emails_raw) ? array_filter(array_map('trim', explode(',', $additional_emails_raw))) : [];

    $notify_days_raw = get_option('domain_manager_notify_days');
    if (empty($notify_days_raw)) $notify_days_raw = '30,15,7,3,1,0';
    $notify_days = array_map('intval', explode(',', $notify_days_raw));

    // Allow ±1 day tolerance to handle cron timing drift on shared hosting.
    // E.g. if cron runs slightly late (e.g. 36h gap), the notification day
    // should still match rather than being silently skipped forever.
    $notify_days_with_tolerance = [];
    foreach ($notify_days as $nd) {
        $notify_days_with_tolerance[] = $nd;
        if ($nd > 0) $notify_days_with_tolerance[] = $nd + 1;
        if ($nd > 1) $notify_days_with_tolerance[] = $nd - 1;
    }
    $notify_days_with_tolerance = array_unique($notify_days_with_tolerance);

    $CI->load->model(DOMAIN_MANAGER_MODULE_NAME . '/domain_manager_model');
    $CI->load->model(DOMAIN_MANAGER_MODULE_NAME . '/hosting_details_model');

    $domains  = $CI->domain_manager_model->get();
    $websites = $CI->hosting_details_model->get();

    $expiring_assets = [];
    $today = new DateTime('midnight');

    foreach ($domains as $d) {
        if (!empty($d['expiry_date']) && $d['expiry_date'] !== '0000-00-00') {
            $exp_date = new DateTime($d['expiry_date']);
            $diff = $today->diff($exp_date);
            if ($diff->invert === 0 && in_array($diff->days, $notify_days_with_tolerance)) {
                $expiring_assets[] = [
                    'id'               => $d['id'],
                    'type'             => 'Domain',
                    'name'             => $d['domain_name'],
                    'expiry'           => $d['expiry_date'],
                    'days'             => $diff->days,
                    'client_id'        => !empty($d['client_id']) ? (int)$d['client_id'] : 0,
                    'assigned_staff_id'=> !empty($d['assigned_staff_id']) ? (int)$d['assigned_staff_id'] : 0,
                ];
            }
        }
    }

    foreach ($websites as $w) {
        if (!empty($w['expiration_date']) && $w['expiration_date'] !== '0000-00-00') {
            $exp_date = new DateTime($w['expiration_date']);
            $diff = $today->diff($exp_date);
            if ($diff->invert === 0 && in_array($diff->days, $notify_days_with_tolerance)) {
                $assigned_staff_id = !empty($w['assigned_staff_id']) ? (int)$w['assigned_staff_id'] : 0;
                if ($assigned_staff_id === 0 && !empty($w['domain_id'])) {
                    $dom = $CI->db->select('assigned_staff_id')->where('id', $w['domain_id'])->get(db_prefix() . 'domain_manager')->row();
                    if ($dom) {
                        $assigned_staff_id = !empty($dom->assigned_staff_id) ? (int)$dom->assigned_staff_id : 0;
                    }
                }
                $expiring_assets[] = [
                    'id'               => $w['id'],
                    'type'             => 'Website',
                    'name'             => $w['website_name'],
                    'expiry'           => $w['expiration_date'],
                    'days'             => $diff->days,
                    'client_id'        => !empty($w['client_id']) ? (int)$w['client_id'] : 0,
                    'assigned_staff_id'=> $assigned_staff_id,
                ];
            }
        }
    }

    // Mark notification run timestamp NOW (before sending) so a PHP crash
    // on one asset doesn't re-send everything on next cron run.
    update_option('domain_manager_last_notification_run', time());

    if (empty($expiring_assets)) {
        return;
    }

    $CI->load->config('email');
    $CI->load->library('email');

    foreach ($expiring_assets as $asset) {
        $client_id         = $asset['client_id'];
        $domain_id         = $asset['id'];
        $domain_name       = $asset['name'];
        $expiry_date       = $asset['expiry'];
        $days_left         = $asset['days'];
        $assigned_staff_id = isset($asset['assigned_staff_id']) ? $asset['assigned_staff_id'] : 0;

        // Resolve customer name; if inactive, zero out client_id so only
        // specific-staff / additional-email channels still fire.
        $customer_name = 'Valued Customer';
        $client_valid  = false;
        if ($client_id > 0) {
            $client = $CI->db->select('company, active')
                ->from(db_prefix() . 'clients')
                ->where('userid', $client_id)
                ->get()->row();
            if (!$client || (int)$client->active !== 1) {
                $client_id    = 0;
                $client_valid = false;
            } else {
                $customer_name = $client->company;
                $client_valid  = true;
            }
        }

        $send_to_customer  = false;
        $send_to_staff     = false;
        // NOTE: additional_emails always fire regardless of routing rule.
        // The routing rule only controls customer + assigned-staff channels.

        if ($notify_recipients === 'Customer Only') {
            $send_to_customer = true;
        } elseif ($notify_recipients === 'Staff Only') {
            $send_to_staff = true;
        } elseif ($notify_recipients === "Customer's Contact Email + Staff Assigned to Customer") {
            $send_to_customer = true;
            $send_to_staff    = true;
        } elseif ($notify_recipients === 'Customer + Assigned Staff + Additional Emails') {
            $send_to_customer = true;
            $send_to_staff    = true;
        }

        // 1. Customer primary contacts
        if ($send_to_customer && $client_id > 0) {
            $primary_contacts = $CI->db->select('email, firstname, lastname')
                ->from(db_prefix() . 'contacts')
                ->where('userid', $client_id)
                ->where('is_primary', 1)
                ->where('active', 1)
                ->get()->result_array();

            foreach ($primary_contacts as $contact) {
                $email = $contact['email'];
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

                $already_sent = $CI->db->where('domain_id', $domain_id)
                    ->where('asset_type', strtolower($asset['type']))
                    ->where('email_sent_to', $email)
                    ->where('days_before_expiry', $days_left)
                    ->where('DATE(sent_at)', date('Y-m-d'))
                    ->get(db_prefix() . 'expiry_notification_logs')->row();
                if ($already_sent) continue;

                $contact_name = $contact['firstname'] . ' ' . $contact['lastname'];
                if (strtolower($asset['type']) === 'website') {
                    $subj = "Your Website is Expiring Soon";
                    $body = "Hello " . $contact_name . ",\n\nThis is a reminder that your website:\n\n" . $domain_name . "\n\nwill expire on:\n\n" . $expiry_date . "\n\nDays Remaining: " . $days_left . "\n\nPlease renew your website before the expiry date to avoid service interruption.\n\nThank you.";
                } else {
                    $subj = "Your Domain is Expiring Soon";
                    $body = "Hello " . $contact_name . ",\n\nThis is a reminder that your domain:\n\n" . $domain_name . "\n\nwill expire on:\n\n" . $expiry_date . "\n\nDays Remaining: " . $days_left . "\n\nPlease renew your domain before the expiry date to avoid service interruption.\n\nThank you.";
                }

                $CI->email->clear();
                $CI->email->from(get_option('smtp_email'), get_option('companyname'));
                $CI->email->to($email);
                $CI->email->subject($subj);
                $CI->email->message($body);
                $sent = $CI->email->send();

                $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                    'domain_id'         => $domain_id,
                    'asset_type'        => strtolower($asset['type']),
                    'customer_id'       => $client_id,
                    'staff_id'          => null,
                    'email_sent_to'     => $email,
                    'days_before_expiry'=> $days_left,
                    'sent_at'           => date('Y-m-d H:i:s'),
                    'status'            => $sent ? 'success' : 'failed',
                ]);
            }
        }

        // 2. Assigned staff: customer admins + asset's assigned staff member
        if ($send_to_staff) {
            $staff_to_email = [];

            if ($client_id > 0) {
                $customer_admins = $CI->db->select('staffid, email, firstname, lastname')
                    ->from(db_prefix() . 'customer_admins')
                    ->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'customer_admins.staff_id')
                    ->where('customer_id', $client_id)
                    ->where(db_prefix() . 'staff.active', 1)
                    ->get()->result_array();
                foreach ($customer_admins as $sa) {
                    $staff_to_email[$sa['staffid']] = $sa;
                }
            }

            if ($assigned_staff_id > 0) {
                $asset_staff = $CI->db->select('staffid, email, firstname, lastname')
                    ->from(db_prefix() . 'staff')
                    ->where('staffid', $assigned_staff_id)
                    ->where('active', 1)
                    ->get()->row_array();
                if ($asset_staff) {
                    $staff_to_email[$asset_staff['staffid']] = $asset_staff;
                }
            }

            foreach ($staff_to_email as $staff) {
                $email    = $staff['email'];
                $staff_id = $staff['staffid'];
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

                $already_sent = $CI->db->where('domain_id', $domain_id)
                    ->where('asset_type', strtolower($asset['type']))
                    ->where('email_sent_to', $email)
                    ->where('days_before_expiry', $days_left)
                    ->where('DATE(sent_at)', date('Y-m-d'))
                    ->get(db_prefix() . 'expiry_notification_logs')->row();
                if ($already_sent) continue;

                if (strtolower($asset['type']) === 'website') {
                    $subj = "Customer Website Expiry Alert";
                    $body = "Customer: " . $customer_name . "\nWebsite: " . $domain_name . "\nExpiry Date: " . $expiry_date . "\nDays Remaining: " . $days_left . "\n\nPlease follow up with the customer regarding renewal.";
                } else {
                    $subj = "Customer Domain Expiry Alert";
                    $body = "Customer: " . $customer_name . "\nDomain: " . $domain_name . "\nExpiry Date: " . $expiry_date . "\nDays Remaining: " . $days_left . "\n\nPlease follow up with the customer regarding renewal.";
                }

                $CI->email->clear();
                $CI->email->from(get_option('smtp_email'), get_option('companyname'));
                $CI->email->to($email);
                $CI->email->subject($subj);
                $CI->email->message($body);
                $sent = $CI->email->send();

                $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                    'domain_id'         => $domain_id,
                    'asset_type'        => strtolower($asset['type']),
                    'customer_id'       => $client_id,
                    'staff_id'          => $staff_id,
                    'email_sent_to'     => $email,
                    'days_before_expiry'=> $days_left,
                    'sent_at'           => date('Y-m-d H:i:s'),
                    'status'            => $sent ? 'success' : 'failed',
                ]);
            }
        }

        // 3. Specific configured staff members (always fires regardless of client status)
        if (!empty($specific_staff_ids)) {
            $staff_records = $CI->db->select('staffid, email, firstname, lastname')
                ->from(db_prefix() . 'staff')
                ->where_in('staffid', $specific_staff_ids)
                ->where('active', 1)
                ->get()->result_array();

            foreach ($staff_records as $staff) {
                $email    = $staff['email'];
                $staff_id = $staff['staffid'];
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

                $already_sent = $CI->db->where('domain_id', $domain_id)
                    ->where('asset_type', strtolower($asset['type']))
                    ->where('email_sent_to', $email)
                    ->where('days_before_expiry', $days_left)
                    ->where('DATE(sent_at)', date('Y-m-d'))
                    ->get(db_prefix() . 'expiry_notification_logs')->row();
                if ($already_sent) continue;

                if (strtolower($asset['type']) === 'website') {
                    $subj = "Customer Website Expiry Alert";
                    $body = "Customer: " . $customer_name . "\nWebsite: " . $domain_name . "\nExpiry Date: " . $expiry_date . "\nDays Remaining: " . $days_left . "\n\nPlease follow up with the customer regarding renewal.";
                } else {
                    $subj = "Customer Domain Expiry Alert";
                    $body = "Customer: " . $customer_name . "\nDomain: " . $domain_name . "\nExpiry Date: " . $expiry_date . "\nDays Remaining: " . $days_left . "\n\nPlease follow up with the customer regarding renewal.";
                }

                $CI->email->clear();
                $CI->email->from(get_option('smtp_email'), get_option('companyname'));
                $CI->email->to($email);
                $CI->email->subject($subj);
                $CI->email->message($body);
                $sent = $CI->email->send();

                $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                    'domain_id'         => $domain_id,
                    'asset_type'        => strtolower($asset['type']),
                    'customer_id'       => $client_id,
                    'staff_id'          => $staff_id,
                    'email_sent_to'     => $email,
                    'days_before_expiry'=> $days_left,
                    'sent_at'           => date('Y-m-d H:i:s'),
                    'status'            => $sent ? 'success' : 'failed',
                ]);
            }
        }

        // 4. Additional email addresses (ALWAYS fires, regardless of routing rule)
        //    These are "always notify" override addresses set in Settings.
        if (!empty($additional_emails)) {
            foreach ($additional_emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

                $already_sent = $CI->db->where('domain_id', $domain_id)
                    ->where('asset_type', strtolower($asset['type']))
                    ->where('email_sent_to', $email)
                    ->where('days_before_expiry', $days_left)
                    ->where('DATE(sent_at)', date('Y-m-d'))
                    ->get(db_prefix() . 'expiry_notification_logs')->row();
                if ($already_sent) continue;

                if (strtolower($asset['type']) === 'website') {
                    $subj = "Customer Website Expiry Alert";
                    $body = "Customer: " . $customer_name . "\nWebsite: " . $domain_name . "\nExpiry Date: " . $expiry_date . "\nDays Remaining: " . $days_left . "\n\nPlease follow up with the customer regarding renewal.";
                } else {
                    $subj = "Customer Domain Expiry Alert";
                    $body = "Customer: " . $customer_name . "\nDomain: " . $domain_name . "\nExpiry Date: " . $expiry_date . "\nDays Remaining: " . $days_left . "\n\nPlease follow up with the customer regarding renewal.";
                }

                $CI->email->clear();
                $CI->email->from(get_option('smtp_email'), get_option('companyname'));
                $CI->email->to($email);
                $CI->email->subject($subj);
                $CI->email->message($body);
                $sent = $CI->email->send();

                $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                    'domain_id'         => $domain_id,
                    'asset_type'        => strtolower($asset['type']),
                    'customer_id'       => $client_id,
                    'staff_id'          => null,
                    'email_sent_to'     => $email,
                    'days_before_expiry'=> $days_left,
                    'sent_at'           => date('Y-m-d H:i:s'),
                    'status'            => $sent ? 'success' : 'failed',
                ]);
            }
        }
    }
}

/**
 * Add head components.
 */
function domain_manager_add_head_components()
{
    if (get_instance()->app_modules->is_active(DOMAIN_MANAGER_MODULE_NAME)) {
        echo '<link href="' . module_dir_url(DOMAIN_MANAGER_MODULE_NAME, 'assets/css/style.css') . '" rel="stylesheet" type="text/css">';
    }
}


$CI = &get_instance();

// Register language files
register_language_files(DOMAIN_MANAGER_MODULE_NAME, [DOMAIN_MANAGER_MODULE_NAME]);

// Load helper functions
$CI->load->helper(DOMAIN_MANAGER_MODULE_NAME . '/domain_manager');

/**
 * Initialize Domain Manager module menu items.
 */
function domain_manager_init_menu_items()
{
    $CI = &get_instance();
    
    if (is_admin() || has_permission(DOMAIN_MANAGER_MODULE_NAME, '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item(DOMAIN_MANAGER_MODULE_NAME, [
            'name'     => _l('hosting_manager'),
            'icon'     => 'fa fa-server',
            'href'     => '#',
            'position' => 30,
        ]);

        $CI->app_menu->add_sidebar_children_item(DOMAIN_MANAGER_MODULE_NAME, [
            'slug'     => 'domain_manager_list_item',
            'name'     => _l('domain_manager_domain_list'),
            'href'     => admin_url('domain_manager_hostinger'),
            'position' => 40,
        ]);
        if (is_admin() || has_permission(DOMAIN_MANAGER_MODULE_NAME, '', 'hosting_view')) {
            $CI->app_menu->add_sidebar_children_item(DOMAIN_MANAGER_MODULE_NAME, [
                'slug'     => 'hosting_view_list',
                'name'     => _l('domain_manager_websites_list'),
                'href'     => admin_url('domain_manager_hostinger/hosting_list'),
                'position' => 45,
            ]);
        }
        if (is_admin() || has_permission(DOMAIN_MANAGER_MODULE_NAME, '', 'hosting_view')) {
            $CI->app_menu->add_sidebar_children_item(DOMAIN_MANAGER_MODULE_NAME, [
                'slug'     => 'emails_view_list',
                'name'     => _l('domain_manager_emails_list'),
                'href'     => admin_url('domain_manager_hostinger/emails'),
                'position' => 50,
            ]);
        }




    }

    if (is_admin() || has_permission(DOMAIN_MANAGER_MODULE_NAME, '', 'view')) {
        $CI->app_tabs->add_project_tab('domain_manager_projects', [
            'name'     => _l('domain_manager_domain_list'),
            'icon'     => 'fa fa-globe',
            'view'     => 'domain_manager_hostinger/admin/project_domain_manager',
            'position' => 10,
        ]);
        $CI->app_tabs->add_project_tab('domain_manager_websites', [
            'name'     => _l('domain_manager_websites_list'),
            'icon'     => 'fa fa-server',
            'view'     => 'domain_manager_hostinger/admin/project_hosting_manager',
            'position' => 11,
        ]);
        $CI->app_tabs->add_project_tab('domain_manager_emails', [
            'name'     => _l('domain_manager_emails_list'),
            'icon'     => 'fa fa-envelope',
            'view'     => 'domain_manager_hostinger/admin/project_emails_manager',
            'position' => 12,
        ]);
    }

    if (is_admin()) {
        $CI->app_menu->add_setup_menu_item(DOMAIN_MANAGER_MODULE_NAME, [
            'slug'     => 'domain_manager_setting',
            'name'     => _l('domain_manager_setting'),
            'href'     => admin_url('domain_manager_hostinger/setting'),
            'position' => 35,
        ]);
    }

    $CI->app_tabs->add_customer_profile_tab('domain_manager_domains', [
        'name'     => _l('domain_manager_domain_list'),
        'icon'     => 'fa fa-globe',
        'view'     => 'domain_manager_hostinger/admin/client_domain_manager',
        'position' => 10,
    ]);
    $CI->app_tabs->add_customer_profile_tab('domain_manager_websites', [
        'name'     => _l('domain_manager_websites_list'),
        'icon'     => 'fa fa-server',
        'view'     => 'domain_manager_hostinger/admin/client_hosting_manager',
        'position' => 11,
    ]);
    $CI->app_tabs->add_customer_profile_tab('domain_manager_emails', [
        'name'     => _l('domain_manager_emails_list'),
        'icon'     => 'fa fa-envelope',
        'view'     => 'domain_manager_hostinger/admin/client_emails_manager',
        'position' => 12,
    ]);
}

/**
 * Module activation hook.
 */
register_activation_hook(DOMAIN_MANAGER_MODULE_NAME, 'domain_manager_activate_module');

function domain_manager_activate_module()
{
    require_once __DIR__ . '/install.php';
}



/**
 * Define module permissions.
 */
function domain_manager_define_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view'   => _l('permission_view') . ' (' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
            'hosting_view'   => _l('hosting_permission_view') . ' (' . _l('permission_global') . ')',
            'hosting_create' => _l('hosting_permission_create'),
            'hosting_edit'   => _l('hosting_permission_edit'),
            'hosting_delete' => _l('hosting_permission_delete'),
            
        ],
    ];

    register_staff_capabilities(DOMAIN_MANAGER_MODULE_NAME, $capabilities, _l('domain_manager'));
}

/**
 * Add settings link in module list.
 *
 * @param array $actions Current actions.
 * @return array
 */
function domain_manager_add_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('domain_manager_hostinger/setting') . '">' . _l('settings') . '</a>';
    return $actions;
}
