<?php
$controller_path = '../../application/controllers/Test_sync.php';

$code = '<?php
defined("BASEPATH") or exit("No direct script access allowed");

class Test_sync extends App_Controller {
    public function index() {
        echo "<h3>Running domain_manager_automated_sync()...</h3>";
        
        // 1. Load the module main file if not autoloaded
        if (!function_exists("domain_manager_automated_sync")) {
            require_once(FCPATH . "modules/domain_manager_hostinger/domain_manager_hostinger.php");
        }
        
        // 2. Call the function
        domain_manager_automated_sync();
        echo "Sync method finished.<br><br>";
        
        // 3. Query the logs to see what was sent/logged
        $logs = $this->db->get(db_prefix() . "expiry_notification_logs")->result_array();
        echo "<h3>Logs in tblexpiry_notification_logs:</h3>";
        if (empty($logs)) {
            echo "No logs found.<br>";
        } else {
            foreach ($logs as $log) {
                echo "<pre>" . print_r($log, true) . "</pre>";
            }
        }
    }
}
';

if (file_put_contents($controller_path, $code)) {
    echo "Successfully created Test_sync controller.";
} else {
    echo "Failed to write Test_sync controller.";
}
?>
