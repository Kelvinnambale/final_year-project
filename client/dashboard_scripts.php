<?php
// This PHP file will output the entire script from the dashboard page
// This allows you to include the same scripts across multiple pages
echo file_get_contents('dashboard.php', false, null, strpos(file_get_contents('dashboard.php'), '<script>') + 8, strpos(file_get_contents('dashboard.php'), '</script>') - strpos(file_get_contents('dashboard.php'), '<script>') - 8);
?>