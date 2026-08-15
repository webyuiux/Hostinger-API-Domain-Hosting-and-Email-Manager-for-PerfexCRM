<?php
$conn = new mysqli('localhost', 'root', '', 'perfex_new');
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

// Port 465 requires SSL encryption - fix the missing encryption setting
$conn->query("UPDATE tbloptions SET value='ssl' WHERE name='smtp_encryption'");
echo "smtp_encryption set to: ssl\n";

// Verify all settings
$keys = ['smtp_email','email_protocol','smtp_host','smtp_port','smtp_encryption','smtp_username'];
foreach ($keys as $k) {
    $r = $conn->query("SELECT value FROM tbloptions WHERE name='$k'");
    $v = ($r && $r->num_rows) ? $r->fetch_assoc()['value'] : '(NOT SET)';
    file_put_contents(__DIR__ . '/smtp_diag.txt', "$k=$v\n", FILE_APPEND);
    echo "$k = $v\n";
}
?>
