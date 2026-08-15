<?php
define('BASEPATH', true);
chdir('../../'); // navigate to root xampp/htdocs/perfex
require_once('index.php');

$CI = &get_instance();
$query = $CI->db->get(db_prefix() . 'staff');
echo "<pre>";
foreach ($query->result_array() as $row) {
    echo "ID: " . $row['staffid'] . " | Email: " . $row['email'] . " | Active: " . $row['active'] . "\n";
}
echo "</pre>";
