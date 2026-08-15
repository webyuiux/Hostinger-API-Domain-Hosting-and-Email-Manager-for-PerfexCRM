<?php
define('BASEPATH', true);
require_once '../../application/config/app-config.php';
$mysqli = new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get MAX lead_id
$result = $mysqli->query("SELECT MAX(lead_id) as max_id FROM tbllead_intelligence");
if($result && $row = $result->fetch_assoc()) {
    $max_id = $row['max_id'];
    if ($max_id) {
        $mysqli->query("UPDATE tbllead_intelligence SET research_status = 'pending', last_researched_at = NULL WHERE lead_id = " . intval($max_id));
        echo "Updated lead_id: " . $max_id;
    } else {
        echo "No leads found in tbllead_intelligence.";
    }
} else {
    echo "Error finding max lead_id or table does not exist.";
}

$mysqli->close();
?>
