<?php
add_action('template_redirect', function () {

    if (isset($_GET[STPA_KEY . "_DISABLE"])) {
        return;
    }
    if (isset($_GET["action"]) && $_GET["action"] == "elementor") {
        return;
    }
    if (
        defined('ELEMENTOR_VERSION') &&
        (
            \Elementor\Plugin::$instance->editor->is_edit_mode()
            || \Elementor\Plugin::$instance->preview->is_preview_mode()
        )
    ) {
        return;
    }
    // Admin
    if (is_admin()) {
        return;
    }

    // AJAX
    if (wp_doing_ajax()) {
        return;
    }

    // REST API
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    if (!is_page()) {
        return;
    }

    global $post;

    if (!$post) {
        return;
    }
    $config = get_post_meta($post->ID, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (!isset($config[STPA_PAGE_CONFIG::KEY_ACTIVE]) || !$config[STPA_PAGE_CONFIG::KEY_ACTIVE]) {
        return;
    }
    /**
     * Obtener HTML personalizado
     */
    $custom_html = get_post_meta(
        $post->ID,
        STPA_PAGE_CONFIG::KEY_HTML,
        true
    );

    /**
     * Si no existe HTML personalizado
     * continuar normal
     */
    if (empty($custom_html)) {
        return;
    }

    /**
     * Headers opcionales
     */
    status_header(200);

    header('Content-Type: text/html; charset=utf-8');

    /**
     * Imprimir HTML
     */
    echo $custom_html;

    /**
     * Detener WordPress
     */
    exit;
});
