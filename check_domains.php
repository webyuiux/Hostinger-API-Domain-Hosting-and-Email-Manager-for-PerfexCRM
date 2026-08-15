<?php
$conn = new mysqli('localhost', 'root', '', 'perfex_new');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h3>Domains in tbldomain_manager:</h3>";
$res = $conn->query("SELECT id, domain_name, expiry_date, client_id, status FROM tbldomain_manager");
while ($row = $res->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h3>Websites in tblhosting_details:</h3>";
$res = $conn->query("SELECT id, website_name, expiration_date, client_id, status FROM tblhosting_details");
while ($row = $res->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h3>Active Clients:</h3>";
$res = $conn->query("SELECT userid, company, active FROM tblclients");
while ($row = $res->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h3>Contacts for Clients:</h3>";
$res = $conn->query("SELECT id, userid, email, is_primary, active, firstname, lastname FROM tblcontacts");
while ($row = $res->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h3>Settings:</h3>";
$res = $conn->query("SELECT name, value FROM tbloptions WHERE name LIKE 'domain_manager%'");
while ($row = $res->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}
?>
