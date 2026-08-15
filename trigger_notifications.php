<?php
define('BASEPATH', true);
// Change directory to root to allow CodeIgniter to resolve system paths correctly
chdir(dirname(dirname(dirname(__FILE__))));
// Load CodeIgniter bootstrapper
require_once('index.php');

$CI = &get_instance();
if (!is_admin()) {
    access_denied('domain_manager');
}

echo "<h3>Triggering Expiry Notifications Diagnostic</h3>";

$CI->load->model('domain_manager_hostinger/domain_manager_model');
$CI->load->model('domain_manager_hostinger/hosting_details_model');

$notify_recipients = get_option('domain_manager_notify_recipients');
if (empty($notify_recipients)) {
    $notify_recipients = "Customer's Contact Email + Staff Assigned to Customer";
}

$specific_staff_ids_raw = get_option('domain_manager_notify_specific_staff');
$specific_staff_ids = !empty($specific_staff_ids_raw) ? array_filter(array_map('intval', explode(',', $specific_staff_ids_raw))) : [];

$additional_emails_raw = get_option('domain_manager_notification_emails');
$additional_emails = !empty($additional_emails_raw) ? array_filter(array_map('trim', explode(',', $additional_emails_raw))) : [];

echo "<strong>Settings Configured:</strong><br>";
echo "Recipients Rule: " . html_escape($notify_recipients) . "<br>";
echo "Specific Staff IDs: " . json_encode($specific_staff_ids) . "<br>";
echo "Additional Emails: " . json_encode($additional_emails) . "<br><br>";

$notify_days_raw = get_option('domain_manager_notify_days');
if (empty($notify_days_raw)) {
    // Use a sensible default — do NOT persist it to avoid overwriting user settings
    $notify_days_raw = '30,15,7,3,1,0';
}
$notify_days = array_map('intval', explode(',', $notify_days_raw));
echo "Days before expiry alerts configuration: " . json_encode($notify_days) . "<br><br>";

$force_test = (isset($_GET['force']) && $_GET['force'] === 'true');
if ($force_test) {
    echo "<div style='background:#DEF7EC;color:#03543F;padding:12px;margin-bottom:15px;border-radius:4px;'><strong>Force Test Mode Enabled:</strong> Bypassing date filter and duplicate checks to send test alerts immediately.</div>";
}

$domains = $CI->domain_manager_model->get();
$websites = $CI->hosting_details_model->get();

$expiring_assets = [];
$today = new DateTime('midnight');

foreach ($domains as $d) {
    if (!empty($d['expiry_date']) && $d['expiry_date'] !== '0000-00-00') {
        $exp_date = new DateTime($d['expiry_date']);
        $diff = $today->diff($exp_date);
        if ($force_test || ($diff->invert === 0 && in_array($diff->days, $notify_days))) {
            $expiring_assets[] = [
                'id' => $d['id'],
                'type' => 'Domain',
                'name' => $d['domain_name'],
                'expiry' => $d['expiry_date'],
                'days' => $force_test ? 25 : $diff->days,
                'client_id' => !empty($d['client_id']) ? (int)$d['client_id'] : 0,
                'custom_email' => $d['custom_email'] ?? '',
                'assigned_staff_id' => $d['assigned_staff_id'] ?? ''
            ];
        }
    }
}

foreach ($websites as $w) {
    if (!empty($w['expiration_date']) && $w['expiration_date'] !== '0000-00-00') {
        $exp_date = new DateTime($w['expiration_date']);
        $diff = $today->diff($exp_date);
        if ($force_test || ($diff->invert === 0 && in_array($diff->days, $notify_days))) {
            $assigned_staff_id = !empty($w['assigned_staff_id']) ? (int)$w['assigned_staff_id'] : '';
            if (empty($assigned_staff_id) && !empty($w['domain_id'])) {
                $dom = $CI->db->select('assigned_staff_id')->where('id', $w['domain_id'])->get(db_prefix() . 'domain_manager')->row();
                if ($dom) {
                    $assigned_staff_id = !empty($dom->assigned_staff_id) ? (int)$dom->assigned_staff_id : '';
                }
            }
            $expiring_assets[] = [
                'id' => $w['id'],
                'type' => 'Website',
                'name' => $w['website_name'],
                'expiry' => $w['expiration_date'],
                'days' => $force_test ? 25 : $diff->days,
                'client_id' => !empty($w['client_id']) ? (int)$w['client_id'] : 0,
                'custom_email' => '',
                'assigned_staff_id' => $assigned_staff_id
            ];
        }
    }
}

if (empty($expiring_assets) && $force_test) {
    $first_client = $CI->db->select('userid')->from(db_prefix() . 'clients')->where('active', 1)->get()->row();
    $client_id = $first_client ? (int)$first_client->userid : 0;
    $expiring_assets[] = [
        'id' => 9999,
        'type' => 'Domain',
        'name' => 'test-domain.com',
        'expiry' => date('Y-m-d', strtotime('+25 days')),
        'days' => 25,
        'client_id' => $client_id
    ];
}

