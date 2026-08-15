<?php
// Quick diagnostic: check what settings_model->update expects
$conn = new mysqli('localhost', 'root', '', 'perfex_new');
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

echo "=== Current Domain Manager Settings in DB ===\n";
$keys = [
    'domain_manager_hostinger_api_token',
    'domain_manager_notify_recipients',
    'domain_manager_notify_specific_staff',
    'domain_manager_notification_emails',
    'domain_manager_notify_days',
];
foreach ($keys as $k) {
    $r = $conn->query("SELECT * FROM tbloptions WHERE name='$k'");
    $row = $r ? $r->fetch_assoc() : null;
    if ($row) {
        $v = strlen($row['value']) > 50 ? substr($row['value'],0,50).'...' : $row['value'];
        echo "$k = $v\n";
    } else {
        echo "$k = (NOT IN DB - will need INSERT not UPDATE)\n";
    }
}

echo "\n=== Checking tbloptions structure ===\n";
$r = $conn->query("DESCRIBE tbloptions");
while ($row = $r->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " " . $row['Key'] . "\n";
}

echo "\n=== Simulating settings_model->update for domain_manager_notify_days ===\n";
// Perfex settings_model->update does: UPDATE tbloptions SET value=X WHERE name=Y
// If the row doesn't exist it returns 0 rows affected = false
$test_key = 'domain_manager_notify_days';
$test_val = '30,15,7,3,1,0';
$check = $conn->query("SELECT id FROM tbloptions WHERE name='$test_key'");
if ($check && $check->num_rows > 0) {
    $upd = $conn->query("UPDATE tbloptions SET value='$test_val' WHERE name='$test_key'");
    echo "$test_key: UPDATE - affected_rows=" . $conn->affected_rows . "\n";
} else {
    echo "$test_key: ROW DOES NOT EXIST - INSERT needed!\n";
    $ins = $conn->query("INSERT INTO tbloptions (name, value) VALUES ('$test_key', '$test_val')");
    echo "INSERT result: " . ($ins ? 'OK' : $conn->error) . "\n";
}
?>
