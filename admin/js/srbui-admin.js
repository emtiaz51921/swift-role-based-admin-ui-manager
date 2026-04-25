jQuery(document).ready(function($) {
    'use strict';

    const $roleSelect = $('#srbui-role-select');
    const $container = $('#srbui-settings-container');
    const $form = $('#srbui-settings-form');
    const $msg = $('#srbui-msg');
    const $spinner = $('#srbui-spinner');
    const $selectedRoleInput = $('#srbui-selected-role');

    // Tab Switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href').substring(1);
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').hide();
        $('#tab-' + target).show();
    });

    // Role Selection
    $roleSelect.on('change', function() {
        const role = $(this).val();
        if (!role) {
            $container.hide();
            return;
        }

        $selectedRoleInput.val(role);
        loadRoleSettings(role);
    });

    function loadRoleSettings(role) {
        $msg.text('').removeClass('success error');
        $spinner.addClass('is-active');

        $.post(srbui_vars.ajax_url, {
            action: 'srbui_load_settings',
            nonce: srbui_vars.nonce,
            role: role
        }, function(response) {
            $spinner.removeClass('is-active');
            if (response.success) {
                const data = response.data;
                
                // Inject the filtered HTML
                $('#srbui-menus-grid').html(data.html.menus);
                $('#srbui-admin-bar-grid').html(data.html.admin_bar);
                $('#srbui-dashboard-grid').html(data.html.dashboard);
                $('#srbui-plugins-grid').html(data.html.plugins);

                $container.fadeIn();
            } else {
                alert(response.data || srbui_vars.messages.error);
            }
        });
    }

    // Form Submission
    $form.on('submit', function(e) {
        e.preventDefault();
        
        $msg.text(srbui_vars.messages.saving).removeClass('success error');
        $spinner.addClass('is-active');

        const formData = $(this).serialize();
        let data = formData + '&action=srbui_save_settings';
        
        // Only append nonce from vars if it's not already in the serialized form.
        if (formData.indexOf('nonce=') === -1) {
            data += '&nonce=' + srbui_vars.nonce;
        }

        $.post(srbui_vars.ajax_url, data, function(response) {
            $spinner.removeClass('is-active');
            if (response.success) {
                $msg.text(srbui_vars.messages.saved).addClass('success');
            } else {
                $msg.text(response.data || srbui_vars.messages.error).addClass('error');
            }
        });
    });
});
