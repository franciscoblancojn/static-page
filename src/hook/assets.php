<?php

class STPA_ASSETS
{
    static private $QUERY_VAR = STPA_KEY . '_ASSET';

    public static function init()
    {
        add_filter('query_vars', [self::class, 'registerQueryVar']);
        add_action('template_redirect', [self::class, 'serve'], 1);
    }

    public static function registerQueryVar($vars)
    {
        $vars[] = self::$QUERY_VAR;
        return $vars;
    }

    public static function serve()
    {
        $file_param = get_query_var(self::$QUERY_VAR);
        if (!$file_param) return;

        if (!preg_match('/^page-(\d+)\.(css|js)$/', $file_param, $matches)) {
            status_header(400);
            exit;
        }

        $post_id = (int) $matches[1];
        $ext = $matches[2];

        $html_file = get_post_meta($post_id, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
        if ($html_file && file_exists($html_file)) {
            $dir = dirname($html_file);
            $file = $dir . '/' . $file_param;
        } else {
            $upload_dir = wp_upload_dir();
            $file = $upload_dir['basedir'] . '/' . STPA_KEY . '/' . $file_param;
        }

        if (!file_exists($file)) {
            status_header(404);
            exit;
        }

        $content_type = $ext === 'css' ? 'text/css' : 'application/javascript';

        header('Content-Type: ' . $content_type . '; charset=UTF-8');
        header('Cache-Control: public, max-age=31536000');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

STPA_ASSETS::init();
