<?php
define('BASEPATH', true);
chdir(dirname(dirname(dirname(__FILE__))));
require_once('index.php');

$CI = &get_instance();
echo "<h3>Direct Query test of tblexpiry_notification_logs:</h3>";

$logs = $CI->db->where('sent_at >=', date('Y-m-d 00:00:00'))
    ->get(db_prefix() . 'expiry_notification_logs')
    ->result_array();

echo "Logs count today: " . count($logs) . "<br><br>";

echo "<pre>";
foreach ($logs as $log) {
    print_r($log);
}
echo "</pre>";
?>
