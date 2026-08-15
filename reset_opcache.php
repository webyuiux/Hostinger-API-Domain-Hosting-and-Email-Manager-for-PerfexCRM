<?php
if (function_exists('opcache_reset')) {
    $res = opcache_reset();
    echo "OPcache reset: " . ($res ? "SUCCESS" : "FAILED") . "\n";
} else {
    echo "OPcache function not found.\n";
}
?>
