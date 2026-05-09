<?php

class STPA_PROCESS_HTML
{
    /**
     * =========================
     * MINIFY CSS
     * =========================
     */
    public static function cssMinify($css = "")
    {
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        $css = str_replace(["\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);

        $replace = [
            '/\s*{\s*/' => '{',
            '/\s*}\s*/' => '}',
            '/\s*:\s*/' => ':',
            '/\s*;\s*/' => ';',
            '/\s*,\s*/' => ',',
            '/;}/' => '}',
        ];

        foreach ($replace as $search => $value) {
            $css = preg_replace($search, $value, $css);
        }

        return trim($css);
    }

    /**
     * =========================
     * MINIFY JS
     * =========================
     */
    public static function jsMinify($js = "")
    {
        $js = preg_replace('!/\*.*?\*/!s', '', $js);
        $js = preg_replace('/(^|[^:])\/\/.*$/m', '$1', $js);

        $js = str_replace(["\r", "\n", "\t"], ' ', $js);

        $js = preg_replace('/\s+/', ' ', $js);

        $replace = [
            '/\s*{\s*/' => '{',
            '/\s*}\s*/' => '}',
            '/\s*=\s*/' => '=',
            '/\s*;\s*/' => ';',
            '/\s*,\s*/' => ',',
            '/\s*\(\s*/' => '(',
            '/\s*\)\s*/' => ')',
            '/\s*\+\s*/' => '+',
            '/\s*-\s*/' => '-',
            '/\s*\*\s*/' => '*',
            '/\s*<\s*/' => '<',
            '/\s*>\s*/' => '>',
        ];

        foreach ($replace as $search => $value) {
            $js = preg_replace($search, $value, $js);
        }

        return trim($js);
    }

    /**
     * =========================
     * MINIFY HTML
     * =========================
     */
    public static function htmlMinify($html = "")
    {
        $html = preg_replace('/<!--(.|\s)*?-->/', '', $html);

        $html = str_replace(["\r", "\n", "\t"], ' ', $html);

        $html = preg_replace('/\s+/', ' ', $html);

        $html = preg_replace('/>\s+</', '><', $html);

        return trim($html);
    }

    /**
     * =========================
     * VALIDAR WP CONTENT
     * =========================
     */
    public static function isWpContent($url)
    {
        return strpos($url, '/wp-content/') !== false;
    }

    /**
     * =========================
     * OBTENER CODIGO
     * =========================
     */
    public static function getCode($url)
    {
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return "";
        }

        return wp_remote_retrieve_body($response);
    }

    /**
     * =========================
     * URL ABSOLUTA
     * =========================
     */
    public static function toAbsoluteUrl($url, $baseUrl)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    /**
     * =========================
     * ELIMINAR WP ADMIN BAR
     * =========================
     */
    public static function eliminarWpadminbar($html)
    {
        $html = preg_replace(
            '/<div id="wpadminbar".*?<\/div>/is',
            '',
            $html
        );

        return $html;
    }

    /**
     * =========================
     * FIX IMAGENES LAZY
     * =========================
     */
    public static function fixLazyImages($html)
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $dom->loadHTML($html);

        $imgs = $dom->getElementsByTagName('img');

        foreach ($imgs as $img) {

            $lazySrc =
                $img->getAttribute('data-src') ?:
                $img->getAttribute('data-lazy-src') ?:
                $img->getAttribute('data-original') ?:
                $img->getAttribute('data-orig-file') ?:
                $img->getAttribute('data-large_image');

            if ($lazySrc) {
                $img->setAttribute('src', $lazySrc);
            }

            $img->removeAttribute('loading');
            $img->removeAttribute('data-src');
            $img->removeAttribute('data-lazy-src');
            $img->removeAttribute('srcset');
            $img->removeAttribute('data-srcset');
        }

        return $dom->saveHTML();
    }

