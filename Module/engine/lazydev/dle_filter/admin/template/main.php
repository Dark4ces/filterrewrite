<?php
/*
=====================================================
 DLE Filter - Admin Panel Main Template for DLE 18.1
=====================================================
*/

if (!defined('DATALIFEENGINE')) {
    header('HTTP/1.1 403 Forbidden');
    header('Location: ../../../../');
    die('Hacking attempt!');
}

use LazyDev\Filter\Admin;

try {
    // Render the main admin panel interface
    echo <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">{$langVar['admin']['other']['block_menu']}</div>
    <div class="list-bordered">
HTML;

    // Generate menu with updated DLE 18.1 styling
    echo Admin::menu([
        [
            'link' => '?mod=' . $modLName . '&action=settings',
            'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/settings.png',
            'title' => $langVar['admin']['settings_title'],
            'descr' => $langVar['admin']['settings_descr'],
            'class' => 'btn btn-outline-primary btn-sm' // DLE 18.1 Bootstrap styling
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
        ]
    ]);

    echo <<<HTML
    </div>
</div>
<script>
// DLE 18.1: Enhanced menu interaction with real-time search integration
$(function() {
    $('.list-bordered a').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET',
            data: { dle_hash: '{$dle_login_hash}' },
            success: function(response) {
                // Load content dynamically into the admin panel
                $('#dle-content').html(response);
                DLEalert('{$langVar['admin']['success']}', '{$langVar['admin']['loaded']}');
            },
            error: function(xhr) {
                DLEalert('Error loading section: ' + xhr.status, '{$langVar['admin']['error']}');
            }
        });
    });
});
</script>
HTML;

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: {$e->getMessage()}</div>";
}
?>
