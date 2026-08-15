<?php
define('BASEPATH', true);
chdir('../../'); // Go to Perfex root

if (!file_exists('application/config/app-config.php')) {
    die("Database config file not found. Make sure you run this script from the module directory.");
}

require_once('application/config/app-config.php');

$conn = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

echo "TABLES:\n";
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        if (strpos($table, 'items') !== false || strpos($table, 'invoice') !== false || strpos($table, 'estimate') !== false || strpos($table, 'proposal') !== false || strpos($table, 'credit') !== false) {
            echo "- $table\n";
        }
    }
}
?>
