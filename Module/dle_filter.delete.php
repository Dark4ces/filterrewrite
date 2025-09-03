<?php
/*
=====================================================
 DLE Filter - Uninstaller for DLE 18.1
=====================================================
*/

if( !defined('DATALIFEENGINE') ) die("Hacking attempt!");

$module_name = "dle_filter";

// Remove from admin sections
$db->query("DELETE FROM `" . PREFIX . "_admin_sections` WHERE name='{$module_name}'");

// Drop module tables
$db->query("DROP TABLE IF EXISTS `" . PREFIX . "_dle_filter_statistics`");
$db->query("DROP TABLE IF EXISTS `" . PREFIX . "_dle_filter_news`");
$db->query("DROP TABLE IF EXISTS `" . PREFIX . "_dle_filter_news_temp`");

// Drop triggers (cleanup)
$db->query("DROP TRIGGER IF EXISTS filter_news_delete");
$db->query("DROP TRIGGER IF EXISTS filter_news_update");
$db->query("DROP TRIGGER IF EXISTS filter_news_insert");

if( $db->error_count ) {
    die("Uninstallation failed: " . $db->query_error);
}

echo "🗑️ DLE Filter module successfully uninstalled from DLE 18.1!";
?>
