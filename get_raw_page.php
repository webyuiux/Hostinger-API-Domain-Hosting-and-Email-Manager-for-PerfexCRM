<?php
header('Content-Type: text/plain');
$html = file_get_contents('http://localhost/perfex/index.php/run_migration_and_sync');
echo htmlspecialchars($html);
?>