if (empty($expiring_assets)) {
    echo "<strong>No expiring domains or websites found on the configured days: " . implode(', ', $notify_days) . " days left from today.</strong><br>";
} else {
    echo "<strong>Expiring assets found:</strong><br>";
    
    $CI->load->config('email');
    $CI->load->library('email');
    
    foreach ($expiring_assets as $asset) {
        $client_id = $asset['client_id'];
        $domain_id = $asset['id'];
        $domain_name = $asset['name'];
        $expiry_date = $asset['expiry'];
        $days_left = $asset['days'];
        
        echo "<hr><strong>Asset [{$asset['type']}] Name: {$domain_name}, Expiry: {$expiry_date} ({$days_left} days left)</strong><br>";
        
        $customer_name = 'Valued Customer';
        $client_valid  = false;
        if ($client_id > 0) {
            $client = $CI->db->select('company, active')
                ->from(db_prefix() . 'clients')
                ->where('userid', $client_id)
                ->get()->row();
            if (!$client || (int)$client->active !== 1) {
                // Client inactive: skip customer-facing emails but still allow
                // specific-staff and additional-email notifications to fire.
                echo "  -> Client ID {$client_id} is inactive. Skipping customer email; specific-staff/additional still active.<br>";
                $client_id    = 0;
                $client_valid = false;
            } else {
                $customer_name = $client->company;
                $client_valid  = true;
            }
        }
        
        $send_to_customer = false;
        $send_to_staff = false;
        // NOTE: additional_emails always fire regardless of routing rule.
        // The routing rule only controls customer + assigned-staff channels.
        
        if ($notify_recipients === 'Customer Only') {
            $send_to_customer = true;
        } elseif ($notify_recipients === 'Staff Only') {
            $send_to_staff = true;
        } elseif ($notify_recipients === "Customer's Contact Email + Staff Assigned to Customer") {
            $send_to_customer = true;
            $send_to_staff = true;
        } elseif ($notify_recipients === 'Customer + Assigned Staff + Additional Emails') {
            $send_to_customer = true;
            $send_to_staff = true;
        }
        
        // 1. Send Customer Email Template
        if ($send_to_customer) {
            $customer_emails = [];
            if ($client_id > 0) {
                $primary_contacts = $CI->db->select('email, firstname, lastname')
                    ->from(db_prefix() . 'contacts')
                    ->where('userid', $client_id)
                    ->where('is_primary', 1)
                    ->where('active', 1)
                    ->get()->result_array();
                foreach ($primary_contacts as $c) {
                    $customer_emails[] = ['email' => $c['email'], 'name' => $c['firstname'] . ' ' . $c['lastname']];
                }
            } elseif (!empty($asset['custom_email'])) {
                $customer_emails[] = ['email' => $asset['custom_email'], 'name' => 'Valued Customer'];
            }
                
            foreach ($customer_emails as $contact) {
                $email = $contact['email'];
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if (!$force_test) {
                        $already_sent = $CI->db->where('domain_id', $domain_id)
                            ->where('asset_type', strtolower($asset['type']))
                            ->where('email_sent_to', $email)
                            ->where('days_before_expiry', $days_left)
                            ->where('DATE(sent_at)', date('Y-m-d'))
                            ->get(db_prefix() . 'expiry_notification_logs')
                            ->row();
                        if ($already_sent) {
                            echo "  -> Skip duplicate: Already sent Customer alert to {$email} today.<br>";
                            continue;
                        }
                    }
                    
                    $contact_name = $contact['name'];
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
                    
                    echo "  -> Sending Customer Alert to {$email}: " . ($sent ? "<span style='color:green;'>[SUCCESS]</span>" : "<span style='color:red;'>[FAILED]</span>") . "<br>";
                    
                    $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                        'domain_id' => $domain_id,
                        'asset_type' => strtolower($asset['type']),
                        'customer_id' => $client_id,
                        'staff_id' => null,
                        'email_sent_to' => $email,
                        'days_before_expiry' => $days_left,
                        'sent_at' => date('Y-m-d H:i:s'),
                        'status' => $sent ? 'success' : 'failed'
                    ]);
                }
            }
        }
        
        // 2. Send Staff Email Template (assigned staff customer admins)
        if ($send_to_staff) {
            $staff_emails = [];
            $seen_staff = [];
            if ($client_id > 0) {
                $assigned_staff = $CI->db->select('staffid, email, firstname, lastname')
                    ->from(db_prefix() . 'customer_admins')
                    ->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'customer_admins.staff_id')
                    ->where('customer_id', $client_id)
                    ->where(db_prefix() . 'staff.active', 1)
                    ->get()->result_array();
                foreach ($assigned_staff as $s) {
                    if (!in_array($s['staffid'], $seen_staff)) {
                        $staff_emails[] = $s;
                        $seen_staff[] = $s['staffid'];
                    }
                }
            }
            if (!empty($asset['assigned_staff_id'])) {
                $assigned_staff = $CI->db->select('staffid, email, firstname, lastname')
                    ->from(db_prefix() . 'staff')
                    ->where('staffid', $asset['assigned_staff_id'])
                    ->where('active', 1)
                    ->get()->result_array();
                foreach ($assigned_staff as $s) {
                    if (!in_array($s['staffid'], $seen_staff)) {
                        $staff_emails[] = $s;
                        $seen_staff[] = $s['staffid'];
                    }
                }
            }
                
            foreach ($staff_emails as $staff) {
                $email = $staff['email'];
                $staff_id = $staff['staffid'];
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if (!$force_test) {
                        $already_sent = $CI->db->where('domain_id', $domain_id)
                            ->where('asset_type', strtolower($asset['type']))
                            ->where('email_sent_to', $email)
                            ->where('days_before_expiry', $days_left)
                            ->where('DATE(sent_at)', date('Y-m-d'))
                            ->get(db_prefix() . 'expiry_notification_logs')
                            ->row();
                        if ($already_sent) {
                            echo "  -> Skip duplicate: Already sent Staff alert to {$email} today.<br>";
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
                    
                    echo "  -> Sending Assigned Staff Alert to {$email} ({$staff['firstname']}): " . ($sent ? "<span style='color:green;'>[SUCCESS]</span>" : "<span style='color:red;'>[FAILED]</span>") . "<br>";
                    
                    $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                        'domain_id' => $domain_id,
                        'asset_type' => strtolower($asset['type']),
                        'customer_id' => $client_id,
                        'staff_id' => $staff_id,
                        'email_sent_to' => $email,
                        'days_before_expiry' => $days_left,
                        'sent_at' => date('Y-m-d H:i:s'),
                        'status' => $sent ? 'success' : 'failed'
                    ]);
                }
            }
        }
        
        // 3. Send Staff Email Template (specific staff to notify)
        if (!empty($specific_staff_ids)) {
            $staff_records = $CI->db->select('staffid, email, firstname, lastname')
                ->from(db_prefix() . 'staff')
                ->where_in('staffid', $specific_staff_ids)
                ->where('active', 1)
                ->get()->result_array();
                
            foreach ($staff_records as $staff) {
                $email = $staff['email'];
                $staff_id = $staff['staffid'];
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if (!$force_test) {
                        $already_sent = $CI->db->where('domain_id', $domain_id)
                            ->where('asset_type', strtolower($asset['type']))
                            ->where('email_sent_to', $email)
                            ->where('days_before_expiry', $days_left)
                            ->where('DATE(sent_at)', date('Y-m-d'))
                            ->get(db_prefix() . 'expiry_notification_logs')
                            ->row();
                        if ($already_sent) {
                            echo "  -> Skip duplicate: Already sent Specific Staff alert to {$email} today.<br>";
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
                    
                    echo "  -> Sending Specific Staff Alert to {$email} ({$staff['firstname']}): " . ($sent ? "<span style='color:green;'>[SUCCESS]</span>" : "<span style='color:red;'>[FAILED]</span>") . "<br>";
                    
                    $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                        'domain_id' => $domain_id,
                        'asset_type' => strtolower($asset['type']),
                        'customer_id' => $client_id,
                        'staff_id' => $staff_id,
                        'email_sent_to' => $email,
                        'days_before_expiry' => $days_left,
                        'sent_at' => date('Y-m-d H:i:s'),
                        'status' => $sent ? 'success' : 'failed'
                    ]);
                }
            }
        }
        
        // 4. Send Staff Email Template (additional emails)
        // 4. Additional email addresses (ALWAYS fires regardless of routing rule)
        //    These are "always notify" override addresses set in Settings.
        if (!empty($additional_emails)) {
            foreach ($additional_emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if (!$force_test) {
                        $already_sent = $CI->db->where('domain_id', $domain_id)
                            ->where('asset_type', strtolower($asset['type']))
                            ->where('email_sent_to', $email)
                            ->where('days_before_expiry', $days_left)
                            ->where('DATE(sent_at)', date('Y-m-d'))
                            ->get(db_prefix() . 'expiry_notification_logs')
                            ->row();
                        if ($already_sent) {
                            echo "  -> Skip duplicate: Already sent Additional alert to {$email} today.<br>";
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
                    
                    echo "  -> Sending Additional Alert to {$email}: " . ($sent ? "<span style='color:green;'>[SUCCESS]</span>" : "<span style='color:red;'>[FAILED]</span>") . "<br>";
                    
                    $CI->db->insert(db_prefix() . 'expiry_notification_logs', [
                        'domain_id' => $domain_id,
                        'asset_type' => strtolower($asset['type']),
                        'customer_id' => $client_id,
                        'staff_id' => null,
                        'email_sent_to' => $email,
                        'days_before_expiry' => $days_left,
                        'sent_at' => date('Y-m-d H:i:s'),
                        'status' => $sent ? 'success' : 'failed'
                    ]);
                }
            }
        }
    }
}
?>
