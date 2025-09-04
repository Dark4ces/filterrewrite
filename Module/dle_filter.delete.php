<?php
/*
=====================================================
 DLE Filter - Uninstaller for DLE 18.1
=====================================================
*/

if (!defined('DATALIFEENGINE')) {
    die("Hacking attempt!");
}

// Module info
$module_name = "dle_filter";

try {
    // Remove admin section entries
    $db->query("DELETE FROM `" . PREFIX . "_admin_sections` WHERE name='{$module_name}'");
    $db->query("DELETE FROM `" . PREFIX . "_admin_sections` WHERE name='field_search'");

    // Drop module tables
    $db->query("DROP TABLE IF EXISTS `" . PREFIX . "_dle_filter_statistics`");
    $db->query("DROP TABLE IF EXISTS `" . PREFIX . "_dle_filter_news`");
    $db->query("DROP TABLE IF EXISTS `" . PREFIX . "_dle_filter_news_temp`");

    echo "✅ DLE Filter module successfully uninstalled for DLE 18.1! Note: Ensure no residual cache or template files remain in DLE 18's optimized template system.";
} catch (Exception $e) {
    die("Uninstallation failed: " . $e->getMessage());
}
?>
