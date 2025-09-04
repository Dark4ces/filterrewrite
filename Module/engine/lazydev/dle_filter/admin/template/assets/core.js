/*
=====================================================
 DLE Filter - Core Admin JavaScript for DLE 18.1
=====================================================
*/

// Core admin object for DLE Filter module
var coreAdmin = {
    mod: 'dle_filter', // Module name
    alert: function(data) {
        // DLE 18.1: Use DLEalert for user notifications
        DLEalert(data.message, data.title || 'Success');
    },
    ajaxSend: function(data, action, redirect) {
        // DLE 18.1: AJAX handler with CSRF token
        $.ajax({
            url: 'engine/lazydev/' + this.mod + '/admin_ajax.php',
            type: 'POST',
            data: $.extend({ action: action, dle_hash: dle_login_hash }, data),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'ok') {
                    coreAdmin.alert(response);
                    if (redirect) {
                        window.location = redirect;
                    }
                } else {
                    coreAdmin.alert({ message: response.message, title: 'Error' });
                }
            },
            error: function(xhr) {
                coreAdmin.alert({ message: 'AJAX error: ' + xhr.status, title: 'Error' });
            }
        });
    }
};

// DLE 18.1: Initialize real-time search autocomplete
$(function() {
    // Autocomplete for news search
    $('.search-input').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'engine/lazydev/' + coreAdmin.mod + '/admin_ajax.php',
                type: 'POST',
                data: {
                    action: 'findNews',
                    query: request.term,
                    dle_hash: dle_login_hash
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
                    coreAdmin.alert({ message: 'Search error: ' + xhr.status, title: 'Error' });
                }
            });
        },
        minLength: 2,
        delay: 300 // DLE 18.1: Optimize for performance
    });

    // Handle menu navigation (from main.php)
    $('.list-bordered a').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET',
            data: { dle_hash: dle_login_hash },
            success: function(response) {
                $('#dle-content').html(response);
                coreAdmin.alert({ message: 'Section loaded', title: 'Success' });
            },
            error: function(xhr) {
                coreAdmin.alert({ message: 'Error loading section: ' + xhr.status, title: 'Error' });
            }
        });
    });

    // DLE 18.1: Initialize TinyMCE for text inputs
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '.tinymce',
            theme: 'modern',
            mobile: { theme: 'mobile' }, // DLE 18.1: Mobile support
            skin: dle_skin, // DLE 18.1: Dark theme support
            plugins: 'advlist autolink lists link image charmap print preview',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save(); // Auto-save for DLE 18.1
                });
            }
        });
    }
});
