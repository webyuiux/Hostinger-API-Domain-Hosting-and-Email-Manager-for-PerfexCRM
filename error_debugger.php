<?php
$log_file = 'C:/Users/Sakshi/xampp/apache/logs/error.log';
if (file_exists($log_file)) {
    echo "<h3>Apache Error Log:</h3>";
    $lines = file($log_file);
    $last_lines = array_slice($lines, -50);
    echo "<pre>" . htmlspecialchars(implode("", $last_lines)) . "</pre>";
} else {
    echo "Log file not found at: " . $log_file;
}
?>
