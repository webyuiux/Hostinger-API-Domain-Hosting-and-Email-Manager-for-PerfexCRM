<?php
$controller_path = '../../application/controllers/Run_migration_and_sync.php';

$code = '<?php
defined("BASEPATH") or exit("No direct script access allowed");

class Run_migration_and_sync extends App_Controller {
    public function index() {
        echo "<div style=\"font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto;\">";
        echo "<h2 style=\"color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;\">Domain Expiry Notification System</h2>";
        
        // 1. Run database installer/migration
        $install_file = FCPATH . "modules/domain_manager_hostinger/install.php";
        if (file_exists($install_file)) {
            require_once($install_file);
            echo "<p style=\"color: #10b981; font-weight: bold;\">✓ Database schema verified/updated.</p>";
        } else {
            echo "<p style=\"color: #ef4444; font-weight: bold;\">✗ Database installer file not found.</p>";
        }
        
        // 2. Clear sync timer cache so it executes immediately
        update_option("domain_manager_last_cron_sync", 0);
        
        // 3. Trigger the automated sync
        if (!function_exists("domain_manager_automated_sync")) {
            require_once(FCPATH . "modules/domain_manager_hostinger/domain_manager_hostinger.php");
        }
        
        echo "<p style=\"color: #3b82f6;\">Running automated domain expiry sync pipeline...</p>";
        domain_manager_automated_sync();
        echo "<p style=\"color: #10b981; font-weight: bold;\">✓ Sync pipeline executed successfully.</p>";
        
        // 4. Retrieve and display today\'s logs
        $logs = $this->db->where("sent_at >=", date("Y-m-d 00:00:00"))
            ->get(db_prefix() . "expiry_notification_logs")
            ->result_array();
            
        echo "<h4>Query Debugging:</h4>";
        echo "SQL: " . htmlspecialchars($this->db->last_query()) . "<br>";
        $err = $this->db->error();
        echo "Error code: " . $err["code"] . ", Message: " . htmlspecialchars($err["message"]) . "<br>";
        echo "Logs count: " . count($logs) . "<br>";
            
        echo "<h3 style=\"color: #334155; margin-top: 30px;\">Sent Notifications Log (Today: " . date("Y-m-d") . ")</h3>";
        if (empty($logs)) {
            echo "<div style=\"background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; color: #64748b;\">";
            echo "No notification emails were sent today. (This could mean no active domains/websites are expiring exactly 30, 15, 7, 3, 1, or 0 days from today, or duplicate notifications for today were prevented).";
            echo "</div>";
        } else {
            echo "<table style=\"width:100%; border-collapse:collapse; margin-top:15px;\">";
            echo "<thead>";
            echo "<tr style=\"background:#f1f5f9; text-align:left; border-bottom:2px solid #cbd5e1;\">";
            echo "<th style=\"padding:10px;\">Log ID</th>";
            echo "<th style=\"padding:10px;\">Domain/Website ID</th>";
            echo "<th style=\"padding:10px;\">Recipient Email</th>";
            echo "<th style=\"padding:10px;\">Alert Interval</th>";
            echo "<th style=\"padding:10px;\">Sent At</th>";
            echo "<th style=\"padding:10px;\">Status</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            foreach ($logs as $log) {
                $status_bg = $log["status"] === "success" ? "#d1fae5" : "#fee2e2";
                $status_fg = $log["status"] === "success" ? "#065f46" : "#991b1b";
                
                // Try to resolve domain/website name
                $asset_name = "ID: " . $log["domain_id"];
                $is_website = isset($log["asset_type"]) && $log["asset_type"] === "website";
                if ($is_website) {
                    $web_query = $this->db->select("website_name")->where("id", $log["domain_id"])->get(db_prefix() . "hosting_details")->row();
                    if ($web_query) {
                        $asset_name = htmlspecialchars($web_query->website_name);
                    }
                } else {
                    $domain_query = $this->db->select("domain_name")->where("id", $log["domain_id"])->get(db_prefix() . "domain_manager")->row();
                    if ($domain_query) {
                        $asset_name = htmlspecialchars($domain_query->domain_name);
                    }
                }
                
                echo "<tr style=\"border-bottom:1px solid #e2e8f0;\">";
                echo "<td style=\"padding:10px;\">{$log["id"]}</td>";
                echo "<td style=\"padding:10px;\">{$asset_name}</td>";
                echo "<td style=\"padding:10px;\">" . htmlspecialchars($log["email_sent_to"]) . "</td>";
                echo "<td style=\"padding:10px;\">{$log["days_before_expiry"]} days left</td>";
                echo "<td style=\"padding:10px;\">{$log["sent_at"]}</td>";
                echo "<td style=\"padding:10px;\"><span style=\"background:{$status_bg}; color:{$status_fg}; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px;\">" . strtoupper($log["status"]) . "</span></td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        }
        
        echo "<div style=\"margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 13px; color: #94a3b8;\">";
        echo "Domain Manager Hostinger Module Expiry Alert Diagnostic Utility";
        echo "</div>";
        echo "</div>";
    }
}
';

if (file_put_contents($controller_path, $code)) {
    echo "Successfully created Run_migration_and_sync controller.";
} else {
    echo "Failed to write Run_migration_and_sync controller.";
}
?>
