<?php

add_action('wp_ajax_stpa_toggle_active', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_toggle_' . ($_POST['post_id'] ?? 0))) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $post_id = intval($_POST['post_id']);
    $config = get_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (!is_array($config)) {
        $config = [];
    }

    $current = $config[STPA_PAGE_CONFIG::KEY_ACTIVE] ?? false;
    $config[STPA_PAGE_CONFIG::KEY_ACTIVE] = !$current;
    update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, $config);

    wp_send_json_success(['active' => $config[STPA_PAGE_CONFIG::KEY_ACTIVE]]);
});

add_action('wp_ajax_stpa_delete_file', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_delete_' . ($_POST['post_id'] ?? 0))) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $post_id = intval($_POST['post_id']);
    $keep_css_js = get_option(STPA_CONFIG, [])['keep_css_js'] ?? false;

    $html_file = get_post_meta($post_id, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
    if ($html_file && file_exists($html_file)) {
        $dir = dirname($html_file);
        unlink($html_file);

        if (!$keep_css_js) {
            foreach (['css', 'js'] as $ext) {
                $f = $dir . "/page-{$post_id}.{$ext}";
                if (file_exists($f)) unlink($f);
            }
        }
    }

    delete_post_meta($post_id, STPA_PAGE_CONFIG::KEY_HTML_FILE);

    $config = get_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (is_array($config)) {
        $config[STPA_PAGE_CONFIG::KEY_ACTIVE] = '0';
        update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, $config);
    }

    wp_send_json_success();
});

add_action('wp_ajax_stpa_bulk_action', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_bulk')) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $action = $_POST['bulk_action'] ?? '';
    $post_ids = array_map('intval', explode(',', $_POST['post_ids'] ?? ''));
    $keep_css_js = get_option(STPA_CONFIG, [])['keep_css_js'] ?? false;

    foreach ($post_ids as $post_id) {
        $config = get_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, true);
        if (!is_array($config)) {
            $config = [];
        }

        switch ($action) {
            case 'activate':
                $config[STPA_PAGE_CONFIG::KEY_ACTIVE] = '1';
                update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, $config);
                break;

            case 'deactivate':
                $config[STPA_PAGE_CONFIG::KEY_ACTIVE] = '0';
                update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, $config);
                break;

            case 'regenerate':
                $config[STPA_PAGE_CONFIG::KEY_ACTIVE] = '1';
                update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, $config);

                $url = add_query_arg(STPA_KEY . '_DISABLE', '1', get_permalink($post_id));
                $response = wp_remote_get($url, [
                    'timeout' => 60,
                    'sslverify' => false,
                    'headers' => ['Cache-Control' => 'no-cache'],
                ]);

                if (!is_wp_error($response)) {
                    $html = wp_remote_retrieve_body($response);
                    $dir = STPA_get_output_dir($post_id);
                    if (!file_exists($dir)) {
                        wp_mkdir_p($dir);
                    }
                    $file = $dir . "/page-{$post_id}.html";
                    file_put_contents($file, $html);
                    update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_HTML_FILE, $file);
                }
                break;

            case 'delete':
                $html_file = get_post_meta($post_id, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
                if ($html_file && file_exists($html_file)) {
                    $dir = dirname($html_file);
                    unlink($html_file);
                    if (!$keep_css_js) {
                        foreach (['css', 'js'] as $ext) {
                            $f = $dir . "/page-{$post_id}.{$ext}";
                            if (file_exists($f)) unlink($f);
                        }
                    }
                }
                delete_post_meta($post_id, STPA_PAGE_CONFIG::KEY_HTML_FILE);
                $config[STPA_PAGE_CONFIG::KEY_ACTIVE] = '0';
                update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, $config);
                break;
        }
    }

    wp_send_json_success();
});

add_action('wp_ajax_stpa_regenerate', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_regen_' . ($_POST['post_id'] ?? 0))) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(['message' => 'Página no encontrada']);
    }

    $config = get_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (!is_array($config)) {
        $config = [];
    }
    $config[STPA_PAGE_CONFIG::KEY_ACTIVE] = '1';
    update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, $config);

    $url = add_query_arg(STPA_KEY . '_DISABLE', '1', get_permalink($post_id));
    $response = wp_remote_get($url, [
        'timeout' => 60,
        'sslverify' => false,
        'headers' => [
            'Cache-Control' => 'no-cache',
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error al obtener la página: ' . $response->get_error_message()]);
    }

    $html = wp_remote_retrieve_body($response);

    $dir = STPA_get_output_dir($post_id);
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }

    $file = $dir . "/page-{$post_id}.html";
    file_put_contents($file, $html);
    update_post_meta($post_id, STPA_PAGE_CONFIG::KEY_HTML_FILE, $file);

    wp_send_json_success([
        'message' => 'Página regenerada correctamente.',
        'size' => size_format(filesize($file)),
    ]);
});