    /**
     * =========================
     * COMBINAR CSS EXTERNO
     * =========================
     */
    public static function combinarCssExterno($html, $baseUrl)
    {
        preg_match_all(
            '/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i',
            $html,
            $matches
        );

        $combinedCss = "";

        if (!empty($matches[1])) {

            foreach ($matches[1] as $href) {

                $cssUrl = self::toAbsoluteUrl($href, $baseUrl);

                if (!self::isWpContent($cssUrl)) {
                    continue;
                }

                $css = self::getCode($cssUrl);

                $combinedCss .= "\n" . $css;
            }

            $html = preg_replace(
                '/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i',
                '',
                $html
            );
        }

        if (!empty(trim($combinedCss))) {

            $style =
                '<style>' .
                self::cssMinify($combinedCss) .
                '</style>';

            $html = str_replace('</head>', $style . '</head>', $html);
        }

        return $html;
    }

    /**
     * =========================
     * COMBINAR CSS INTERNO
     * =========================
     */
    public static function combinarCssInterno($html)
    {
        preg_match_all('/<style.*?>(.*?)<\/style>/is', $html, $matches);

        $css = implode("\n", $matches[1] ?? []);

        $html = preg_replace('/<style.*?>.*?<\/style>/is', '', $html);

        if (!empty(trim($css))) {

            $style =
                '<style>' .
                self::cssMinify($css) .
                '</style>';

            $html = str_replace('</head>', $style . '</head>', $html);
        }

        return $html;
    }
    /**
     * =========================
     * COMBINAR JS EXTERNO
     * =========================
     */
    public static function combinarJsExterno($html, $baseUrl)
    {
        preg_match_all(
            '/<script[^>]+src=["\']([^"\']+)["\'][^>]*><\/script>/i',
            $html,
            $matches
        );

        $combinedJs = "";

        if (!empty($matches[1])) {

            foreach ($matches[1] as $src) {

                $jsUrl = self::toAbsoluteUrl($src, $baseUrl);

                // SOLO wp-content
                if (!self::isWpContent($jsUrl)) {
                    continue;
                }

                $js = self::getCode($jsUrl);

                if (empty($js)) {
                    continue;
                }

                // mantener orden
                $combinedJs .= "\n;\n" . $js;
            }

            // eliminar scripts externos originales
            $html = preg_replace(
                '/<script[^>]+src=["\'][^"\']+["\'][^>]*><\/script>/i',
                '',
                $html
            );
        }

        if (!empty(trim($combinedJs))) {

            $script =
                '<script>' .
                self::jsMinify($combinedJs) .
                '</script>';

            $html = str_replace('</body>', $script . '</body>', $html);
        }

        return $html;
    }
    /**
     * =========================
     * COMBINAR JS INTERNO
     * =========================
     */
    public static function combinarJsInterno($html)
    {
        preg_match_all(
            '/<script(?![^>]*src=)[^>]*>(.*?)<\/script>/is',
            $html,
            $matches
        );

        $js = implode("\n;\n", $matches[1] ?? []);

        $html = preg_replace(
            '/<script(?![^>]*src=)[^>]*>.*?<\/script>/is',
            '',
            $html
        );

        if (!empty(trim($js))) {

            $script =
                '<script>' .
                self::jsMinify($js) .
                '</script>';

            $html = str_replace('</body>', $script . '</body>', $html);
        }

        return $html;
    }

    /**
     * =========================
     * PROCESAR HTML
     * =========================
     */
    public static function procesingHtml($html, $baseUrl = "", $config = [])
    {
        if (empty($baseUrl)) {
            $baseUrl = home_url();
        }

        $html = self::eliminarWpadminbar($html);

        if ($config[STPA_PAGE_CONFIG::KEY_CSS_EXTERNO]) {
            $html = self::combinarCssExterno($html, $baseUrl);
        }
        if ($config[STPA_PAGE_CONFIG::KEY_CSS_INTERNO]) {
            $html = self::combinarCssInterno($html);
        }
        if ($config[STPA_PAGE_CONFIG::KEY_JS_EXTERNO]) {
            $html = self::combinarJsExterno($html, $baseUrl);
        }
        if ($config[STPA_PAGE_CONFIG::KEY_JS_INTERNO]) {
            $html = self::combinarJsInterno($html);
        }

        $html = self::fixLazyImages($html);

        return self::htmlMinify($html);
    }
}
