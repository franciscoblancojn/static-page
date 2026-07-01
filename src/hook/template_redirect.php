<?php

function STPA_replace_global_sections($html)
{
    $config = get_option(STPA_CONFIG, []);
    $output_dir = !empty($config['output_dir']) ? sanitize_title($config['output_dir']) : STPA_KEY;
    $upload_dir = wp_upload_dir();
    $dir = $upload_dir['basedir'] . '/' . $output_dir . '/section-global';

    if (!is_dir($dir)) {
        return $html;
    }

    $files = glob($dir . '/*.html') ?: [];
    $hidden = glob($dir . '/.*.html') ?: [];
    $files = array_merge($files, $hidden);
    if (empty($files)) {
        return $html;
    }

    $replacements = [];

    foreach ($files as $file) {
        $key = basename($file, '.html');
        $sectionContent = file_get_contents($file);
        $patterns = [];

        if (strpos($key, '#') === 0) {
            $id = preg_quote(substr($key, 1), '/');
            $patterns[] = '/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*id\s*=\s*["\']' . $id . '["\'][^>]*>.*?<\/\1>/s';
            $patterns[] = '/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*id\s*=\s*["\']' . $id . '["\'][^>]*\/\s*>/s';
        } elseif (strpos($key, '.') === 0) {
            $class = preg_quote(substr($key, 1), '/');
            $patterns[] = '/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*class\s*=\s*["\'][^"\']*\b' . $class . '\b[^"\']*["\'][^>]*>.*?<\/\1>/s';
            $patterns[] = '/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*class\s*=\s*["\'][^"\']*\b' . $class . '\b[^"\']*["\'][^>]*\/\s*>/s';
        } else {
            $tag = preg_quote($key, '/');
            $patterns[] = '/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/s';
            $patterns[] = '/<' . $tag . '\b[^>]*\/\s*>/s';
        }

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $replacements[$m[0]] = $sectionContent;
                }
            }
        }
    }

    if (empty($replacements)) {
        return $html;
    }

    uksort($replacements, function ($a, $b) {
        return strlen($b) - strlen($a);
    });

    foreach ($replacements as $search => $replace) {
        $html = str_replace($search, $replace, $html);
    }

    return $html;
}

add_action('template_redirect', function () {

    if (isset($_GET[STPA_KEY . "_DISABLE"])) {
        return;
    }
    if (isset($_GET['preview']) && $_GET['preview'] == 'true') {
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

    $file = get_post_meta(
        $post->ID,
        STPA_PAGE_CONFIG::KEY_HTML_FILE,
        true
    );

    if ($file && file_exists($file)) {
        $html = file_get_contents($file);
        $html = STPA_replace_global_sections($html);
        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Length: ' . strlen($html));
        echo $html;
        exit;
    }
    return;
}, 1);
