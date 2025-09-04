<?php
/*
=====================================================
 DLE Filter - Admin Panel Dashboard for DLE 18.1
=====================================================
*/

if (!defined('DATALIFEENGINE')) {
    header('HTTP/1.1 403 Forbidden');
    header('Location: ../../../');
    die('Hacking attempt!');
}

use LazyDev\Filter\Admin;

try {
    // Initialize module name and CSRF token
    $modLName = 'dle_filter';
    $dle_login_hash = isset($_REQUEST['dle_hash']) ? totranslit(strip_tags($_REQUEST['dle_hash']), true, false) : '';

    // Load language
    $langVar = Data::receive('lang');

    // Render dashboard with navigation menu
    echo <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">{$langVar['admin']['other']['block_menu']}</div>
    <div class="list-bordered">
HTML;

    // Generate menu (optimized for DLE 18.1 search navigation)
    echo Admin::menu([
        [
            'link' => '?mod=' . $modLName . '&action=settings',
            'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/settings.png',
            'title' => $langVar['admin']['settings_title'],
            'descr' => $langVar['admin']['settings_descr'],
            'class' => 'btn btn-outline-primary btn-sm'
        ],
        [
            'link' => '?mod=' . $modLName . '&action=statistics',
            'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/statistics.png',
            'title' => $langVar['admin']['statistics_title'],
            'descr' => $langVar['admin']['statistics_descr'],
            'class' => 'btn btn-outline-primary btn-sm'
        ],
        [
            'link' => '?mod=' . $modLName . '&action=fields',
            'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/fields.png',
            'title' => $langVar['admin']['fields_title'],
            'descr' => $langVar['admin']['fields_descr'],
            'class' => 'btn btn-outline-primary btn-sm'
        ],
        [
            'link' => '?mod=' . $modLName . '&action=search',
            'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/search.png',
            'title' => $langVar['admin']['search_title'],
            'descr' => $langVar['admin']['search_descr'],
            'class' => 'btn btn-outline-primary btn-sm'
        ]
    ]);

    echo <<<HTML
    </div>
</div>
<script>
// DLE 18.1: Dynamic menu navigation with search integration
$(function() {
    $('.list-bordered a').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET',
            data: { dle_hash: '{$dle_login_hash}' },
            success: function(response) {
                $('#dle-content').html(response);
                coreAdmin.alert({ message: '{$langVar['admin']['success']}', title: '{$langVar['admin']['loaded']}' });
            },
            error: function(xhr) {
                coreAdmin.alert({ message: 'Error loading section: ' + xhr.status, title: '{$langVar['admin']['error']}' });
            }
        });
    });
});
</script>
HTML;

    // Include footer
    include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/template/footer.php';

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: {$e->getMessage()}</div>";
}
?>
