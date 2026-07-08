<?php

/**
 * Elementos HTML5 sin etiqueta de cierre: si una clave de sección global
 * apunta a uno de estos tags, se tratan como auto-cerrados aunque no
 * terminen en "/>".
 */
function STPA_void_html_tags()
{
    return ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];
}

/**
 * Dado el offset de una etiqueta de apertura, busca el offset donde
 * termina su etiqueta de cierre correspondiente, contando el anidamiento
 * de tags con el mismo nombre (evita cortar en el primer </tag> anidado).
 * Devuelve false si no se encuentra un cierre balanceado.
 */
function STPA_find_matching_tag_end($html, $tag, $offset)
{
    $len = strlen($html);
    $openEnd = strpos($html, '>', $offset);
    if ($openEnd === false) {
        return false;
    }

    if ($html[$openEnd - 1] === '/' || in_array(strtolower($tag), STPA_void_html_tags(), true)) {
        return $openEnd + 1;
    }

    $tagLen = strlen($tag);
    $depth = 1;
    $pos = $openEnd + 1;

    while ($pos < $len) {
        $nextTagPos = strpos($html, '<', $pos);
        if ($nextTagPos === false) {
            return false;
        }

        $isClosing = isset($html[$nextTagPos + 1]) && $html[$nextTagPos + 1] === '/';
        $nameStart = $isClosing ? $nextTagPos + 2 : $nextTagPos + 1;

        if (strtolower(substr($html, $nameStart, $tagLen)) !== strtolower($tag)) {
            $pos = $nextTagPos + 1;
            continue;
        }

        $charAfter = substr($html, $nameStart + $tagLen, 1);
        $isBoundary = $charAfter === '' || $charAfter === '>' || $charAfter === '/' || ctype_space($charAfter);
        if (!$isBoundary) {
            $pos = $nextTagPos + 1;
            continue;
        }

        $tagEnd = strpos($html, '>', $nextTagPos);
        if ($tagEnd === false) {
            return false;
        }

        if ($isClosing) {
            $depth--;
        } elseif ($html[$tagEnd - 1] !== '/') {
            $depth++;
        }

        $pos = $tagEnd + 1;

        if ($depth === 0) {
            return $pos;
        }
    }

    return false;
}

/**
 * Encuentra, para una clave de sección global (#id, .clase o tag), todas
 * las coincidencias de su elemento completo (apertura + contenido anidado
 * + cierre) dentro del HTML. Devuelve una lista de ['start', 'end'].
 */
function STPA_find_global_section_matches($html, $key)
{
    if (strpos($key, '#') === 0) {
        $needle = preg_quote(substr($key, 1), '/');
        $openPattern = '/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*\sid\s*=\s*["\']' . $needle . '["\'][^>]*>/';
    } elseif (strpos($key, '.') === 0) {
        $needle = preg_quote(substr($key, 1), '/');
        $openPattern = '/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*\sclass\s*=\s*["\'][^"\']*\b' . $needle . '\b[^"\']*["\'][^>]*>/';
    } else {
        $needle = preg_quote($key, '/');
        $openPattern = '/<(' . $needle . ')\b[^>]*>/';
    }

    if (!preg_match_all($openPattern, $html, $opens, PREG_OFFSET_CAPTURE)) {
        return [];
    }

    $matches = [];
    foreach ($opens[0] as $i => $openMatch) {
        $start = $openMatch[1];
        $tagName = $opens[1][$i][0];
        $end = STPA_find_matching_tag_end($html, $tagName, $start);
        if ($end === false) {
            continue;
        }
        $matches[] = ['start' => $start, 'end' => $end];
    }

    return $matches;
}

function STPA_replace_global_sections($html)
{
    $dir = STPA_get_global_sections_dir();

    if (!is_dir($dir)) {
        return $html;
    }

    $files = glob($dir . '/*.html') ?: [];
    $hidden = glob($dir . '/.*.html') ?: [];
    $files = array_merge($files, $hidden);
    if (empty($files)) {
        return $html;
    }

    $candidates = [];

    foreach ($files as $file) {
        $key = basename($file, '.html');
        $sectionContent = file_get_contents($file);
        if ($sectionContent === false || trim($sectionContent) === '') {
            continue;
        }

        $matches = STPA_find_global_section_matches($html, $key);
        if (empty($matches)) {
            continue;
        }

        // Un #id debe ser único en el documento: si aparece más de una vez
        // la página tiene contenido duplicado, así que solo se reemplaza la
        // primera coincidencia y se deja constancia en el log.
        if (strpos($key, '#') === 0 && count($matches) > 1) {
            if (class_exists('\franciscoblancojn\wordpress_utils\FWUSystemLog')) {
                \franciscoblancojn\wordpress_utils\FWUSystemLog::add(STPA_KEY, [
                    'type' => 'global_section_duplicate_id',
                    'key' => $key,
                    'url' => home_url(add_query_arg([], null)),
                    'matches' => count($matches),
                ]);
            }
            $matches = [$matches[0]];
        }

        foreach ($matches as $match) {
            $candidates[] = [
                'start' => $match['start'],
                'end' => $match['end'],
                'replace' => $sectionContent,
            ];
        }
    }

    if (empty($candidates)) {
        return $html;
    }

    // Si dos coincidencias se solapan (p. ej. una anidada dentro de otra),
    // se prioriza la de mayor tamaño y se descarta la más pequeña.
    usort($candidates, function ($a, $b) {
        return ($b['end'] - $b['start']) - ($a['end'] - $a['start']);
    });

    $accepted = [];
    foreach ($candidates as $candidate) {
        $overlaps = false;
        foreach ($accepted as $range) {
            if ($candidate['start'] < $range['end'] && $candidate['end'] > $range['start']) {
                $overlaps = true;
                break;
            }
        }
        if (!$overlaps) {
            $accepted[] = $candidate;
        }
    }

    if (empty($accepted)) {
        return $html;
    }

    // Se reemplaza de atrás hacia adelante para que los offsets ya calculados
    // sigan siendo válidos tras cada substr_replace.
    usort($accepted, function ($a, $b) {
        return $b['start'] - $a['start'];
    });

    foreach ($accepted as $range) {
        $html = substr_replace($html, $range['replace'], $range['start'], $range['end'] - $range['start']);
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
