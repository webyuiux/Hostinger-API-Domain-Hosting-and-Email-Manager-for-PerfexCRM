<?php
$conn = new mysqli('localhost', 'root', '', 'perfex_new');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h3>tblexpiry_notification_logs columns:</h3>";
$res = $conn->query("SHOW COLUMNS FROM tblexpiry_notification_logs");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
} else {
    echo "Table tblexpiry_notification_logs does not exist or error: " . $conn->error . "<br>";
}

echo "<h3>Updated Options:</h3>";
$res = $conn->query("SELECT name, value FROM tbloptions WHERE name LIKE 'domain_manager%'");
while ($row = $res->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h3>Updated Contact 1:</h3>";
$res = $conn->query("SELECT id, email, is_primary, active FROM tblcontacts WHERE id=1");
while ($row = $res->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h3>Domain ID 3:</h3>";
$res = $conn->query("SELECT id, domain_name, expiry_date, client_id FROM tbldomain_manager WHERE id=3");
while ($row = $res->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}
?>
