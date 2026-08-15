<?php
$conn = new mysqli('localhost', 'root', '', 'perfex_new');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h3>tblexpiry_notification_logs rows:</h3>";
$res = $conn->query("SELECT * FROM tblexpiry_notification_logs");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
} else {
    echo "Error querying logs: " . $conn->error;
}
?>
