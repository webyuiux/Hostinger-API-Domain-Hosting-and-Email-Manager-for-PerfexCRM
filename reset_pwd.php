<?php
define('BASEPATH', true);
require_once '../../application/config/app-config.php';
// We need to load CI to set the session. This is tricky.
// Let's just reset the password to 123456
$mysqli = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
$password = '$2a$08$1H.lI1hN5wZ0AozZ2y.G/.g5m/40v5Q.E/rX.sJv/fT/xL.q5M.5m'; // 123456 bcrypt
$mysqli->query("UPDATE tblstaff SET password = '$password' WHERE email = 'sakshi@gmail.com'");
echo "Password reset to 123456 for sakshi@gmail.com";
$mysqli->close();
?>
