<?php
/*
=====================================================
 DLE Filter - Admin Panel Footer Template for DLE 18.1
=====================================================
*/

if (!defined('DATALIFEENGINE')) {
    header('HTTP/1.1 403 Forbidden');
    header('Location: ../../../../');
    die('Hacking attempt!');
}

try {
    // Output global JavaScript variables for DLE 18.1
    global $modLName, $dle_login_hash, $langVar, $jsAdminScript;

    // Combine and output JavaScript scripts
    $js_output = '';
    if (!empty($jsAdminScript)) {
        $js_output = implode("\n", $jsAdminScript);
    }

    echo <<<HTML
        </div><!-- End panel-body -->
    </div><!-- End panel -->
</div><!-- End container -->

<script>
// DLE 18.1: Global admin scripts for DLE Filter module
var coreAdmin = {
    mod: '{$modLName}',
    alert: function(data) {
        DLEalert(data.message, data.title || '{$langVar['admin']['success']}');
    },
    ajaxSend: function(data, action, redirect) {
        $.ajax({
            url: 'engine/lazydev/{$modLName}/admin_ajax.php',
            type: 'POST',
            data: $.extend({ action: action, dle_hash: '{$dle_login_hash}' }, data),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'ok') {
                    coreAdmin.alert(response);
                    if (redirect) {
                        window.location = redirect;
                    }
                } else {
                    coreAdmin.alert({ message: response.message, title: '{$langVar['admin']['error']}' });
                }
            },
            error: function(xhr) {
                coreAdmin.alert({ message: 'AJAX error: ' + xhr.status, title: '{$langVar['admin']['error']}' });
            }
        });
    }
};

// DLE 18.1: Initialize search autocomplete for real-time search
$(function() {
    $('.search-input').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'engine/lazydev/{$modLName}/admin_ajax.php',
                type: 'POST',
                data: {
                    action: 'findNews',
                    query: request.term,
                    dle_hash: '{$dle_login_hash}'
                },
                dataType: 'json',
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: item.name,
                            value: item.value
                        };
                    }));
                },
                error: function(xhr) {
                    DLEalert('Search error: ' + xhr.status, '{$langVar['admin']['error']}');
                }
            });
        },
        minLength: 2
    });
});

// Additional module-specific scripts
{$js_output}
</script>

<!-- DLE 18.1: Ensure compatibility with TinyMCE and mobile/dark theme -->
<script src="{$config['http_home_url']}engine/editor/jscripts/tinymce/tinymce.min.js"></script>
<script>
$(function() {
    tinymce.init({
        selector: '.tinymce',
        theme: 'modern',
        mobile: { theme: 'mobile' }, // DLE 18.1 mobile support
        skin: '{$config['skin']}', // Support DLE 18's dark theme
        plugins: 'advlist autolink lists link image charmap print preview',
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image'
    });
});
</script>

</body>
</html>
HTML;

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: {$e->getMessage()}</div>";
}
?>
