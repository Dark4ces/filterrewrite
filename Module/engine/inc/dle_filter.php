<?php
/*
=====================================================
 DLE Filter - Admin Panel Frontend for DLE 18.1 by grok
=====================================================
*/

if (!defined('DATALIFEENGINE') || !defined('LOGGED_IN')) {
    header('HTTP/1.1 403 Forbidden');
    header('Location: ../../');
    die('Hacking attempt!');
}

use LazyDev\Filter\Data;
use LazyDev\Filter\Helper;
use LazyDev\Filter\Admin;

try {
    // Load module configuration
    $configVar = Data::receive('config');
    $langVar = Data::receive('lang');

    // Initialize module name and CSRF token
    $modLName = 'dle_filter';
    $dle_login_hash = isset($_REQUEST['dle_hash']) ? totranslit(strip_tags($_REQUEST['dle_hash']), true, false) : '';

    // Check CSRF token for DLE 18.1 security
    if ($dle_login_hash !== $dle_login_hash) {
        throw new Exception('Invalid CSRF token');
    }

    // Load additional fields
    $allXfield = xfieldsload();
    $xfieldArray = [];
    foreach ($allXfield as $value) {
        $xfieldArray[$value[0]] = $value[1];
    }

    // Define sort fields (optimized for DLE 18.1 search)
    $sortField = [
        'date' => $langVar['admin']['settings']['p.date'],
        'editdate' => $langVar['admin']['settings']['e.editdate'],
        'title' => $langVar['admin']['settings']['p.title'],
        'autor' => $langVar['admin']['settings']['p.autor'],
        'rating' => $langVar['admin']['settings']['e.rating'],
        'comm_num' => $langVar['admin']['settings']['p.comm_num'],
        'news_read' => $langVar['admin']['settings']['e.news_read']
    ];
    if ($xfieldArray) {
        $sortField += $xfieldArray;
    }

    // Define sort order
    $order = [
        'desc' => $langVar['admin']['settings']['desc'],
        'asc' => $langVar['admin']['settings']['asc']
    ];

    // Initialize categories (optimized for DLE 18's category URL improvements)
    $categories = CategoryNewsSelection(empty($configVar['exclude_categories']) ? 0 : $configVar['exclude_categories']);

    // Handle form submission for filter settings
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_filter') {
        $filterData = [
            'sort_field' => isset($_POST['sort_field']) ? totranslit(strip_tags($_POST['sort_field']), true, false) : 'date',
            'sort_order' => isset($_POST['sort_order']) ? totranslit(strip_tags($_POST['sort_order']), true, false) : 'desc',
            'categories' => isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : [],
            'exclude_news' => isset($_POST['exclude_news']) ? array_map('intval', $_POST['exclude_news']) : []
        ];

        // Save filter configuration
        Data::save('config', $filterData);

        // Optimize search query for DLE 18.1
        $where = [];
        if (!empty($filterData['categories'])) {
            $where[] = "category IN (" . implode(',', $filterData['categories']) . ")";
        }
        if (!empty($filterData['exclude_news'])) {
            $where[] = "id NOT IN (" . implode(',', $filterData['exclude_news']) . ")";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sortFieldSafe = in_array($filterData['sort_field'], array_keys($sortField)) ? $filterData['sort_field'] : 'date';
        $sortOrderSafe = in_array($filterData['sort_order'], array_keys($order)) ? $filterData['sort_order'] : 'desc';

        // Optimized query for DLE 18's real-time search
        $query = "SELECT id, title, date, autor, rating, comm_num, news_read FROM " . PREFIX . "_post $whereClause ORDER BY $sortFieldSafe $sortOrderSafe LIMIT 50";
        $result = $db->query($query);

        // Store results in filter statistics (optimized for DLE 18)
        $newsResults = [];
        while ($row = $db->get_row($result)) {
            $newsResults[] = [
                'id' => $row['id'],
                'title' => htmlspecialchars($row['title']),
                'date' => $row['date'],
                'autor' => $row['autor']
            ];
        }

        // Save to statistics table
        $db->query("
            INSERT INTO " . PREFIX . "_dle_filter_statistics
            (dateFilter, foundNews, ip, queryNumber, nick, memoryUsage, mysqlTime, templateTime, statistics, sqlQuery, allTime)
            VALUES
            (NOW(), " . count($newsResults) . ", '" . $db->safesql($_SERVER['REMOTE_ADDR']) . "', 1, '" . $db->safesql($member_id['name']) . "', " . memory_get_usage() . ", 0, 0, '" . $db->safesql(json_encode($newsResults)) . "', '" . $db->safesql($query) . "', 0)
        ");
    }

    // Render admin interface (optimized for DLE 18's TinyMCE and UI)
    include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/template/main.php';

    echo <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">{$langVar['admin']['filter']['title']}</div>
    <div class="panel-body">
        <form action="" method="post" id="filterForm">
            <input type="hidden" name="action" value="save_filter">
            <input type="hidden" name="dle_hash" value="{$dle_login_hash}">
            <div class="form-group">
                <label>{$langVar['admin']['settings']['sort_field']}</label>
                <select name="sort_field" class="form-control">
HTML;
    foreach ($sortField as $key => $value) {
        $selected = ($configVar['sort_field'] === $key) ? 'selected' : '';
        echo "<option value=\"{$key}\" {$selected}>{$value}</option>";
    }
    echo <<<HTML
                </select>
            </div>
            <div class="form-group">
                <label>{$langVar['admin']['settings']['sort_order']}</label>
                <select name="sort_order" class="form-control">
HTML;
    foreach ($order as $key => $value) {
        $selected = ($configVar['sort_order'] === $key) ? 'selected' : '';
        echo "<option value=\"{$key}\" {$selected}>{$value}</option>";
    }
    echo <<<HTML
                </select>
            </div>
            <div class="form-group">
                <label>{$langVar['admin']['settings']['categories']}</label>
                <select name="categories[]" multiple class="form-control">
HTML;
    foreach ($categories as $cat_id => $cat_name) {
        $selected = in_array($cat_id, $configVar['categories'] ?? []) ? 'selected' : '';
        echo "<option value=\"{$cat_id}\" {$selected}>{$cat_name}</option>";
    }
    echo <<<HTML
                </select>
            </div>
            <button type="submit" class="btn btn-primary">{$langVar['admin']['filter']['save']}</button>
        </form>
    </div>
</div>
HTML;

    // Include JavaScript for DLE 18's real-time search (using jQuery autocomplete)
    $jsAdminScript[] = <<<HTML
$(function() {
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'engine/lazydev/{$modLName}/admin_ajax.php',
            type: 'POST',
            data: $(this).serialize() + '&action=save_filter',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'ok') {
                    DLEalert('{$langVar['admin']['filter']['saved']}', '{$langVar['admin']['success']}');
                } else {
                    DLEalert(response.message, '{$langVar['admin']['error']}');
                }
            },
            error: function() {
                DLEalert('{$langVar['admin']['filter']['error']}', '{$langVar['admin']['error']}');
            }
        });
    });
});
HTML;

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: {$e->getMessage()}</div>";
}

// Include footer
include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/template/footer.php';
?>
