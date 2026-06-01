<?php

add_action('admin_menu', function () {
    add_submenu_page(
        STPA_KEY,
        'Páginas Estáticas',
        'Páginas Estáticas',
        'manage_options',
        STPA_KEY . '_config',
        'STPA_PAGE_CONFIG_VIEW'
    );
    remove_submenu_page(STPA_KEY, STPA_KEY);
});

function STPA_PAGE_CONFIG_VIEW()
{
    require_once STPA_DIR . 'src/page/pages/config/page.php';
}
