<?php
header('Content-Type: text/plain');
$conn = new mysqli('localhost', 'root', '', 'perfex_new');
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

$keys = ['smtp_email','smtp_email_name','email_protocol','smtp_host','smtp_port','smtp_encryption','smtp_username','companyname'];
echo "=== PERFEX SMTP SETTINGS ===\n";
foreach ($keys as $k) {
    $r = $conn->query("SELECT value FROM tbloptions WHERE name='$k'");
    $v = ($r && $r->num_rows) ? $r->fetch_assoc()['value'] : '(NOT SET)';
    echo str_pad($k, 22) . " = " . $v . "\n";
}

echo "\n=== SMTP TCP CONNECTION TEST ===\n";
$host_r = $conn->query("SELECT value FROM tbloptions WHERE name='smtp_host'");
$port_r = $conn->query("SELECT value FROM tbloptions WHERE name='smtp_port'");
$enc_r  = $conn->query("SELECT value FROM tbloptions WHERE name='smtp_encryption'");
$h = ($host_r && $host_r->num_rows) ? $host_r->fetch_assoc()['value'] : '';
$p = ($port_r && $port_r->num_rows) ? (int)$port_r->fetch_assoc()['value'] : 587;
$e = ($enc_r  && $enc_r->num_rows)  ? $enc_r->fetch_assoc()['value']  : '';

if (empty($h)) {
    echo "RESULT: SMTP Host is NOT configured!\n";
    echo "ACTION: Go to Setup > Email Settings in Perfex and fill in SMTP details.\n";
} else {
    $prefix = ($e === 'ssl') ? 'ssl://' : '';
    echo "Testing: {$prefix}{$h}:{$p}\n";
    $fp = @fsockopen($prefix . $h, $p, $errno, $errstr, 5);
    if ($fp) {
        fclose($fp);
        echo "RESULT: SUCCESS - TCP connection to SMTP server works!\n";
        echo "NOTE: If emails still fail, username/password or encryption may be wrong.\n";
    } else {
        echo "RESULT: FAILED - Cannot reach SMTP server.\n";
        echo "ERROR:  [{$errno}] {$errstr}\n";
        echo "ACTION: Check SMTP host/port, or use Gmail smtp.gmail.com:587 (TLS)\n";
    }
}
?>
