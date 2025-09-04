<?php
/*
=====================================================
 DLE Filter - Installer for DLE 18.1 by grok
=====================================================
*/

if (!defined('DATALIFEENGINE')) {
    die("Hacking attempt!");
}

// Module info
$module_name = "dle_filter";

try {
    // Remove old admin section (if exists)
    $db->query("DELETE FROM `" . PREFIX . "_admin_sections` WHERE name='{$module_name}'");
    $db->query("DELETE FROM `" . PREFIX . "_admin_sections` WHERE name='field_search'");

    // Drop old stats table (cleanup)
    $db->query("DROP TABLE IF EXISTS `" . PREFIX . "_dle_filter_statistics`");

    // Register module in admin panel (updated for DLE 18's admin features)
    $db->query("
        INSERT INTO `" . PREFIX . "_admin_sections`
        (`name`, `title`, `descr`, `icon`, `allow_groups`)
        VALUES
        ('{$module_name}', 'DLE Filter', 'Управление настройками', '', '1')
    ");

    // Create statistics table (optimized for DLE 18's database performance)
    $db->query("
        CREATE TABLE IF NOT EXISTS `" . PREFIX . "_dle_filter_statistics` (
            `idFilter` INT AUTO_INCREMENT PRIMARY KEY,
            `dateFilter` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `foundNews` TINYINT(1) NOT NULL DEFAULT 0,
            `ip` VARCHAR(40) NOT NULL,
            `queryNumber` SMALLINT(2) NOT NULL,
            `nick` VARCHAR(40) NOT NULL,
            `memoryUsage` DOUBLE NOT NULL,
            `mysqlTime` DOUBLE NOT NULL,
            `templateTime` DOUBLE NOT NULL,
            `statistics` MEDIUMTEXT NOT NULL,
            `sqlQuery` TEXT NOT NULL,
            `allTime` DOUBLE NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='DLE Filter statistics';
    ");

    // Create news table
    $db->query("
        CREATE TABLE IF NOT EXISTS `" . PREFIX . "_dle_filter_news` (
            `filterId` INT AUTO_INCREMENT PRIMARY KEY,
            `newsId` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='DLE Filter news binding';
    ");

    // Create temp news table
    $db->query("
        CREATE TABLE IF NOT EXISTS `" . PREFIX . "_dle_filter_news_temp` (
            `tempId` INT AUTO_INCREMENT PRIMARY KEY,
            `newsId` INT NOT NULL,
            `xfieldNew` MEDIUMTEXT NOT NULL,
            `allow_br` TINYINT(1) NOT NULL DEFAULT '1'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='DLE Filter temporary storage';
    ");

    echo "✅ DLE Filter module successfully installed for DLE 18.1! Note: Check DLE 18's admin panel for new features like AI integration.";
} catch (Exception $e) {
    die("Installation failed: " . $e->getMessage());
}
?>
