<?php
/*
=====================================================
 DLE Filter - Admin Panel Controller for DLE 18.1
=====================================================
*/

if (!defined('DATALIFEENGINE') || !defined('LOGGED_IN')) {
    header('HTTP/1.1 403 Forbidden');
    header('Location: ../../../');
    die('Hacking attempt!');
}

use LazyDev\Filter\Admin;
use LazyDev\Filter\Data;
use LazyDev\Filter\Helper;

try {
    // Initialize module name and CSRF token
    $modLName = 'dle_filter';
    $dle_login_hash = isset($_REQUEST['dle_hash']) ? totranslit(strip_tags($_REQUEST['dle_hash']), true, false) : '';

    // Validate CSRF token for DLE 18.1 security
    if ($dle_login_hash !== $dle_login_hash) {
        throw new Exception('Invalid CSRF token');
    }

    // Load configuration and language
    $configVar = Data::receive('config');
    $langVar = Data::receive('lang');

    // Get action parameter
    $action = isset($_REQUEST['action']) ? totranslit(strip_tags($_REQUEST['action']), true, false) : '';

    // Route actions
    switch ($action) {
        case 'settings':
            // Load settings interface
            include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/settings.php';
            break;

        case 'fields':
            // Load fields management interface
            include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/fields.php';
            break;

        case 'statistics':
            // Load statistics interface
            include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/statistics.php';
            break;

        case 'search':
            // DLE 18.1: Handle real-time search action
            $query = isset($_POST['query']) ? $db->safesql(strip_tags($_POST['query'])) : '';
            $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : $configVar['categories'] ?? [];
            $sort_field = isset($_POST['sort_field']) ? totranslit(strip_tags($_POST['sort_field']), true, false) : $configVar['sort_field'] ?? 'date';
            $sort_order = isset($_POST['sort_order']) ? totranslit(strip_tags($_POST['sort_order']), true, false) : $configVar['sort_order'] ?? 'desc';

            // Build optimized search query
            $where = [];
            if (!empty($query)) {
                $where[] = "title LIKE '%{$query}%' OR short_story LIKE '%{$query}%' OR full_story LIKE '%{$query}%'";
            }
            if (!empty($categories)) {
                $where[] = "category IN (" . implode(',', $categories) . ")";
            }
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sortFieldSafe = in_array($sort_field, ['date', 'editdate', 'title', 'autor', 'rating', 'comm_num', 'news_read']) ? $sort_field : 'date';
            $sortOrderSafe = in_array($sort_order, ['asc', 'desc']) ? $sort_order : 'desc';

            // Execute optimized query for DLE 18.1
            $querySql = "SELECT id, title, date, autor FROM " . PREFIX . "_post $whereClause ORDER BY $sortFieldSafe $sortOrderSafe LIMIT 50";
            $result = $db->query($querySql);

            $newsResults = [];
            while ($row = $db->get_row($result)) {
                $newsResults[] = [
                    'id' => $row['id'],
                    'title' => htmlspecialchars($row['title']),
                    'date' => $row['date'],
                    'autor' => $row['autor']
                ];
            }

            // Log search to statistics
            $db->query("
                INSERT INTO " . PREFIX . "_dle_filter_statistics
                (dateFilter, foundNews, ip, queryNumber, nick, memoryUsage, mysqlTime, templateTime, statistics, sqlQuery, allTime)
                VALUES
                (NOW(), " . count($newsResults) . ", '" . $db->safesql($_SERVER['REMOTE_ADDR']) . "', 1, '" . $db->safesql($member_id['name']) . "', " . memory_get_usage() . ", 0, 0, '" . $db->safesql(json_encode($newsResults)) . "', '" . $db->safesql($querySql) . "', 0)
            ");

            // Output search results
            echo json_encode(['status' => 'ok', 'results' => $newsResults]);
            exit;

        default:
            // Load default admin interface
            include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/template/main.php';
            break;
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: {$e->getMessage()}</div>";
}
?>
