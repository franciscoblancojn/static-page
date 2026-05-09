<?php
add_action('template_redirect', function () {

    if (!is_page()) {
        return;
    }

    global $post;

    if (!$post) {
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