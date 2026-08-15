<?php
// Path to write the controller
$controller_path = '../../application/controllers/Run_migration.php';

$code = '<?php
defined("BASEPATH") or exit("No direct script access allowed");

class Run_migration extends App_Controller {
    public function index() {
        echo "<h3>Running Domain Expiry Notification System Migration & Test Setup...</h3>";
        
        // 1. Run database installer
        $install_file = FCPATH . "modules/domain_manager_hostinger/install.php";
        if (file_exists($install_file)) {
            require_once($install_file);
            echo "install.php executed.<br>";
        } else {
            echo "install.php not found at: " . htmlspecialchars($install_file) . "<br>";
        }
        
        // 2. Setup testing contact in database
        $this->db->where("id", 1);
        $this->db->update(db_prefix() . "contacts", [
            "email" => "sakshidalvi688@gmail.com",
            "is_primary" => 1,
            "active" => 1
        ]);
        echo "Contact ID 1 updated to sakshidalvi688@gmail.com (is_primary=1).<br>";
        
        // 3. Setup testing settings in options
        update_option("domain_manager_notification_emails", "sakshidalvi688@gmail.com");
        update_option("domain_manager_notify_recipients", "Customer + Assigned Staff + Additional Emails");
        update_option("domain_manager_notify_days", "30,15,7,3,1,0");
        update_option("domain_manager_last_cron_sync", 0);
        echo "Options updated (domain_manager_notification_emails, domain_manager_notify_recipients, domain_manager_notify_days, domain_manager_last_cron_sync).<br>";
        
        // 4. Ensure there is an expiring domain for client 1
        // Let\'s check domain with ID 3 (leads.com, expiry 2026-07-10) and set its expiry to match one of the warning days
        // Today is 2026-06-22. Expiry on 2026-07-10 is 18 days left. Let\'s set it to 15 days left (2026-07-07)
        $this->db->where("id", 3);
        $this->db->update(db_prefix() . "domain_manager", [
            "expiry_date" => "2026-07-07",
            "client_id" => 1
        ]);
        echo "Domain ID 3 expiry date set to 2026-07-07 (exactly 15 days from today, 2026-06-22).<br>";
        
        // 5. Clear notification logs for today to ensure we can test repeatedly
        $this->db->empty_table(db_prefix() . "expiry_notification_logs");
        echo "Cleared tblexpiry_notification_logs.<br>";
        
        echo "<h4>Migration and Test Setup Successful!</h4>";
    }
}
';

if (file_put_contents($controller_path, $code)) {
    echo "Successfully created Run_migration controller at " . htmlspecialchars($controller_path);
} else {
    echo "Failed to write Run_migration controller.";
}
?>
