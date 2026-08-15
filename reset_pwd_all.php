<?php
define('BASEPATH', true);
require_once '../../application/config/app-config.php';
require_once '../../application/third_party/phpass.php';

$hasher = new PasswordHash(8, false);
$hash = $hasher->HashPassword('microsoft');

$mysqli = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$stmt = $mysqli->prepare("UPDATE tblstaff SET password = ?");
$stmt->bind_param("s", $hash);
$stmt->execute();

echo "Password successfully changed to 'microsoft' for all staff";

$stmt->close();
$mysqli->close();
?>
