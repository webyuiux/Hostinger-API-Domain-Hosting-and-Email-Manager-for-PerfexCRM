<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Domain_manager_hostinger extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'domain_manager_model',
            'staff_model',
            'hosting_details_model',
            'settings_model',
            'hostinger_api_model',
            'email_manager_model',
        ]);
        $this->load->helper('domain_manager_hostinger/domain_manager');

        if (!$this->db->field_exists('ssl_status', db_prefix() . 'hosting_details')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `ssl_status` VARCHAR(100) NULL DEFAULT 'active'");
        }
        if (!$this->db->field_exists('domain_status', db_prefix() . 'hosting_details')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `domain_status` VARCHAR(100) NULL DEFAULT 'active'");
        }
        if (!$this->db->field_exists('server_type', db_prefix() . 'hosting_details')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `server_type` VARCHAR(100) NULL DEFAULT 'Shared'");
        }
        // Staff assignment per domain — added for direct email dispatch on link
        if (!$this->db->field_exists('assigned_staff_id', db_prefix() . 'domain_manager')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "domain_manager` ADD `assigned_staff_id` INT(11) NULL DEFAULT NULL");
        }
        // Staff assignment per website/hosting
        if (!$this->db->field_exists('assigned_staff_id', db_prefix() . 'hosting_details')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `assigned_staff_id` INT(11) NULL DEFAULT NULL");
        }
        // Client Email field for emails manager
        if ($this->db->table_exists(db_prefix() . 'emails_manager') && !$this->db->field_exists('client_email', db_prefix() . 'emails_manager')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "emails_manager` ADD `client_email` VARCHAR(255) NULL DEFAULT NULL");
        }
        // Added for extra requirements
        if (!$this->db->field_exists('client_email', db_prefix() . 'domain_manager')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "domain_manager` ADD `client_email` VARCHAR(255) NULL DEFAULT NULL");
        }
        if (!$this->db->field_exists('available_mailbox_count', db_prefix() . 'domain_manager')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "domain_manager` ADD `available_mailbox_count` INT(11) NULL DEFAULT 0");
        }
        if (!$this->db->field_exists('start_date', db_prefix() . 'domain_manager')) {
            $this->db->query("ALTER TABLE `" . db_prefix() . "domain_manager` ADD `start_date` DATE NULL DEFAULT NULL");
        }

        // Add unique index on emails_manager to prevent duplicate records
        if ($this->db->table_exists(db_prefix() . 'emails_manager')) {
            $index_exists = $this->db->query("SHOW INDEX FROM `" . db_prefix() . "emails_manager` WHERE Key_name = 'unique_domain_mailbox'")->num_rows();
            if (!$index_exists) {
                // Delete duplicate records keeping only the first one
                $this->db->query("DELETE t1 FROM " . db_prefix() . "emails_manager t1 INNER JOIN " . db_prefix() . "emails_manager t2 WHERE t1.id > t2.id AND t1.domain = t2.domain AND t1.mailbox_name = t2.mailbox_name AND t1.deleted = 0 AND t2.deleted = 0;");
                // Add unique constraint
                $this->db->query("ALTER TABLE `" . db_prefix() . "emails_manager` ADD UNIQUE KEY `unique_domain_mailbox` (`domain`, `mailbox_name`);");
            }
        }
    }

    // -----------------------------------------------------------------------
    // DOMAINS
    // -----------------------------------------------------------------------

    public function index()
    {
        if (!has_permission('domain_manager', '', 'view')) {
            access_denied('domain_manager');
        }
        $data['title'] = _l('domain_manager_list');
        
        // Fetch actual records for the portfolio containers
        $data['external_domains_list'] = $this->domain_manager_model->get_portfolio('external');
        $data['internal_domains_list'] = $this->domain_manager_model->get_portfolio('internal');

        // TEMPORARY DEBUG STATEMENT (Uncomment to use)
        /*
        echo '<pre>';
        print_r($this->domain_manager_model->get_expiring_soon(5));
        echo '</pre>';
        die;
        */

        // Fetch expiring domains (next 5 days)
        $data['expiring_soon_list'] = $this->domain_manager_model->get_expiring_soon(5);
        $data['expiring_soon_count'] = count($data['expiring_soon_list']);

        // Populate counts for stat cards
        $data['external_domains'] = count($data['external_domains_list']);
        $data['internal_domains'] = count($data['internal_domains_list']);
        $data['total_assets']     = $data['external_domains'] + $data['internal_domains'];
        
        $data['externalCount']    = $data['external_domains'];
        $data['internalCount']    = $data['internal_domains'];
        $data['expiring_soon']    = $data['expiring_soon_count'];

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('domain_manager_hostinger', 'tables/domain_manager'));
        }
        $this->load->view('index', $data);
    }

    public function create()
    {
        if (!has_permission('domain_manager', '', 'create')) {
            access_denied('domain_manager');
        }
        $data['title']   = _l('domain_manager_add');
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['staff']   = $this->staff_model->get('', ['active' => 1]);
        $this->load->view('create', $data);
    }

    public function save_domain_manager()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'create')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['name'])) {
            set_alert('warning', 'The domain name field is required.');
            redirect(admin_url('domain_manager_hostinger/create'));
        }
        $domain_name = strtolower(trim($data['name']));

        // Check for duplicate domain name
        $this->db->where('domain_name', $domain_name);
        $existing = $this->db->get(db_prefix() . 'domain_manager')->row();
        if ($existing) {
            set_alert('warning', 'Domain "' . $domain_name . '" already exists in the table.');
            redirect(admin_url('domain_manager_hostinger/create'));
        }

        $insert_data = [
            'domain_name'  => $domain_name,
            'registrar'    => $data['domain_manager_registrar'] ?? null,
            'domain_type'  => in_array($data['domain_type'] ?? '', ['internal', 'external']) ? $data['domain_type'] : 'external',
            'status'       => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'client_id'    => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'client_email' => !empty($data['client_email']) ? trim($data['client_email']) : null,
            'available_mailbox_count' => isset($data['available_mailbox_count']) ? (int)$data['available_mailbox_count'] : 0,
            'start_date'   => !empty($data['start_date']) ? to_sql_date($data['start_date']) : null,
            'assigned_staff_id' => !empty($data['assigned_staff_id']) ? (int)$data['assigned_staff_id'] : null,
            'purchase_date'=> !empty($data['domain_manager_purchase_date']) ? to_sql_date($data['domain_manager_purchase_date']) : null,
            'expiry_date'  => !empty($data['domain_manager_expiry_date']) ? to_sql_date($data['domain_manager_expiry_date']) : null,
            'description'  => $data['description'] ?? null,
            'created_by'   => get_staff_user_id(),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        $id = $this->domain_manager_model->add($insert_data);
        set_alert($id ? 'success' : 'danger', $id ? 'Domain added successfully.' : 'Failed to add domain.');
        redirect(admin_url('domain_manager_hostinger') . '#domain-saved');
    }

    public function view($id)
    {
        if (!has_permission('domain_manager', '', 'view')) {
            access_denied('domain_manager');
        }
        $data['domain']  = $this->domain_manager_model->get($id);
        $data['hosting'] = $this->hosting_details_model->get_domain_id($id);
        
        $this->load->helper('domain_manager_hostinger/domain_manager');
        $whois = domain_manager_get_whois_info($data['domain']->domain_name);
        $data['whois'] = $whois;
        $data['whois_raw'] = $whois ? $whois['raw_text'] : "Unable to retrieve WHOIS information for {$data['domain']->domain_name}.";

        $this->load->view('view', $data);
    }

    public function edit($id)
    {
        if (!has_permission('domain_manager', '', 'edit')) {
            access_denied('domain_manager');
        }
        $data['domain']  = $this->domain_manager_model->get($id);
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['staff']   = $this->staff_model->get('', ['active' => 1]);
        $this->load->view('edit', $data);
    }

    public function update_domain_manager()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'edit')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['id']) || empty($data['name'])) {
            set_alert('warning', 'Domain name is required.');
            redirect(admin_url('domain_manager_hostinger'));
        }
        $update_data = [
            'domain_name'  => strtolower(trim($data['name'])),
            'registrar'    => $data['domain_manager_registrar'] ?? null,
            'domain_type'  => in_array($data['domain_type'] ?? '', ['internal', 'external']) ? $data['domain_type'] : 'external',
            'status'       => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'client_id'    => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'client_email' => !empty($data['client_email']) ? trim($data['client_email']) : null,
            'available_mailbox_count' => isset($data['available_mailbox_count']) ? (int)$data['available_mailbox_count'] : 0,
            'start_date'   => !empty($data['start_date']) ? to_sql_date($data['start_date']) : null,
            'assigned_staff_id' => !empty($data['assigned_staff_id']) ? (int)$data['assigned_staff_id'] : null,
            'purchase_date'=> !empty($data['domain_manager_purchase_date']) ? to_sql_date($data['domain_manager_purchase_date']) : null,
            'expiry_date'  => !empty($data['domain_manager_expiry_date']) ? to_sql_date($data['domain_manager_expiry_date']) : null,
            'description'  => $data['description'] ?? null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        $r = $this->domain_manager_model->update($data['id'], $update_data);
        set_alert($r ? 'success' : 'danger', $r ? 'Domain updated successfully.' : 'Failed to update domain.');
        redirect(admin_url('domain_manager_hostinger') . '#domain-saved');
    }

    public function delete($id)
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'delete')) {
            access_denied('domain_manager');
        }
        if (!is_numeric($id)) {
            set_alert('danger', 'Invalid domain ID.');
            redirect(admin_url('domain_manager_hostinger'));
        }
        $r = $this->domain_manager_model->delete($id);
        set_alert($r ? 'success' : 'danger', $r ? 'Domain deleted.' : 'Failed to delete domain.');
        redirect(admin_url('domain_manager_hostinger'));
    }

    // -----------------------------------------------------------------------
    // SETTINGS
    // -----------------------------------------------------------------------

    public function setting()
    {
        if (!is_admin()) {
            access_denied('domain_manager');
        }
        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            if (!isset($post_data['settings']['domain_manager_notify_recipients'])) {
                $post_data['settings']['domain_manager_notify_recipients'] = '';
            }
            
            if (isset($post_data['settings']['domain_manager_notify_specific_staff'])) {
                $post_data['settings']['domain_manager_notify_specific_staff'] = implode(',', $post_data['settings']['domain_manager_notify_specific_staff']);
            } else {
                $post_data['settings']['domain_manager_notify_specific_staff'] = '';
            }

            // Only update token if a new value was provided
            if (isset($post_data['settings']['domain_manager_hostinger_api_token'])
                && trim($post_data['settings']['domain_manager_hostinger_api_token']) === '') {
                unset($post_data['settings']['domain_manager_hostinger_api_token']);
            }
            $success = $this->settings_model->update($post_data);
            // Perfex returns 0 (falsy) when all values are unchanged — treat that as success too
            if ($success !== false) {
                set_alert('success', 'Settings saved successfully.');
            } else {
                set_alert('danger', 'Failed to save settings.');
            }
            redirect(admin_url('domain_manager_hostinger/setting'));

        }
        $data['title']               = _l('domain_manager_setting');
        $data['hostinger_token']     = get_option('domain_manager_hostinger_api_token');
        $data['hostinger_token_set'] = !empty($data['hostinger_token']);
        $data['notification_emails'] = get_option('domain_manager_notification_emails');
        $data['notify_days']         = get_option('domain_manager_notify_days');
        if (empty($data['notify_days'])) {
            $data['notify_days'] = '25';
        }
        
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        
        $data['notify_recipients'] = get_option('domain_manager_notify_recipients');
        
        $specific_staff = get_option('domain_manager_notify_specific_staff');
        $data['notify_specific_staff'] = !empty($specific_staff) ? explode(',', $specific_staff) : [];

        $this->load->view('manage', $data);
    }

    public function get_domain_details_by_name_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $domain_name = $this->input->get('domain');
        if (empty($domain_name)) {
            echo json_encode(['success' => false]);
            return;
        }

        $domain_name = strtolower(trim($domain_name));
        $this->db->where('domain_name', $domain_name);
        $this->db->where('deleted', 0);
        $domain = $this->db->get(db_prefix() . 'domain_manager')->row();

        if ($domain) {
            echo json_encode(['success' => true, 'data' => $domain]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function send_expiry_alerts_ajax()
    {
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $CI = &get_instance();
        $CI->load->model(DOMAIN_MANAGER_MODULE_NAME . '/domain_manager_model');
        $CI->load->model(DOMAIN_MANAGER_MODULE_NAME . '/hosting_details_model');

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

        $domains  = $CI->domain_manager_model->get();
        $websites = $CI->hosting_details_model->get();

        $expiring_assets = [];
        $today = new DateTime('midnight');

        // force=true  → test mode (fake data, skip duplicate check)
        $force_test = ($this->input->get('force') === 'true');

        // When triggered manually from the UI we use a generous window:
        // any asset expiring within max(notify_days) or 30 days qualifies.
        // Exact-day matching is reserved for the automated cron job.
        $max_notify_days = !empty($notify_days) ? max(30, max($notify_days)) : 30;

        foreach ($domains as $d) {
            if (!empty($d['expiry_date']) && $d['expiry_date'] !== '0000-00-00') {
                $exp_date = new DateTime($d['expiry_date']);
                $diff     = $today->diff($exp_date);
                // Include future-expiring domains within the max window
                if ($force_test || ($diff->invert === 0 && $diff->days <= $max_notify_days)) {
                    $expiring_assets[] = [
                        'id'               => $d['id'],
                        'type'             => 'Domain',
                        'name'             => $d['domain_name'],
                        'expiry'           => $d['expiry_date'],
                        'days'             => $force_test ? 25 : $diff->days,
                        'client_id'        => !empty($d['client_id'])       ? (int)$d['client_id']       : 0,
                        'assigned_staff_id'=> !empty($d['assigned_staff_id'])? (int)$d['assigned_staff_id']: 0
                    ];
                }
            }
        }

        foreach ($websites as $w) {
            if (!empty($w['expiration_date']) && $w['expiration_date'] !== '0000-00-00') {
                $exp_date = new DateTime($w['expiration_date']);
                $diff     = $today->diff($exp_date);
                if ($force_test || ($diff->invert === 0 && $diff->days <= $max_notify_days)) {
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
                        'days'             => $force_test ? 25 : $diff->days,
                        'client_id'        => !empty($w['client_id'])       ? (int)$w['client_id']       : 0,
                        'assigned_staff_id'=> $assigned_staff_id
                    ];
                }
            }
        }

        if (empty($expiring_assets) && $force_test) {
            $first_client = $CI->db->select('userid')->from(db_prefix() . 'clients')->where('active', 1)->get()->row();
            $client_id = $first_client ? (int)$first_client->userid : 0;
            $expiring_assets[] = [
                'id'     => 9999,
                'type'   => 'Domain',
                'name'   => 'test-domain.com',
                'expiry' => date('Y-m-d', strtotime('+25 days')),
                'days'   => 25,
                'client_id' => $client_id
            ];
        }

        if (empty($expiring_assets)) {
            echo json_encode(['success' => true, 'message' => 'No domains or websites are expiring within the next ' . $max_notify_days . ' days. Nothing to send.']);
            exit;
        }

        $sent_to    = [];
        $failed_to  = [];
        $skipped_assets = []; // Track skipped assets with detailed reasons

        $CI->load->config('email');
        $CI->load->library('email');

        foreach ($expiring_assets as $asset) {
            $client_id        = $asset['client_id'];
            $domain_id        = $asset['id'];
            $domain_name      = $asset['name'];
            $expiry_date      = $asset['expiry'];
            $days_left        = $asset['days'];
            $assigned_staff_id= isset($asset['assigned_staff_id']) ? $asset['assigned_staff_id'] : 0;

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
            $send_to_additional= false;

            if ($notify_recipients === 'Customer Only') {
                $send_to_customer = true;
            } elseif ($notify_recipients === 'Staff Only') {
                $send_to_staff = true;
            } elseif ($notify_recipients === "Customer's Contact Email + Staff Assigned to Customer") {
                $send_to_customer = true;
                $send_to_staff    = true;
            } elseif ($notify_recipients === 'Customer + Assigned Staff + Additional Emails') {
                $send_to_customer  = true;
                $send_to_staff     = true;
                $send_to_additional= true;
            }

            $asset_sent = false;
            $reasons = [];

            // 1. Customer contact emails
            if ($send_to_customer) {
                if ($client_id <= 0) {
                    $reasons[] = "No active client linked (Customer notifications enabled)";
                } else {
                    $primary_contacts = $CI->db->select('email, firstname, lastname')
                        ->from(db_prefix() . 'contacts')
                        ->where('userid', $client_id)
                        ->where('is_primary', 1)
                        ->where('active', 1)
                        ->get()->result_array();

                    if (empty($primary_contacts)) {
                        $reasons[] = "Client has no active primary contact (Customer notifications enabled)";
                    } else {
                        foreach ($primary_contacts as $contact) {
                            $email = $contact['email'];
                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                if (!$force_test) {
                                    $already_sent = $CI->db->where('domain_id', $domain_id)
                                        ->where('asset_type', strtolower($asset['type']))
                                        ->where('email_sent_to', $email)
                                        ->where('days_before_expiry', $days_left)
                                        ->where('sent_at >=', date('Y-m-d 00:00:00'))
                                        ->get(db_prefix() . 'expiry_notification_logs')->row();
                                    if ($already_sent) {
                                        $reasons[] = "Already sent to customer contact ($email) today";
                                        continue;
                                    }
                                }

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

                                if ($sent) {
                                    $sent_to[] = $email;
                                    $asset_sent = true;
                                } else {
                                    $failed_to[] = $email;
                                    $reasons[] = "SMTP send failed to customer contact ($email)";
                                }

                                $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                                    'domain_id'        => $domain_id,
                                    'asset_type'       => strtolower($asset['type']),
                                    'customer_id'      => $client_id,
                                    'staff_id'         => null,
                                    'email_sent_to'    => $email,
                                    'days_before_expiry'=> $days_left,
                                    'sent_at'          => date('Y-m-d H:i:s'),
                                    'status'           => $sent ? 'success' : 'failed'
                                ]);
                            } else {
                                $reasons[] = "Invalid contact email format: '$email'";
                            }
                        }
                    }
                }
            }

            // 2. Staff emails (customer admins + assigned staff)
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
                    $domain_staff = $CI->db->select('staffid, email, firstname, lastname')
                        ->from(db_prefix() . 'staff')
                        ->where('staffid', $assigned_staff_id)
                        ->where('active', 1)
                        ->get()->row_array();
                    if ($domain_staff) {
                        $staff_to_email[$domain_staff['staffid']] = $domain_staff;
                    }
                }

                if (empty($staff_to_email)) {
                    $reasons[] = "No active customer admins or assigned staff found (Staff notifications enabled)";
                } else {
                    foreach ($staff_to_email as $staff) {
                        $email    = $staff['email'];
                        $staff_id = $staff['staffid'];
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            if (!$force_test) {
                                $already_sent = $CI->db->where('domain_id', $domain_id)
                                    ->where('asset_type', strtolower($asset['type']))
                                    ->where('email_sent_to', $email)
                                    ->where('days_before_expiry', $days_left)
                                    ->where('sent_at >=', date('Y-m-d 00:00:00'))
                                    ->get(db_prefix() . 'expiry_notification_logs')->row();
                                if ($already_sent) {
                                    $reasons[] = "Already sent to staff member ($email) today";
                                    continue;
                                }
                            }

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

                            if ($sent) {
                                $sent_to[] = $email;
                                $asset_sent = true;
                            } else {
                                $failed_to[] = $email;
                                $reasons[] = "SMTP send failed to staff member ($email)";
                            }

                            $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                                'domain_id'        => $domain_id,
                                'asset_type'       => strtolower($asset['type']),
                                'customer_id'      => $client_id,
                                'staff_id'         => $staff_id,
                                'email_sent_to'    => $email,
                                'days_before_expiry'=> $days_left,
                                'sent_at'          => date('Y-m-d H:i:s'),
                                'status'           => $sent ? 'success' : 'failed'
                            ]);
                        } else {
                            $reasons[] = "Invalid staff email format: '$email'";
                        }
                    }
                }
            }

            // 3. Specific staff
            if (!empty($specific_staff_ids)) {
                $staff_records = $CI->db->select('staffid, email, firstname, lastname')
                    ->from(db_prefix() . 'staff')
                    ->where_in('staffid', $specific_staff_ids)
                    ->where('active', 1)
                    ->get()->result_array();

                if (empty($staff_records)) {
                    $reasons[] = "No active specific staff members resolved from configuration";
                } else {
                    foreach ($staff_records as $staff) {
                        $email    = $staff['email'];
                        $staff_id = $staff['staffid'];
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            if (!$force_test) {
                                $already_sent = $CI->db->where('domain_id', $domain_id)
                                    ->where('asset_type', strtolower($asset['type']))
                                    ->where('email_sent_to', $email)
                                    ->where('days_before_expiry', $days_left)
                                    ->where('sent_at >=', date('Y-m-d 00:00:00'))
                                    ->get(db_prefix() . 'expiry_notification_logs')->row();
                                if ($already_sent) {
                                    $reasons[] = "Already sent to specific staff ($email) today";
                                    continue;
                                }
                            }

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

                            if ($sent) {
                                $sent_to[] = $email;
                                $asset_sent = true;
                            } else {
                                $failed_to[] = $email;
                                $reasons[] = "SMTP send failed to specific staff ($email)";
                            }

                            $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                                'domain_id'        => $domain_id,
                                'asset_type'       => strtolower($asset['type']),
                                'customer_id'      => $client_id,
                                'staff_id'         => $staff_id,
                                'email_sent_to'    => $email,
                                'days_before_expiry'=> $days_left,
                                'sent_at'          => date('Y-m-d H:i:s'),
                                'status'           => $sent ? 'success' : 'failed'
                            ]);
                        } else {
                            $reasons[] = "Invalid specific staff email format: '$email'";
                        }
                    }
                }
            }

            // 4. Additional email addresses
            if ($send_to_additional) {
                if (empty($additional_emails)) {
                    $reasons[] = "No additional emails configured (Additional notifications enabled)";
                } else {
                    foreach ($additional_emails as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            if (!$force_test) {
                                $already_sent = $CI->db->where('domain_id', $domain_id)
                                    ->where('asset_type', strtolower($asset['type']))
                                    ->where('email_sent_to', $email)
                                    ->where('days_before_expiry', $days_left)
                                    ->where('sent_at >=', date('Y-m-d 00:00:00'))
                                    ->get(db_prefix() . 'expiry_notification_logs')->row();
                                if ($already_sent) {
                                    $reasons[] = "Already sent to additional email ($email) today";
                                    continue;
                                }
                            }

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

                            if ($sent) {
                                $sent_to[] = $email;
                                $asset_sent = true;
                            } else {
                                $failed_to[] = $email;
                                $reasons[] = "SMTP send failed to additional email ($email)";
                            }

                            $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                                'domain_id'        => $domain_id,
                                'asset_type'       => strtolower($asset['type']),
                                'customer_id'      => $client_id,
                                'staff_id'         => null,
                                'email_sent_to'    => $email,
                                'days_before_expiry'=> $days_left,
                                'sent_at'          => date('Y-m-d H:i:s'),
                                'status'           => $sent ? 'success' : 'failed'
                            ]);
                        } else {
                            $reasons[] = "Invalid additional email format: '$email'";
                        }
                    }
                }
            }

            if (!$asset_sent) {
                $skipped_assets[] = [
                    'type'    => $asset['type'],
                    'name'    => $domain_name,
                    'expiry'  => $expiry_date,
                    'days'    => $days_left,
                    'reasons' => array_unique($reasons)
                ];
            }
        }

        $sent_to   = array_unique($sent_to);
        $failed_to = array_unique($failed_to);

        if (!empty($sent_to)) {
            $msg = 'Successfully sent ' . count($sent_to) . ' alert(s) to: ' . implode(', ', $sent_to);
            if (!empty($failed_to)) {
                $msg .= '. Failed for: ' . implode(', ', $failed_to);
            }
            if (!empty($skipped_assets)) {
                $msg .= '. Skipped ' . count($skipped_assets) . ' asset(s).';
            }
            echo json_encode(['success' => true, 'message' => $msg]);
        } elseif (!empty($failed_to)) {
            echo json_encode(['success' => false, 'message' => 'Email sending failed for: ' . implode(', ', $failed_to) . '. Please check your SMTP settings under Setup → Email Settings.']);
        } elseif (!empty($skipped_assets)) {
            // Detailed message with why each asset was skipped
            $reasons_summary = [];
            foreach ($skipped_assets as $sa) {
                $reasons_str = !empty($sa['reasons']) ? implode('; ', $sa['reasons']) : 'No specific reason';
                $reasons_summary[] = $sa['type'] . ' "' . $sa['name'] . '" (' . $sa['days'] . ' days left): ' . $reasons_str;
            }
            $msg = 'No emails were sent. Details for ' . count($skipped_assets) . ' eligible asset(s):<br/>- ' . implode('<br/>- ', $reasons_summary);
            echo json_encode(['success' => false, 'message' => $msg]);
        } else {
            echo json_encode(['success' => true, 'message' => 'All alerts for today have already been sent. Duplicate notifications are prevented.']);
        }
        exit;
    }

    // -----------------------------------------------------------------------
    // HOSTINGER API ACTIONS
    // -----------------------------------------------------------------------

    public function hostinger_api_test()
    {
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $result = $this->hostinger_api_model->test_connection();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    /**
     * AJAX: Optionally sync domains from Hostinger, then return all unlinked
     * Hostinger-synced domains plus auto-match suggestions, staff list, and
     * any existing assigned_staff_id per domain.
     */
    public function sync_and_show_unlinked()
    {
        if (!is_admin()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $do_sync  = ($this->input->get('sync') === '1');
        $sync_msg = '';

        if ($do_sync) {
            $sync_result = $this->hostinger_api_model->sync_domains();
            $sync_msg    = isset($sync_result['message']) ? $sync_result['message'] : '';
        }

        $unlinked     = $this->hostinger_api_model->get_unlinked_domains();
        $auto_matches = $this->hostinger_api_model->auto_match_domains_to_clients();

        // Clients for dropdowns
        $clients_raw = $this->domain_manager_model->get_clients();
        $clients = [];
        foreach ($clients_raw as $c) {
            $clients[] = ['id' => (int)$c['userid'], 'name' => $c['company']];
        }

        // Staff for dropdowns
        $staff_raw = $this->staff_model->get('', ['active' => 1]);
        $staff = [];
        foreach ($staff_raw as $s) {
            $staff[] = [
                'id'   => (int)$s['staffid'],
                'name' => $s['firstname'] . ' ' . $s['lastname'],
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'sync_message' => $sync_msg,
            'unlinked'     => $unlinked,   // includes assigned_staff_id field
            'auto_matches' => $auto_matches,
            'clients'      => $clients,
            'staff'        => $staff,
        ]);
        exit;
    }

    /**
     * AJAX: Save domain → client + staff assignments, then immediately send
     * expiry notification emails to the assigned customer contact and staff.
     *
     * POST: links = [{domain_id, client_id, staff_id}, ...]
     */
    public function bulk_link_domains()
    {
        if (!is_admin()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $raw = $this->input->post('links');
        if (empty($raw) || !is_array($raw)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No links data received.']);
            exit;
        }

        // Validate and build save list
        $save_list = [];
        foreach ($raw as $row) {
            $domain_id    = (int)($row['domain_id'] ?? 0);
            $client_id    = (int)($row['client_id'] ?? 0);
            $staff_id     = (int)($row['staff_id']  ?? 0);
            $custom_email = trim($row['custom_email'] ?? '');
            if ($domain_id > 0 && (!empty($custom_email) || $staff_id > 0)) {
                $save_list[] = compact('domain_id', 'client_id', 'staff_id', 'custom_email');
            }
        }

        if (empty($save_list)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please provide an email or assign staff for at least one domain.']);
            exit;
        }

        // ── STEP 1: Save client_id + assigned_staff_id to each domain ──────────
        $now     = date('Y-m-d H:i:s');
        $updated = 0;
        foreach ($save_list as $item) {
            $update = [
                'assigned_staff_id'=> $item['staff_id'] > 0 ? $item['staff_id'] : null,
                'updated_at'       => $now,
            ];
            // Only update client_id if explicitly provided (though we are removing it from UI)
            if ($item['client_id'] > 0) {
                $update['client_id'] = $item['client_id'];
            }
            $this->db->where('id', $item['domain_id']);
            $this->db->update(db_prefix() . 'domain_manager', $update);
            $updated += $this->db->affected_rows();
        }

        // ── STEP 2: Immediately send notification emails ────────────────────────
        // Decrypt SMTP password and set up PHPMailer (same logic as send_test_email_ajax)
        $smtp_host = get_option('smtp_host');
        $smtp_port = (int)(get_option('smtp_port') ?: 465);
        $smtp_enc  = get_option('smtp_encryption') ?: 'ssl';
        $smtp_user = get_option('smtp_username');
        $smtp_pass = $this->encryption->decrypt(get_option('smtp_password'));
        $from_email= get_option('smtp_email');
        $from_name = get_option('smtp_email_name') ?: get_option('companyname') ?: 'Domain Manager';

        $phpmailer_paths = [
            FCPATH . 'vendor/phpmailer/phpmailer/src/PHPMailer.php',
            APPPATH . '../vendor/phpmailer/phpmailer/src/PHPMailer.php',
        ];
        $phpmailer_found = false;
        foreach ($phpmailer_paths as $pm_path) {
            if (file_exists($pm_path)) {
                require_once $pm_path;
                require_once dirname($pm_path) . '/SMTP.php';
                require_once dirname($pm_path) . '/Exception.php';
                $phpmailer_found = true;
                break;
            }
        }

        $sent_to   = [];
        $failed_to = [];

        foreach ($save_list as $item) {
            // Reload domain with fresh expiry data
            $domain = $this->db->select('id, domain_name, expiry_date, status')
                ->from(db_prefix() . 'domain_manager')
                ->where('id', $item['domain_id'])
                ->get()->row();

            if (!$domain) continue;

            $domain_name = $domain->domain_name;
            $expiry_date = $domain->expiry_date ?: 'N/A';
            $days_left   = ($expiry_date !== 'N/A')
                ? (int)floor((strtotime($expiry_date) - time()) / 86400)
                : 0;

            // Fetch client name + primary contact
            $client = $this->db->select('company')
                ->from(db_prefix() . 'clients')
                ->where('userid', $item['client_id'])
                ->get()->row();
            $customer_name = $client ? $client->company : 'Valued Customer';

            // ── Customer email ──────────────────────────────────────────────
            $contacts_to_email = [];
            
            // If custom email is provided and valid, use it.
            if (!empty($item['custom_email']) && filter_var($item['custom_email'], FILTER_VALIDATE_EMAIL)) {
                $contacts_to_email[] = [
                    'email'     => $item['custom_email'],
                    'firstname' => 'Valued',
                    'lastname'  => 'Customer'
                ];
            } else if ($item['client_id'] > 0) {
                // Fallback to primary contacts if a client_id happened to be passed
                $primary_contacts = $this->db->select('email, firstname, lastname')
                    ->from(db_prefix() . 'contacts')
                    ->where('userid', $item['client_id'])
                    ->where('is_primary', 1)
                    ->where('active', 1)
                    ->get()->result_array();
                
                foreach ($primary_contacts as $c) {
                    if (filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
                        $contacts_to_email[] = $c;
                    }
                }
            }

            foreach ($contacts_to_email as $contact) {
                $contact_name = trim($contact['firstname'] . ' ' . $contact['lastname']);
                if (empty($contact_name) || $contact_name === 'Valued Customer') {
                    $contact_name = $customer_name;
                }
                
                $subj = 'Domain Expiry Notice: ' . $domain_name;
                $body = '<div style="font-family:Arial,sans-serif;max-width:560px;padding:24px;border:1px solid #e5e7eb;border-radius:8px;">'
                    . '<h2 style="color:#4f46e5;margin-top:0;">Domain Expiry Notice</h2>'
                    . '<p>Hello <strong>' . htmlspecialchars($contact_name) . '</strong>,</p>'
                    . '<p>This is a notification that the following domain has been assigned to your account and will expire soon:</p>'
                    . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
                    . '<tr><td style="padding:8px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:bold;">Domain</td><td style="padding:8px;border:1px solid #e5e7eb;">' . htmlspecialchars($domain_name) . '</td></tr>'
                    . '<tr><td style="padding:8px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:bold;">Expiry Date</td><td style="padding:8px;border:1px solid #e5e7eb;">' . $expiry_date . '</td></tr>'
                    . '<tr><td style="padding:8px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:bold;">Days Remaining</td><td style="padding:8px;border:1px solid #e5e7eb;color:' . ($days_left <= 30 ? '#dc2626' : '#16a34a') . ';">' . $days_left . ' days</td></tr>'
                    . '</table>'
                    . '<p>Please renew your domain before the expiry date to avoid service interruption.</p>'
                    . '<hr style="border:none;border-top:1px solid #e5e7eb;">'
                    . '<p style="color:#6b7280;font-size:12px;">Sent by ' . htmlspecialchars(get_option('companyname') ?: 'Domain Manager') . ' on ' . date('Y-m-d H:i:s') . '</p>'
                    . '</div>';

                $email_sent = $this->_send_email_via_phpmailer(
                    $phpmailer_found, $smtp_host, $smtp_port, $smtp_enc, $smtp_user, $smtp_pass,
                    $from_email, $from_name, $contact['email'], $subj, $body
                );

                if ($email_sent) {
                    $sent_to[] = $contact['email'];
                } else {
                    $failed_to[] = $contact['email'];
                }

                // Log
                $this->db->insert(db_prefix() . 'expiry_notification_logs', [
                    'domain_id'         => $item['domain_id'],
                    'customer_id'       => $item['client_id'],
                    'staff_id'          => null,
                    'email_sent_to'     => $contact['email'],
                    'days_before_expiry'=> $days_left,
                    'sent_at'           => $now,
                    'status'            => $email_sent ? 'success' : 'failed',
                ]);
            }

            // ── Staff email ─────────────────────────────────────────────────
            if ($item['staff_id'] > 0) {
                $staff_member = $this->db->select('email, firstname, lastname')
                    ->from(db_prefix() . 'staff')
                    ->where('staffid', $item['staff_id'])
                    ->where('active', 1)
                    ->get()->row();

                if ($staff_member && filter_var($staff_member->email, FILTER_VALIDATE_EMAIL)) {
                    $subj = 'Domain Assignment Alert: ' . $domain_name;
                    $body = '<div style="font-family:Arial,sans-serif;max-width:560px;padding:24px;border:1px solid #e5e7eb;border-radius:8px;">'
                        . '<h2 style="color:#4f46e5;margin-top:0;">Domain Assignment – Staff Alert</h2>'
                        . '<p>Hello <strong>' . htmlspecialchars($staff_member->firstname) . '</strong>,</p>'
                        . '<p>You have been assigned as the responsible staff member for the following domain:</p>'
                        . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
                        . '<tr><td style="padding:8px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:bold;">Domain</td><td style="padding:8px;border:1px solid #e5e7eb;">' . htmlspecialchars($domain_name) . '</td></tr>'
                        . '<tr><td style="padding:8px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:bold;">Client</td><td style="padding:8px;border:1px solid #e5e7eb;">' . htmlspecialchars($customer_name) . '</td></tr>'
                        . '<tr><td style="padding:8px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:bold;">Expiry Date</td><td style="padding:8px;border:1px solid #e5e7eb;">' . $expiry_date . '</td></tr>'
                        . '<tr><td style="padding:8px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:bold;">Days Remaining</td><td style="padding:8px;border:1px solid #e5e7eb;color:' . ($days_left <= 30 ? '#dc2626' : '#16a34a') . ';">' . $days_left . ' days</td></tr>'
                        . '</table>'
                        . '<p>Please follow up with the customer regarding renewal if necessary.</p>'
                        . '<hr style="border:none;border-top:1px solid #e5e7eb;">'
                        . '<p style="color:#6b7280;font-size:12px;">Sent by ' . htmlspecialchars(get_option('companyname') ?: 'Domain Manager') . ' on ' . date('Y-m-d H:i:s') . '</p>'
                        . '</div>';

                    $email_sent = $this->_send_email_via_phpmailer(
                        $phpmailer_found, $smtp_host, $smtp_port, $smtp_enc, $smtp_user, $smtp_pass,
                        $from_email, $from_name, $staff_member->email, $subj, $body
                    );

                    if ($email_sent) {
                        $sent_to[] = $staff_member->email . ' (staff)';
                    } else {
                        $failed_to[] = $staff_member->email . ' (staff)';
                    }

                    $this->db->insert(db_prefix() . 'expiry_notification_logs', [
                        'domain_id'         => $item['domain_id'],
                        'customer_id'       => $item['client_id'],
                        'staff_id'          => $item['staff_id'],
                        'email_sent_to'     => $staff_member->email,
                        'days_before_expiry'=> $days_left,
                        'sent_at'           => $now,
                        'status'            => $email_sent ? 'success' : 'failed',
                    ]);
                }
            }
        }

        // Build final response message
        $msg = $updated . ' domain(s) saved.';
        if (!empty($sent_to)) {
            $msg .= ' Emails sent to: ' . implode(', ', array_unique($sent_to)) . '.';
        }
        if (!empty($failed_to)) {
            $msg .= ' Failed for: ' . implode(', ', array_unique($failed_to)) . '.';
        }
        if (empty($sent_to) && empty($failed_to)) {
            $msg .= ' No emails sent (no valid emails provided).';
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'updated' => $updated,
            'sent'    => count(array_unique($sent_to)),
            'failed'  => count(array_unique($failed_to)),
        ]);
        exit;
    }

    /**
     * Internal helper: send a single HTML email via PHPMailer (or CI fallback).
     * Returns true on success, false on failure.
     */
    private function _send_email_via_phpmailer(
        $phpmailer_found, $smtp_host, $smtp_port, $smtp_enc,
        $smtp_user, $smtp_pass, $from_email, $from_name,
        $to_email, $subject, $body
    ) {
        if ($phpmailer_found) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                $mail->SMTPSecure = (strtolower($smtp_enc) === 'ssl')
                    ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $smtp_port;
                $mail->setFrom($from_email, $from_name);
                $mail->addAddress($to_email);
                $mail->isHTML(true);
                $mail->Subject    = $subject;
                $mail->Body       = $body;
                $mail->send();
                return true;
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                return false;
            }
        } else {
            // CI email fallback
            $this->load->library('email');
            $this->email->initialize([
                'protocol'  => 'smtp',
                'smtp_host' => (strtolower($smtp_enc) === 'ssl') ? 'ssl://' . $smtp_host : $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_user' => $smtp_user,
                'smtp_pass' => $smtp_pass,
                'smtp_crypto'=> $smtp_enc,
                'mailtype'  => 'html',
                'charset'   => 'utf-8',
            ]);
            $this->email->clear();
            $this->email->from($from_email, $from_name);
            $this->email->to($to_email);
            $this->email->subject($subject);
            $this->email->message($body);
            return (bool)$this->email->send();
        }
    }


    /**
     * Send a test email to verify SMTP configuration.
     * Uses PHPMailer directly with saved Perfex SMTP settings.
     */
    public function send_test_email_ajax()
    {
        if (!is_admin()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $to_email = trim($this->input->post('email'));
        if (!$to_email || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
            exit;
        }

        $from_email = get_option('smtp_email');
        $from_name  = get_option('smtp_email_name') ?: (get_option('companyname') ?: 'Domain Manager');
        $company    = get_option('companyname') ?: 'Domain Manager';
        $smtp_host  = get_option('smtp_host');
        $smtp_port  = (int)(get_option('smtp_port') ?: 465);
        $smtp_enc   = get_option('smtp_encryption') ?: 'ssl';
        $smtp_user  = get_option('smtp_username');
        // smtp_password is stored AES-encrypted in the DB — must decrypt before use
        $smtp_pass  = $this->encryption->decrypt(get_option('smtp_password'));

        if (empty($from_email)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No SMTP sender email configured. Go to <strong>Setup &rarr; Email Settings</strong> and fill in SMTP details first.'
            ]);
            exit;
        }

        $subject = 'Test Email - ' . $company . ' Domain Manager';
        $body    = '<div style="font-family:Arial,sans-serif;max-width:520px;padding:24px;border:1px solid #e5e7eb;border-radius:8px;">'
                 . '<h2 style="color:#4f46e5;margin-top:0;">&#9989; SMTP Test Email</h2>'
                 . '<p>This is a test email sent from your <strong>' . html_escape($company) . '</strong> Domain Manager.</p>'
                 . '<p>If you received this email, your SMTP settings are configured correctly!</p>'
                 . '<hr style="border:none;border-top:1px solid #e5e7eb;">'
                 . '<p style="color:#6b7280;font-size:12px;">Sent at: ' . date('Y-m-d H:i:s') . '<br>'
                 . 'SMTP: ' . html_escape($smtp_host) . ':' . $smtp_port . ' (' . strtoupper($smtp_enc) . ')</p>'
                 . '</div>';

        $sent         = false;
        $error_detail = '';

        // Use PHPMailer directly (same as Perfex uses internally)
        $phpmailer_paths = [
            FCPATH . 'vendor/phpmailer/phpmailer/src/PHPMailer.php',
            APPPATH . '../vendor/phpmailer/phpmailer/src/PHPMailer.php',
        ];
        $phpmailer_found = false;
        foreach ($phpmailer_paths as $pm_path) {
            if (file_exists($pm_path)) {
                require_once $pm_path;
                require_once dirname($pm_path) . '/SMTP.php';
                require_once dirname($pm_path) . '/Exception.php';
                $phpmailer_found = true;
                break;
            }
        }

        if ($phpmailer_found) {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                $mail->SMTPSecure = (strtolower($smtp_enc) === 'ssl')
                    ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $smtp_port;
                $mail->setFrom($from_email, $from_name);
                $mail->addAddress($to_email);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
                $sent = true;
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                $error_detail = $e->getMessage();
            }
        } else {
            // Fallback: CodeIgniter email with explicit SSL config
            $this->load->library('email');
            $ci_config = [
                'protocol'    => 'smtp',
                'smtp_host'   => (strtolower($smtp_enc) === 'ssl') ? 'ssl://' . $smtp_host : $smtp_host,
                'smtp_port'   => $smtp_port,
                'smtp_user'   => $smtp_user,
                'smtp_pass'   => $smtp_pass,
                'smtp_crypto' => $smtp_enc,
                'mailtype'    => 'html',
                'charset'     => 'utf-8',
            ];
            $this->email->initialize($ci_config);
            $this->email->clear();
            $this->email->from($from_email, $from_name);
            $this->email->to($to_email);
            $this->email->subject($subject);
            $this->email->message($body);
            $sent = $this->email->send();
            if (!$sent) {
                $error_detail = strip_tags($this->email->print_debugger(['headers']));
            }
        }

        header('Content-Type: application/json');
        if ($sent) {
            echo json_encode([
                'success' => true,
                'message' => '&#9989; Test email sent successfully to <strong>' . html_escape($to_email) . '</strong>! Please check your inbox and spam folder.'
            ]);
        } else {
            // Extract clearest error line
            $key_error = '';
            foreach (array_filter(array_map('trim', explode("\n", $error_detail))) as $ln) {
                if (!empty($ln)) { $key_error = $ln; break; }
            }
            $msg  = 'Failed to send test email to <strong>' . html_escape($to_email) . '</strong>.<br>';
            if (!empty($key_error)) {
                $msg .= '<br><small style="color:#c0392b;"><strong>SMTP Error:</strong> ' . html_escape(substr($key_error, 0, 250)) . '</small>';
            }
            $msg .= '<br><br>&#128073; Go to <strong>Setup &rarr; Email Settings</strong> and verify your SMTP credentials.';
            echo json_encode(['success' => false, 'message' => $msg]);
        }
        exit;
    }

    public function debug_api($endpoint = 'domains')
    {
        if (!is_admin()) { show_404(); }
        header('Content-Type: application/json');
        $raw = ($endpoint === 'websites')
            ? $this->hostinger_api_model->get_websites()
            : $this->hostinger_api_model->get_domains();
        echo json_encode(['debug' => true, 'endpoint' => $endpoint, 'result' => $raw]);
        exit;
    }

    public function sync_hostinger_domains()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'create')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $result = $this->hostinger_api_model->sync_domains();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function sync_hostinger_websites()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'create')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $result = $this->hostinger_api_model->sync_websites();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function domain_manager_table()
    {
        if (!has_permission('domain_manager', '', 'view')) {
            access_denied('domain_manager');
        }
        $this->app->get_table_data(module_views_path('domain_manager_hostinger', 'tables/domain_manager'));
    }

    /**
     * AJAX endpoint to fetch domains data
     */
    public function get_domains_json()
    {
        if (!has_permission('domain_manager', '', 'view')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $type = $this->input->get('type'); // 'internal' or 'external'
        $domains = $this->domain_manager_model->get_portfolio($type);

        foreach ($domains as &$domain) {
            // Count mailboxes
            $domain['total_mailboxes'] = $this->db->where('domain', $domain['domain_name'])->where('deleted', 0)->count_all_results(db_prefix() . 'emails_manager');

            // Client email
            $client_email = !empty($domain['client_email']) ? $domain['client_email'] : '';
            if (empty($client_email) && !empty($domain['client_id'])) {
                $contact = $this->db->select('email')->where('userid', $domain['client_id'])->where('is_primary', 1)->get(db_prefix() . 'contacts')->row();
                if ($contact) {
                    $client_email = $contact->email;
                }
            }
            $domain['formatted_client_email'] = !empty($client_email) ? $client_email : '—';

            // Created Date
            $created_date = '—';
            if (!empty($domain['start_date']) && $domain['start_date'] !== '0000-00-00') {
                $created_date = _d($domain['start_date']);
            } elseif (!empty($domain['purchase_date']) && $domain['purchase_date'] !== '0000-00-00') {
                $created_date = _d($domain['purchase_date']);
            } elseif (!empty($domain['created_at']) && $domain['created_at'] !== '0000-00-00 00:00:00') {
                $created_date = _d(date('Y-m-d', strtotime($domain['created_at'])));
            }
            $domain['formatted_created_date'] = $created_date;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $domains]);
        exit;
    }

    // -----------------------------------------------------------------------
    // WEBSITES
    // -----------------------------------------------------------------------

    public function hosting_list()
    {
        if (!has_permission('domain_manager', '', 'hosting_view')) {
            access_denied('domain_manager');
        }
        $data['title'] = _l('domain_manager_websites_list');
        $data['websites_list'] = $this->hosting_details_model->get_all_with_relations();
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['domains'] = $this->domain_manager_model->get();
        
        // Count active websites
        $active_count = 0;
        foreach($data['websites_list'] as $w) {
            if(strtolower($w['status']) === 'active') {
                $active_count++;
            }
        }
        $data['active_websites'] = $active_count;

        // Websites expiring soon (next 5 days) — for bell-icon dropdown
        $data['websites_expiring_soon_list']  = $this->hosting_details_model->get_websites_expiring_soon(5);
        $data['websites_expiring_soon_count'] = count($data['websites_expiring_soon_list']);

        if ($this->input->is_ajax_request() && !$this->input->get('json')) {
            $this->app->get_table_data(module_views_path('domain_manager_hostinger', 'tables/hosting_details'));
            return;
        }
        $this->load->view('hosting/index', $data);
    }

    public function get_websites_json()
    {
        if (!has_permission('domain_manager', '', 'hosting_view')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $websites = $this->hosting_details_model->get_all_with_relations();
        foreach ($websites as &$w) {
            $w['total_mailboxes'] = 0;
            if (!empty($w['domain_name'])) {
                $w['total_mailboxes'] = $this->db->where('domain', $w['domain_name'])->where('deleted', 0)->count_all_results(db_prefix() . 'emails_manager');
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $websites]);
        exit;
    }

    public function hosting_create()
    {
        if (!has_permission('domain_manager', '', 'hosting_create')) {
            access_denied('domain_manager');
        }
        $data['title']   = _l('domain_manager_hosting_add');
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['domains'] = $this->domain_manager_model->get();
        $data['staff']   = $this->staff_model->get('', ['active' => 1]);
        $this->load->view('hosting/create', $data);
    }

    public function save_hosting()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_create')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['website_name'])) {
            set_alert('warning', 'Website name is required.');
            redirect(admin_url('domain_manager_hostinger/hosting_create'));
        }
        $website_name = trim($data['website_name']);

        // Check for duplicate website name
        $this->db->where('website_name', $website_name);
        $existing = $this->db->get(db_prefix() . 'hosting_details')->row();
        if ($existing) {
            set_alert('warning', 'Website "' . $website_name . '" already exists in the table.');
            redirect(admin_url('domain_manager_hostinger/hosting_create'));
        }

        $insert_data = [
            'website_name'    => $website_name,
            'domain_id'       => !empty($data['domain_id']) ? (int)$data['domain_id'] : null,
            'provider'        => $data['domain_manager_provider'] ?? null,
            'client_id'       => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'assigned_staff_id' => !empty($data['assigned_staff_id']) ? (int)$data['assigned_staff_id'] : null,
            'status'          => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'ssl_status'      => $data['ssl_status'] ?? 'active',
            'domain_status'   => $data['domain_status'] ?? 'active',
            'server_type'     => $data['server_type'] ?? 'Shared',
            'start_date'      => !empty($data['domain_manager_start_date']) ? to_sql_date($data['domain_manager_start_date']) : null,
            'expiration_date' => !empty($data['domain_manager_expiry_date']) ? to_sql_date($data['domain_manager_expiry_date']) : null,
            'description'     => $data['description'] ?? null,
            'created_by'      => get_staff_user_id(),
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        $id = $this->hosting_details_model->add($insert_data);
        
        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success' => $id ? true : false,
                'message' => $id ? 'Website added successfully.' : 'Failed to add website.'
            ]);
            die();
        }

        set_alert($id ? 'success' : 'danger', $id ? 'Website added successfully.' : 'Failed to add website.');
        redirect(admin_url('domain_manager_hostinger/hosting_list'));
    }

    public function bulk_action_hosting()
    {
        if (!has_permission('domain_manager', '', 'hosting_edit')) {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            die();
        }

        $action = $this->input->post('action');
        $ids = $this->input->post('ids');

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'No websites selected.']);
            die();
        }

        $success_count = 0;

        foreach ($ids as $id) {
            if ($action === 'delete') {
                if ($this->hosting_details_model->delete($id)) $success_count++;
            } elseif ($action === 'renew') {
                $hosting = $this->hosting_details_model->get($id);
                if ($hosting && $hosting->expiration_date) {
                    $new_date = date('Y-m-d', strtotime($hosting->expiration_date . ' + 1 year'));
                    if ($this->hosting_details_model->update($id, ['expiration_date' => $new_date])) $success_count++;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Successfully processed $success_count websites."
        ]);
        die();
    }

    public function view_hosting($id)
    {
        if (!has_permission('domain_manager', '', 'hosting_view')) {
            access_denied('domain_manager');
        }
        $data['hosting'] = $this->hosting_details_model->get($id);
        
        // Pass linked domain if it exists
        $data['domain'] = null;
        if ($data['hosting']->domain_id) {
            $data['domain'] = $this->domain_manager_model->get($data['hosting']->domain_id);
        }

        $this->load->helper('domain_manager_hostinger/domain_manager');
        
        $whois_domain = '';
        if ($data['domain']) {
            $whois_domain = $data['domain']->domain_name;
        } else {
            // Clean website_name to get domain
            $whois_domain = preg_replace('#^https?://(www\.)?#i', '', $data['hosting']->website_name);
            $whois_domain = explode('/', $whois_domain)[0];
        }

        $whois_raw = "No linked domain. WHOIS details are only available for linked domains.";
        $whois = null;
        if (!empty($whois_domain)) {
            $whois = domain_manager_get_whois_info($whois_domain);
            $whois_raw = $whois ? $whois['raw_text'] : "Unable to retrieve WHOIS information for {$whois_domain}.";
        }
        $data['whois_raw'] = $whois_raw;
        $data['domain_whois'] = $whois;
        
        $this->load->view('hosting/view', $data);
    }

    public function edit_hosting($id)
    {
        if (!has_permission('domain_manager', '', 'hosting_edit')) {
            access_denied('domain_manager');
        }
        $data['hosting'] = $this->hosting_details_model->get($id);
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['domains'] = $this->domain_manager_model->get();
        $data['staff']   = $this->staff_model->get('', ['active' => 1]);
        $this->load->view('hosting/edit', $data);
    }

    public function update_hosting()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_edit')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['id']) || empty($data['website_name'])) {
            set_alert('warning', 'Website name is required.');
            redirect(admin_url('domain_manager_hostinger/hosting_list'));
        }
        $update_data = [
            'website_name'    => trim($data['website_name']),
            'domain_id'       => !empty($data['domain_id']) ? (int)$data['domain_id'] : null,
            'provider'        => $data['domain_manager_provider'] ?? null,
            'client_id'       => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'assigned_staff_id' => !empty($data['assigned_staff_id']) ? (int)$data['assigned_staff_id'] : null,
            'status'          => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'start_date'      => !empty($data['domain_manager_start_date']) ? to_sql_date($data['domain_manager_start_date']) : null,
            'expiration_date' => !empty($data['domain_manager_expiry_date']) ? to_sql_date($data['domain_manager_expiry_date']) : null,
            'description'     => $data['description'] ?? null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        $r = $this->hosting_details_model->update($data['id'], $update_data);
        set_alert($r ? 'success' : 'danger', $r ? 'Website updated successfully.' : 'Failed to update website.');
        redirect(admin_url('domain_manager_hostinger/hosting_list'));
    }

    public function delete_hosting($id)
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_delete')) {
            access_denied('domain_manager');
        }
        if (!is_numeric($id)) {
            set_alert('danger', 'Invalid website ID.');
            redirect(admin_url('domain_manager_hostinger/hosting_list'));
        }
        $r = $this->hosting_details_model->delete($id);
        set_alert($r ? 'success' : 'danger', $r ? 'Website deleted.' : 'Failed to delete website.');
        redirect(admin_url('domain_manager_hostinger/hosting_list'));
    }

    // -----------------------------------------------------------------------
    // EMAILS
    // -----------------------------------------------------------------------

    /**
     * Alias for emails() to prevent 404 on legacy links
     */
    public function email_list()
    {
        $this->emails();
    }

    public function emails()
    {
        if (!has_permission('domain_manager', '', 'hosting_view')) {
            access_denied('domain_manager');
        }
        $data['title']   = _l('domain_manager_emails_list');
        $data['domains'] = $this->domain_manager_model->get();
        $this->load->view('emails/index', $data);
    }

    public function domain_email_edit($domain_id)
    {
        if (!has_permission('domain_manager', '', 'hosting_edit')) {
            access_denied('domain_manager');
        }
        
        $domain = $this->domain_manager_model->get($domain_id);
        if (!$domain) {
            show_404();
        }

        // Get the first mailbox for this domain
        $mailbox = $this->db->where('domain', $domain->domain_name)
                            ->where('deleted', 0)
                            ->order_by('id', 'asc')
                            ->get(db_prefix() . 'emails_manager')
                            ->row();

        if (!$mailbox) {
            // Auto-create a default primary mailbox: admin@domain_name
            $mailbox_name = 'admin@' . ltrim($domain->domain_name, '@');
            $insert_data = [
                'mailbox_name' => $mailbox_name,
                'domain'       => $domain->domain_name,
                'client_id'    => $domain->client_id,
                'client_email' => $domain->client_email,
                'status'       => 'active',
                'start_date'   => date('Y-m-d'),
                'created_by'   => get_staff_user_id(),
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
            $this->load->model('domain_manager_hostinger/email_manager_model');
            $mailbox_id = $this->email_manager_model->add($insert_data);
            domain_manager_sync_mailbox_count($domain->domain_name);
        } else {
            $mailbox_id = $mailbox->id;
        }

        redirect(admin_url('domain_manager_hostinger/email_edit/' . $mailbox_id));
    }

    public function domain_email_delete($domain_id)
    {
        if (!has_permission('domain_manager', '', 'hosting_delete')) {
            access_denied('domain_manager');
        }
        
        $domain = $this->domain_manager_model->get($domain_id);
        if ($domain) {
            // Soft delete all mailboxes for this domain
            $this->db->where('domain', $domain->domain_name);
            $this->db->update(db_prefix() . 'emails_manager', ['deleted' => 1]);
            domain_manager_sync_mailbox_count($domain->domain_name);
            set_alert('success', 'Domain mailboxes deleted successfully.');
        } else {
            set_alert('danger', 'Domain not found.');
        }

        redirect(admin_url('domain_manager_hostinger/emails'));
    }

    public function email_create()
    {
        if (!has_permission('domain_manager', '', 'hosting_create')) {
            access_denied('domain_manager');
        }
        $data['title']   = _l('domain_manager_email_add');
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['domains'] = $this->domain_manager_model->get();
        $this->load->view('emails/create', $data);
    }

    public function email_edit($id)
    {
        if (!has_permission('domain_manager', '', 'hosting_edit')) {
            access_denied('domain_manager');
        }
        $data['title']   = _l('domain_manager_email_edit');
        $data['email']   = $this->email_manager_model->get($id);
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['domains'] = $this->domain_manager_model->get();
        
        if (!$data['email']) {
            show_404();
        }
        
        // Fetch additional mailboxes for the same domain
        $this->db->select(db_prefix() . 'emails_manager.*, ' . db_prefix() . 'clients.company as client_name');
        $this->db->from(db_prefix() . 'emails_manager');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'emails_manager.client_id', 'left');
        $this->db->where('domain', $data['email']->domain);
        $this->db->where(db_prefix() . 'emails_manager.id !=', $id);
        $this->db->where('deleted', 0);
        $data['additional_mailboxes'] = $this->db->get()->result_array();
        
        $this->load->view('emails/edit', $data);
    }

    public function email_view($id)
    {
        if (!has_permission('domain_manager', '', 'hosting_view')) {
            access_denied('domain_manager');
        }
        $data['title'] = _l('domain_manager_email_view');
        $data['email'] = $this->email_manager_model->get($id);
        
        if (!$data['email']) {
            show_404();
        }
        
        $this->load->view('emails/view', $data);
    }

    public function save_email()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_create')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        
        $mailbox_names = $this->input->post('mailbox_names');
        if (empty($mailbox_names) || !is_array($mailbox_names)) {
            if (!empty($data['mailbox_name'])) {
                $mailbox_names = preg_split('/[\r\n,;]+/', $data['mailbox_name']);
            } else {
                set_alert('warning', 'Mailbox name is required.');
                redirect(admin_url('domain_manager_hostinger/email_create'));
            }
        }

        $inserted_count = 0;

        foreach ($mailbox_names as $mailbox) {
            $mailbox = trim($mailbox);
            if (empty($mailbox)) {
                continue;
            }

            $item_domain = !empty($data['domain']) ? trim($data['domain']) : '';

            // Append domain if not already present in the mailbox name
            if (strpos($mailbox, '@') === false && !empty($item_domain)) {
                $mailbox_domain = ltrim($item_domain, '@');
                $mailbox = $mailbox . '@' . $mailbox_domain;
            } elseif (empty($item_domain) && strpos($mailbox, '@') !== false) {
                $parts = explode('@', $mailbox);
                if (count($parts) > 1) {
                    $item_domain = trim($parts[1]);
                }
            }

            $exists = $this->db->where('domain', $item_domain)
                               ->where('mailbox_name', $mailbox)
                               ->where('deleted', 0)
                               ->get(db_prefix() . 'emails_manager')
                               ->row();
            if ($exists) {
                set_alert('warning', 'Mailbox ' . $mailbox . ' already exists.');
                continue;
            }

            $insert_data = [
                'mailbox_name'    => $mailbox,
                'domain'          => !empty($item_domain) ? $item_domain : null,
                'client_id'       => !empty($data['client_id']) ? (int)$data['client_id'] : null,
                'client_email'    => !empty($data['client_email']) ? trim($data['client_email']) : null,
                'available_count' => !empty($data['available_count']) ? (int)$data['available_count'] : null,
                'status'          => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
                'start_date'      => !empty($data['start_date']) ? to_sql_date($data['start_date']) : null,
                'expiry_date'     => !empty($data['expiry_date']) ? to_sql_date($data['expiry_date']) : null,
                'description'     => $data['description'] ?? null,
                'created_by'      => get_staff_user_id(),
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];
            
            if ($this->email_manager_model->add($insert_data)) {
                $inserted_count++;
                domain_manager_sync_mailbox_count($item_domain);
            }
        }

        if ($inserted_count > 0) {
            set_alert('success', $inserted_count . ' Email(s) added successfully.');
        } else {
            set_alert('danger', 'Failed to add emails.');
        }
        redirect(admin_url('domain_manager_hostinger/emails'));
    }

    public function update_email()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_edit')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['id']) || empty($data['mailbox_name'])) {
            set_alert('warning', 'Mailbox name is required.');
            redirect(admin_url('domain_manager_hostinger/emails'));
        }

        $update_data = [
            'mailbox_name'    => $data['mailbox_name'] ?? null,
            'domain'          => $data['domain'] ?? null,
            'client_id'       => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'client_email'    => !empty($data['client_email']) ? trim($data['client_email']) : null,
            'available_count' => !empty($data['available_count']) ? (int)$data['available_count'] : null,
            'status'          => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'start_date'      => !empty($data['start_date']) ? to_sql_date($data['start_date']) : null,
            'expiry_date'     => !empty($data['expiry_date']) ? to_sql_date($data['expiry_date']) : null,
            'description'     => $data['description'] ?? null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        $r = $this->email_manager_model->update($data['id'], $update_data);
        if ($r) {
            domain_manager_sync_mailbox_count($data['domain']);
        }

        // Add extra mailboxes if provided
        $mailbox_names = $this->input->post('mailbox_names');
        if (!empty($mailbox_names) && is_array($mailbox_names)) {
            $item_domain = strtolower(trim($data['domain']));
            foreach ($mailbox_names as $mailbox) {
                $mailbox = trim($mailbox);
                if (empty($mailbox)) {
                    continue;
                }
                if (strpos($mailbox, '@') === false && !empty($item_domain)) {
                    $mailbox = $mailbox . '@' . ltrim($item_domain, '@');
                }
                $exists = $this->db->where('mailbox_name', $mailbox)->where('deleted', 0)->get(db_prefix() . 'emails_manager')->row();
                if ($exists) {
                    continue;
                }
                $insert_data = [
                    'mailbox_name'    => $mailbox,
                    'domain'          => $item_domain,
                    'client_id'       => !empty($data['client_id']) ? (int)$data['client_id'] : null,
                    'client_email'    => !empty($data['client_email']) ? trim($data['client_email']) : null,
                    'status'          => 'active',
                    'start_date'      => date('Y-m-d'),
                    'created_by'      => get_staff_user_id(),
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
                if ($this->email_manager_model->add($insert_data)) {
                    domain_manager_sync_mailbox_count($item_domain);
                }
            }
        }

        set_alert($r ? 'success' : 'danger', $r ? 'Email updated successfully.' : 'Failed to update email.');
        redirect(admin_url('domain_manager_hostinger/emails'));
    }

    public function email_delete($id)
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_delete')) {
            access_denied('domain_manager');
        }
        if (!is_numeric($id)) {
            set_alert('danger', 'Invalid email ID.');
            redirect(admin_url('domain_manager_hostinger/emails'));
        }
        $email = $this->email_manager_model->get($id);
        $r = $this->email_manager_model->delete($id);
        if ($r && $email) {
            domain_manager_sync_mailbox_count($email->domain);
        }
        set_alert($r ? 'success' : 'danger', $r ? 'Email deleted.' : 'Failed to delete email.');
        
        $redirect = $this->input->get('redirect');
        if ($redirect == 'edit_domain') {
            redirect(admin_url('domain_manager_hostinger/edit/' . $this->input->get('domain_id')));
        }
        
        redirect(admin_url('domain_manager_hostinger/emails'));
    }

    /**
     * AJAX: Given a client_id, return the primary contact email(s) for that client.
     * Used to auto-populate the "Client Email" column in the Sync & Link panel.
     * GET: client_id=<int>
     */
    public function get_client_email_ajax()
    {
        if (!is_admin()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'contacts' => []]);
            exit;
        }

        $client_id = (int)$this->input->get('client_id');
        if ($client_id <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'contacts' => []]);
            exit;
        }

        // Fetch all active contacts for this client
        $contacts = $this->db
            ->select('email, firstname, lastname, is_primary')
            ->from(db_prefix() . 'contacts')
            ->where('userid', $client_id)
            ->where('active', 1)
            ->order_by('is_primary', 'DESC')
            ->get()->result_array();

        $all_contacts = [];
        foreach ($contacts as $c) {
            if (filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
                $all_contacts[] = [
                    'email'      => $c['email'],
                    'name'       => $c['firstname'] . ' ' . $c['lastname'],
                    'is_primary' => (int)$c['is_primary']
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'contacts' => $all_contacts,
        ]);
        exit;
    }

    public function add_mailbox_ajax()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_create')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }

        $mailbox = trim($this->input->post('mailbox_name'));
        $domain = trim($this->input->post('domain'));
        $status = $this->input->post('status');
        $client_id = $this->input->post('client_id');
        $client_email = trim($this->input->post('client_email'));

        if (empty($mailbox) || empty($domain)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Mailbox name and domain are required.']);
            exit;
        }

        if (strpos($mailbox, '@') === false) {
            $mailbox = $mailbox . '@' . ltrim($domain, '@');
        }

        // check if mailbox exists
        $exists = $this->db->where('mailbox_name', $mailbox)->where('deleted', 0)->get(db_prefix() . 'emails_manager')->row();
        if ($exists) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Mailbox already exists.']);
            exit;
        }

        $insert_data = [
            'mailbox_name'    => $mailbox,
            'domain'          => $domain,
            'client_id'       => !empty($client_id) ? (int)$client_id : null,
            'client_email'    => !empty($client_email) ? $client_email : null,
            'status'          => in_array($status, ['active', 'expired', 'suspended', 'pending']) ? $status : 'active',
            'start_date'      => date('Y-m-d'),
            'created_by'      => get_staff_user_id(),
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];

        $this->load->model('domain_manager_hostinger/email_manager_model');
        if ($this->email_manager_model->add($insert_data)) {
            domain_manager_sync_mailbox_count($domain);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Mailbox added successfully.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to add mailbox.']);
        }
        exit;
    }

    public function update_mailbox_ajax()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }

        $id = $this->input->post('id');
        $mailbox = trim($this->input->post('mailbox_name'));
        $domain = trim($this->input->post('domain'));
        $status = $this->input->post('status');
        $client_id = $this->input->post('client_id');
        $client_email = trim($this->input->post('client_email'));

        if (empty($id) || empty($mailbox) || empty($domain)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Required fields are missing.']);
            exit;
        }

        if (strpos($mailbox, '@') === false) {
            $mailbox = $mailbox . '@' . ltrim($domain, '@');
        }

        // check if duplicate
        $exists = $this->db->where('mailbox_name', $mailbox)->where('id !=', $id)->where('deleted', 0)->get(db_prefix() . 'emails_manager')->row();
        if ($exists) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Mailbox name already in use.']);
            exit;
        }

        $update_data = [
            'mailbox_name' => $mailbox,
            'domain'       => $domain,
            'client_id'    => !empty($client_id) ? (int)$client_id : null,
            'client_email' => !empty($client_email) ? $client_email : null,
            'status'       => in_array($status, ['active', 'expired', 'suspended', 'pending']) ? $status : 'active',
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        $this->load->model('domain_manager_hostinger/email_manager_model');
        if ($this->email_manager_model->update($id, $update_data)) {
            domain_manager_sync_mailbox_count($domain);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Mailbox updated successfully.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to update mailbox or no changes made.']);
        }
        exit;
    }

    public function delete_mailbox_ajax($id)
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_delete')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }

        $this->load->model('domain_manager_hostinger/email_manager_model');
        $email = $this->email_manager_model->get($id);
        if ($this->email_manager_model->delete($id)) {
            if ($email) {
                domain_manager_sync_mailbox_count($email->domain);
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Mailbox deleted successfully.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to delete mailbox.']);
        }
        exit;
    }

    public function delete_multiple_mailboxes_ajax()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_delete')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }

        $ids = $this->input->post('ids');
        if (empty($ids) || !is_array($ids)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No mailboxes selected.']);
            exit;
        }

        $this->load->model('domain_manager_hostinger/email_manager_model');
        $deleted_count = 0;
        $domains_to_sync = [];
        foreach ($ids as $id) {
            $email = $this->email_manager_model->get($id);
            if ($email) {
                $domains_to_sync[] = $email->domain;
            }
            if ($this->email_manager_model->delete($id)) {
                $deleted_count++;
            }
        }
        foreach (array_unique($domains_to_sync) as $dom) {
            domain_manager_sync_mailbox_count($dom);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $deleted_count . ' Mailbox(es) deleted successfully.']);
        exit;
    }

    public function get_mailboxes_table_html($domain, $primary_email_id)
    {
        $this->db->select(db_prefix() . 'emails_manager.*, ' . db_prefix() . 'clients.company as client_name');
        $this->db->from(db_prefix() . 'emails_manager');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'emails_manager.client_id', 'left');
        $this->db->where('domain', urldecode($domain));
        $this->db->where(db_prefix() . 'emails_manager.id !=', $primary_email_id);
        $this->db->where('deleted', 0);
        $mailboxes = $this->db->get()->result_array();

        $html = '';
        if (!empty($mailboxes)) {
            $total = count($mailboxes);
            $index = 0;
            foreach ($mailboxes as $mb) {
                $index++;
                $is_last = ($index === $total);
                $tree_char = $is_last ? '└── ' : '├── ';
                $status_class = ($mb['status'] == 'active') ? 'success' : (($mb['status'] == 'expired' || $mb['status'] == 'suspended') ? 'danger' : 'warning');
                $created_date = !empty($mb['created_at']) ? _d($mb['created_at']) : 'N/A';
                
                $html .= '<div class="mailbox-tree-item tw-flex tw-justify-between tw-items-center tw-py-2 hover:tw-bg-neutral-50 tw-px-2 tw-rounded" style="font-family: monospace; font-size: 14px; display: flex; justify-content: space-between; align-items: center; padding-top: 8px; padding-bottom: 8px;">';
                $html .= '<div class="tw-flex tw-items-center" style="display: flex; align-items: center;">';
                $html .= '<input type="checkbox" class="chk-mailbox" value="' . $mb['id'] . '" style="margin: 0 8px 0 0; cursor: pointer; transform: scale(1.1);">';
                $html .= '<span class="text-muted tw-mr-1" style="color: #64748b; margin-right: 4px;">' . $tree_char . '</span>';
                $html .= '<a href="mailto:' . html_escape($mb['mailbox_name']) . '" class="tw-text-neutral-700 hover:tw-underline" style="color: #334155; text-decoration: none;">' . html_escape($mb['mailbox_name']) . '</a>';
                $html .= '<span class="label label-' . $status_class . ' tw-ml-3" style="font-family: sans-serif; font-size: 10px; padding: 2px 6px; margin-left: 12px;">' . ucfirst(html_escape($mb['status'])) . '</span>';
                $html .= '</div>';
                
                $html .= '<div class="tw-flex tw-gap-2" style="display: flex; gap: 8px;">';
                
                $html .= '<button type="button" class="btn btn-default btn-xs view-mailbox-btn" ';
                $html .= 'data-mailbox-name="' . html_escape($mb['mailbox_name']) . '" ';
                $html .= 'data-domain="' . html_escape($mb['domain']) . '" ';
                $html .= 'data-client-name="' . html_escape($mb['client_name'] ?? 'No Client Linked') . '" ';
                $html .= 'data-client-email="' . html_escape($mb['client_email'] ?? '—') . '" ';
                $html .= 'data-created-at="' . $created_date . '" ';
                $html .= 'data-status="' . ucfirst(html_escape($mb['status'])) . '" ';
                $html .= '><i class="fa fa-eye"></i> View</button>';

                $html .= '<button type="button" class="btn btn-default btn-xs edit-mailbox-btn" ';
                $html .= 'data-id="' . $mb['id'] . '" ';
                $html .= 'data-mailbox-name="' . html_escape(explode('@', $mb['mailbox_name'])[0]) . '" ';
                $html .= 'data-status="' . html_escape($mb['status']) . '" ';
                $html .= 'data-client-id="' . html_escape($mb['client_id'] ?? '') . '" ';
                $html .= 'data-client-email="' . html_escape($mb['client_email'] ?? '') . '" ';
                $html .= '><i class="fa fa-pencil"></i> Edit</button>';

                $html .= '<button type="button" class="btn btn-danger btn-xs delete-mailbox-btn" ';
                $html .= 'data-id="' . $mb['id'] . '" ';
                $html .= '><i class="fa fa-remove"></i> Delete</button>';

                $html .= '</div>';
                $html .= '</div>';
            }
        } else {
            $html .= '<div class="text-muted tw-py-4">No additional mailboxes found.</div>';
        }
        echo $html;
        exit;
    }
}

