<?php
$conn = new mysqli('localhost', 'root', '', 'perfex_new');
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

$result = [];
$keys = ['smtp_email','smtp_email_name','email_protocol','smtp_host','smtp_port','smtp_encryption','smtp_username','companyname'];
foreach ($keys as $k) {
    $r = $conn->query("SELECT value FROM tbloptions WHERE name='$k'");
    $result[$k] = ($r && $r->num_rows) ? $r->fetch_assoc()['value'] : '(NOT SET)';
}

$host = $result['smtp_host'];
$port = (int)($result['smtp_port'] ?: 587);
$enc  = $result['smtp_encryption'];

$conn_result = 'NOT TESTED';
$conn_error  = '';
if (!empty($host) && $host !== '(NOT SET)') {
    $prefix = ($enc === 'ssl') ? 'ssl://' : '';
    $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 5);
    if ($fp) { fclose($fp); $conn_result = 'SUCCESS'; }
    else { $conn_result = 'FAILED'; $conn_error = "[$errno] $errstr"; }
} else {
    $conn_result = 'HOST NOT SET';
}

$output = "SMTP_EMAIL=" . $result['smtp_email'] . "\n"
        . "EMAIL_PROTOCOL=" . $result['email_protocol'] . "\n"
        . "SMTP_HOST=" . $result['smtp_host'] . "\n"
        . "SMTP_PORT=" . $result['smtp_port'] . "\n"
        . "SMTP_ENC=" . $result['smtp_encryption'] . "\n"
        . "SMTP_USER=" . $result['smtp_username'] . "\n"
        . "COMPANY=" . $result['companyname'] . "\n"
        . "TCP_TEST=" . $conn_result . "\n"
        . "TCP_ERROR=" . $conn_error . "\n";

file_put_contents(__DIR__ . '/smtp_diag.txt', $output);
echo "Written to smtp_diag.txt";
?>
