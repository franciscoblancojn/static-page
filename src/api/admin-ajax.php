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

add_action('wp_ajax_stpa_global_section_create', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_global_section_action')) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $selector = trim(wp_unslash($_POST['selector'] ?? ''));

    if (!$selector) {
        wp_send_json_error(['message' => 'La clave de búsqueda es requerida']);
    }

    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(['message' => 'Página no encontrada']);
    }

    $slug = STPA_global_section_slug($selector);
    $existing = STPA_GLOBAL_SECTIONS_DATA::get($slug);
    if ($existing && ($existing['selector'] ?? '') !== $selector) {
        wp_send_json_error(['message' => 'La clave "' . $selector . '" genera un identificador en conflicto con una sección existente. Usa otra clave.']);
    }

    $url = add_query_arg(STPA_KEY . '_DISABLE', '1', get_permalink($post_id));

    try {
        $html = STPA_extract_section_html($url, $selector);
    } catch (\Throwable $th) {
        wp_send_json_error(['message' => $th->getMessage()]);
    }

    $dir = STPA_get_global_sections_dir();
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
    $file = $dir . '/' . $slug . '.html';
    file_put_contents($file, $html);

    STPA_GLOBAL_SECTIONS_DATA::save($slug, [
        'selector' => $selector,
        'source_post_id' => $post_id,
        'file' => $file,
        'updated_at' => current_time('mysql'),
    ]);

    $updated = STPA_sweep_global_section_update($selector, $html);

    wp_send_json_success([
        'message' => 'Sección Global creada correctamente.',
        'slug' => $slug,
        'updated_pages' => count($updated),
    ]);
});

add_action('wp_ajax_stpa_global_section_regenerate', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_global_section_action')) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $slug = sanitize_title($_POST['slug'] ?? '');
    $entry = STPA_GLOBAL_SECTIONS_DATA::get($slug);
    if (!$entry) {
        wp_send_json_error(['message' => 'Sección Global no encontrada']);
    }

    $url = add_query_arg(STPA_KEY . '_DISABLE', '1', get_permalink($entry['source_post_id']));

    try {
        $html = STPA_extract_section_html($url, $entry['selector']);
    } catch (\Throwable $th) {
        wp_send_json_error(['message' => $th->getMessage()]);
    }

    file_put_contents($entry['file'], $html);
    $entry['updated_at'] = current_time('mysql');
    STPA_GLOBAL_SECTIONS_DATA::save($slug, $entry);

    $updated = STPA_sweep_global_section_update($entry['selector'], $html);

    wp_send_json_success([
        'message' => 'Sección Global regenerada correctamente.',
        'updated_pages' => count($updated),
    ]);
});

add_action('wp_ajax_stpa_global_section_delete', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stpa_global_section_action')) {
        wp_send_json_error(['message' => 'Nonce inválido']);
    }

    $slug = sanitize_title($_POST['slug'] ?? '');
    $entry = STPA_GLOBAL_SECTIONS_DATA::get($slug);
    if ($entry && !empty($entry['file']) && file_exists($entry['file'])) {
        unlink($entry['file']);
    }
    STPA_GLOBAL_SECTIONS_DATA::delete($slug);

    wp_send_json_success();
});
