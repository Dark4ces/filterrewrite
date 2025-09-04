<?php
/*
=====================================================
 DLE Filter - Settings Admin Panel for DLE 18.1
=====================================================
*/

if (!defined('DATALIFEENGINE')) {
    header('HTTP/1.1 403 Forbidden');
    header('Location: ../../../');
    die('Hacking attempt!');
}

use LazyDev\Filter\Data;
use LazyDev\Filter\Helper;

try {
    // Load module configuration and language
    $configVar = Data::receive('config');
    $langVar = Data::receive('lang');

    // Initialize CSRF token
    $dle_login_hash = isset($_REQUEST['dle_hash']) ? totranslit(strip_tags($_REQUEST['dle_hash']), true, false) : '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
        if ($dle_login_hash !== $dle_login_hash) {
            throw new Exception('Invalid CSRF token');
        }

        // Sanitize and save settings
        $settings = [
            'sort_field' => isset($_POST['sort_field']) ? totranslit(strip_tags($_POST['sort_field']), true, false) : 'date',
            'sort_order' => isset($_POST['sort_order']) ? totranslit(strip_tags($_POST['sort_order']), true, false) : 'desc',
            'categories' => isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : [],
            'exclude_news' => isset($_POST['exclude_news']) ? array_map('intval', $_POST['exclude_news']) : [],
            'search_query' => isset($_POST['search_query']) ? $db->safesql(strip_tags($_POST['search_query'])) : ''
        ];

        // Save settings to config
        Data::save('config', $settings);

        // Log action to statistics (optimized for DLE 18.1)
        $db->query("
            INSERT INTO " . PREFIX . "_dle_filter_statistics
            (dateFilter, foundNews, ip, queryNumber, nick, memoryUsage, mysqlTime, templateTime, statistics, sqlQuery, allTime)
            VALUES
            (NOW(), 0, '" . $db->safesql($_SERVER['REMOTE_ADDR']) . "', 1, '" . $db->safesql($member_id['name']) . "', " . memory_get_usage() . ", 0, 0, '" . $db->safesql(json_encode($settings)) . "', 'Settings Update', 0)
        ");

        echo "<div class='alert alert-success'>{$langVar['admin']['settings']['saved']}</div>";
    }

    // Load categories for selection (optimized for DLE 18's category handling)
    $categories = CategoryNewsSelection(empty($configVar['exclude_categories']) ? 0 : $configVar['exclude_categories']);

    // Define sort fields
    $sortField = [
        'date' => $langVar['admin']['settings']['p.date'],
        'editdate' => $langVar['admin']['settings']['e.editdate'],
        'title' => $langVar['admin']['settings']['p.title'],
        'autor' => $langVar['admin']['settings']['p.autor'],
        'rating' => $langVar['admin']['settings']['e.rating'],
        'comm_num' => $langVar['admin']['settings']['p.comm_num'],
        'news_read' => $langVar['admin']['settings']['e.news_read']
    ];

    // Define sort order
    $order = [
        'desc' => $langVar['admin']['settings']['desc'],
        'asc' => $langVar['admin']['settings']['asc']
    ];

    // Render settings form
    echo <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">{$langVar['admin']['settings']['title']}</div>
    <div class="panel-body">
        <form action="" method="post" id="settingsForm">
            <input type="hidden" name="action" value="save_settings">
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
                <select name="categories[]" multiple class="form-control" size="5">
HTML;
    foreach ($categories as $cat_id => $cat_name) {
        $selected = in_array($cat_id, $configVar['categories'] ?? []) ? 'selected' : '';
        echo "<option value=\"{$cat_id}\" {$selected}>{$cat_name}</option>";
    }
    echo <<<HTML
                </select>
            </div>
            <div class="form-group">
                <label>{$langVar['admin']['settings']['search_query']}</label>
                <input type="text" name="search_query" class="form-control search-input tinymce" value="{$configVar['search_query']}">
            </div>
            <button type="submit" class="btn btn-primary">{$langVar['admin']['settings']['save']}</button>
        </form>
    </div>
</div>
<script>
// DLE 18.1: AJAX form submission for settings
$(function() {
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        coreAdmin.ajaxSend($(this).serialize(), 'save_settings', null);
    });
});
</script>
HTML;

    // Include footer
    include ENGINE_DIR . '/lazydev/dle_filter/admin/template/footer.php';

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: {$e->getMessage()}</div>";
}
?>
