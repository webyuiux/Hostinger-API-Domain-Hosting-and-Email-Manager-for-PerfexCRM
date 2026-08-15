<?php
define('BASEPATH', true);
require_once '../../application/config/app-config.php';
$mysqli = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
$result = $mysqli->query("SELECT email FROM tblstaff WHERE admin = 1 LIMIT 1");
if($row = $result->fetch_assoc()) {
    echo "Admin email: " . $row['email'];
}
$mysqli->close();
?>
