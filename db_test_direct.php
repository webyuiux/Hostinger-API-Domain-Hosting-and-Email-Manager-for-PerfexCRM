<?php
$conn = new mysqli('localhost', 'root', '', 'perfex');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function descTable($conn, $table) {
    echo "\nCOLUMNS FOR $table:\n";
    $result = $conn->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ") Null:" . $row['Null'] . " Key:" . $row['Key'] . "\n";
    }
}

descTable($conn, 'tblitems');
descTable($conn, 'tblitemable');
?>