function STPA_get_global_sections_dir()
{
    $config = get_option(STPA_CONFIG, []);
    $output_dir = !empty($config['output_dir']) ? sanitize_title($config['output_dir']) : STPA_KEY;
    $upload_dir = wp_upload_dir();
    return $upload_dir['basedir'] . '/' . $output_dir . '/section-global';
}

function STPA_sanitize_gs_key($key)
{
    return preg_replace('/[^a-zA-Z0-9#._-]/', '', trim($key));
}

function STPA_save_global_section($key, $html, $page_id)
{
    $dir = STPA_get_global_sections_dir();
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }

    file_put_contents($dir . '/' . $key . '.html', $html);

    $meta = [
        'source_page_id' => $page_id,
        'key' => $key,
        'created' => time(),
        'updated' => time(),
    ];
    file_put_contents($dir . '/' . $key . '.json', json_encode($meta, JSON_PRETTY_PRINT));
}

add_action('wp_ajax_stpa_gs_create', function () {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No tienes permisos']);
        }
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_gs_create')) {
            wp_send_json_error(['message' => 'Nonce inválido']);
        }

        $page_id = intval($_POST['page_id'] ?? 0);
        $key = STPA_sanitize_gs_key($_POST['key'] ?? '');
        $elementHtml = wp_unslash($_POST['html'] ?? '');

        if (!$page_id || !get_post($page_id)) {
            wp_send_json_error(['message' => 'Página no válida']);
        }
        if (empty($key)) {
            wp_send_json_error(['message' => 'La clave de búsqueda es requerida']);
        }
        if (trim($elementHtml) === '') {
            wp_send_json_error(['message' => "No se encontró el elemento '{$key}' en la página seleccionada."]);
        }

        STPA_save_global_section($key, $elementHtml, $page_id);

        wp_send_json_success([
            'message' => "Sección global '{$key}' creada correctamente.",
            'size' => size_format(strlen($elementHtml)),
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

add_action('wp_ajax_stpa_gs_regenerate', function () {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No tienes permisos']);
        }
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_gs_regen_' . ($_POST['key'] ?? ''))) {
            wp_send_json_error(['message' => 'Nonce inválido']);
        }

        $key = STPA_sanitize_gs_key($_POST['key'] ?? '');
        $page_id = intval($_POST['page_id'] ?? 0);
        $elementHtml = wp_unslash($_POST['html'] ?? '');

        if (empty($key)) {
            wp_send_json_error(['message' => 'Clave no válida']);
        }
        if (!$page_id || !get_post($page_id)) {
            wp_send_json_error(['message' => 'Página de origen no válida']);
        }
        if (trim($elementHtml) === '') {
            wp_send_json_error(['message' => "No se encontró el elemento '{$key}' en la página seleccionada."]);
        }

        STPA_save_global_section($key, $elementHtml, $page_id);

        wp_send_json_success([
            'message' => "Sección global '{$key}' regenerada correctamente.",
            'size' => size_format(strlen($elementHtml)),
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

add_action('wp_ajax_stpa_gs_delete', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_gs_delete_' . ($_POST['key'] ?? ''))) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $key = STPA_sanitize_gs_key($_POST['key'] ?? '');
    if (empty($key)) {
        wp_send_json_error(['message' => 'Clave no válida']);
    }

    $dir = STPA_get_global_sections_dir();
    $htmlFile = $dir . '/' . $key . '.html';
    $metaFile = $dir . '/' . $key . '.json';

    if (file_exists($htmlFile)) {
        unlink($htmlFile);
    }
    if (file_exists($metaFile)) {
        unlink($metaFile);
    }

    wp_send_json_success(['message' => "Sección global '{$key}' eliminada."]);
});

add_action('wp_ajax_stpa_gs_view', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_gs_view_' . ($_POST['key'] ?? ''))) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $key = STPA_sanitize_gs_key($_POST['key'] ?? '');
    if (empty($key)) {
        wp_send_json_error(['message' => 'Clave no válida']);
    }

    $dir = STPA_get_global_sections_dir();
    $htmlFile = $dir . '/' . $key . '.html';

    if (!file_exists($htmlFile)) {
        wp_send_json_error(['message' => 'Archivo no encontrado']);
    }

    $content = file_get_contents($htmlFile);
    wp_send_json_success([
        'key' => $key,
        'html' => $content,
        'size' => size_format(filesize($htmlFile)),
    ]);
});
