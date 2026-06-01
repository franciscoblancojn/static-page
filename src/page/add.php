<?php

add_action('admin_menu', function () {
    add_menu_page(
        'Static Page',
        'Static Page',
        'manage_options',
        STPA_KEY,
        'STPA_REDIRECT_FIRST_SUBMENU',
        'dashicons-media-document'
    );
});

function STPA_REDIRECT_FIRST_SUBMENU()
{
    wp_redirect(admin_url('admin.php?page=' . STPA_KEY . '_config'));
    exit;
}
