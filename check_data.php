<?php
$conn = new mysqli('localhost', 'root', '', 'perfex_new');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Clear any existing variations for item 4
$conn->query("DELETE FROM tblitem_variations WHERE item_id = 4");

// Insert a variation for Polo T Shirt (id = 4) - color (attribute_id = 3) -> red (variation_id = 4)
$res = $conn->query("INSERT INTO tblitem_variations (item_id, attribute_id, variation_id, price, sku, stock) VALUES (4, 3, 4, 120.00, 'POLO-RED', 10)");
if ($res) {
    echo "Inserted variation successfully!\n";
} else {
    echo "Insert failed: " . $conn->error . "\n";
}

// Let's run the datatable query to see if it retrieves attributes and variations!
$query = "SELECT 
    i.id,
    i.description,
    COALESCE((SELECT GROUP_CONCAT(DISTINCT tblitems_attributes.name SEPARATOR ', ') FROM tblitem_variations JOIN tblitems_attributes ON tblitems_attributes.id = tblitem_variations.attribute_id WHERE item_id = i.id), i.attribute) as attributes,
    COALESCE((SELECT GROUP_CONCAT(DISTINCT tblitems_attribute_values.value SEPARATOR ', ') FROM tblitem_variations JOIN tblitems_attribute_values ON tblitems_attribute_values.id = tblitem_variations.variation_id WHERE item_id = i.id), i.variation) as variations
FROM tblitems i WHERE i.id = 4";

$res = $conn->query($query);
if ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
