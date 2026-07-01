<?php

function STPA_get_output_dir($post_id = null)
{
    $config = get_option(STPA_CONFIG, []);
    $output_type = $config['output_type'] ?? 'default';
    $output_dir = !empty($config['output_dir']) ? sanitize_title($config['output_dir']) : STPA_KEY;

    if ($post_id) {
        $post = get_post($post_id);
    }

    switch ($output_type) {
        case 'slug':
            if ($post) {
                $slug = $post->post_name;
                $subdir = "page-{$post_id}";
            } else {
                $subdir = "page-{$post_id}";
            }
            break;

        case 'id_group':
            $group = floor($post_id / 100) * 100;
            $subdir = "group-{$group}";
            break;

        case 'parent':
            if ($post && $post->post_parent) {
                $parent = get_post($post->post_parent);
                $subdir = $parent ? $parent->post_name : 'sin-padre';
            } else {
                $subdir = 'sin-padre';
            }
            break;

        default:
            $subdir = '';
            break;
    }

    $upload_dir = wp_upload_dir();
    if ($subdir) {
        return $upload_dir['basedir'] . '/' . $output_dir . '/' . $subdir;
    }
    return $upload_dir['basedir'] . '/' . $output_dir;
}

function STPA_get_global_sections_dir()
{
    $upload_dir = wp_upload_dir();
    return $upload_dir['basedir'] . '/' . STPA_KEY . '/section-global';
}

function STPA_get_global_sections_url()
{
    $upload_dir = wp_upload_dir();
    return $upload_dir['baseurl'] . '/' . STPA_KEY . '/section-global';
}

function STPA_global_section_slug($key)
{
    $slug = sanitize_title($key);
    return $slug ?: 'section';
}

/**
 * Traduce un selector CSS simple (tag, tag#id, tag.clase, #id, .clase) a XPath.
 * No soporta combinadores (espacio, >, etc.) — cubre los casos de uso de Secciones Globales.
 */
function STPA_css_selector_to_xpath($selector)
{
    $selector = trim($selector);
    if ($selector === '') return null;
    if (!preg_match('/^([a-zA-Z][a-zA-Z0-9]*)?((?:[#.][a-zA-Z0-9_-]+)*)$/', $selector, $m)) {
        return null;
    }
    $tag = ($m[1] ?? '') !== '' ? $m[1] : '*';
    $rest = $m[2] ?? '';
    preg_match_all('/([#.])([a-zA-Z0-9_-]+)/', $rest, $parts, PREG_SET_ORDER);

    $conditions = [];
    foreach ($parts as $p) {
        if ($p[1] === '#') {
            $conditions[] = "@id='" . $p[2] . "'";
        } else {
            $conditions[] = "contains(concat(' ', normalize-space(@class), ' '), ' " . $p[2] . " ')";
        }
    }

    $xpath = '//' . $tag;
    if ($conditions) {
        $xpath .= '[' . implode(' and ', $conditions) . ']';
    }
    return $xpath;
}

/**
 * Busca el primer nodo que coincide con el selector dentro de un HTML.
 * Devuelve ['dom' => DOMDocument, 'node' => DOMNode] o null si no hay coincidencia.
 */
function STPA_dom_find_first($html, $selector)
{
    $xpathQuery = STPA_css_selector_to_xpath($selector);
    if (!$xpathQuery) return null;

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query($xpathQuery);
    if (!$nodes || $nodes->length === 0) return null;

    return ['dom' => $dom, 'node' => $nodes->item(0)];
}

/**
 * Descarga una URL y extrae el HTML del primer elemento que coincide con el selector.
 */
function STPA_extract_section_html($url, $selector)
{
    $response = wp_remote_get($url, [
        'timeout' => 60,
        'sslverify' => false,
        'headers' => ['Cache-Control' => 'no-cache'],
    ]);
    if (is_wp_error($response)) {
        throw new Exception('Error al obtener la página: ' . $response->get_error_message());
    }

    $html = wp_remote_retrieve_body($response);
    $found = STPA_dom_find_first($html, $selector);
    if (!$found) {
        throw new Exception('No se encontró ningún elemento para el selector: ' . $selector);
    }

    return $found['dom']->saveHTML($found['node']);
}

/**
 * Reemplaza en $html el nodo que coincide con $selector por $newHtml.
 * Devuelve el HTML completo actualizado, o null si no hubo coincidencia.
 */
function STPA_replace_selector_in_html($html, $selector, $newHtml)
{
    $found = STPA_dom_find_first($html, $selector);
    if (!$found) return null;

    $dom = $found['dom'];
    $node = $found['node'];

    $fragment = new DOMDocument();
    libxml_use_internal_errors(true);
    $fragment->loadHTML('<div id="stpa-fragment-wrap">' . $newHtml . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    $wrap = $fragment->getElementById('stpa-fragment-wrap');
    if (!$wrap) return null;

    foreach ($wrap->childNodes as $child) {
        $imported = $dom->importNode($child, true);
        $node->parentNode->insertBefore($imported, $node);
    }
    $node->parentNode->removeChild($node);

    return $dom->saveHTML();
}

/**
 * Aplica TODAS las Secciones Globales registradas sobre un HTML ya generado,
 * en un solo pase de DOM. Se usa al servir el archivo estático (template_redirect)
 * para que header/footer siempre reflejen la última versión guardada de la
 * Sección Global, sin depender de que cada página haya sido regenerada.
 */
function STPA_apply_global_sections_to_html($html)
{
    $sections = STPA_GLOBAL_SECTIONS_DATA::getAll();
    if (empty($sections)) return $html;

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $changed = false;

    foreach ($sections as $entry) {
        $selector = $entry['selector'] ?? '';
        $file = $entry['file'] ?? '';
        if (!$selector || !$file || !file_exists($file)) continue;

        $xpathQuery = STPA_css_selector_to_xpath($selector);
        if (!$xpathQuery) continue;

        $nodes = $xpath->query($xpathQuery);
        if (!$nodes || $nodes->length === 0) continue;

        $newHtml = file_get_contents($file);

        $fragment = new DOMDocument();
        libxml_use_internal_errors(true);
        $fragment->loadHTML('<div id="stpa-fragment-wrap">' . $newHtml . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        $wrap = $fragment->getElementById('stpa-fragment-wrap');
        if (!$wrap) continue;

        // materializar la lista antes de mutar el DOM (DOMNodeList es "live")
        $matches = [];
        foreach ($nodes as $node) $matches[] = $node;

        foreach ($matches as $node) {
            foreach ($wrap->childNodes as $child) {
                $node->parentNode->insertBefore($dom->importNode($child, true), $node);
            }
            $node->parentNode->removeChild($node);
            $changed = true;
        }
    }

    return $changed ? $dom->saveHTML() : $html;
}

/**
 * Aplica el HTML actualizado de una Sección Global a todas las páginas
 * que ya tienen un archivo estático generado, sin necesidad de regenerarlas.
 */
function STPA_sweep_global_section_update($selector, $newHtml)
{
    $updated = [];

    $pages = get_posts([
        'post_type' => 'page',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [[
            'key' => STPA_PAGE_CONFIG::KEY_HTML_FILE,
            'compare' => 'EXISTS',
        ]],
    ]);

    foreach ($pages as $page) {
        $file = get_post_meta($page->ID, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
        if (!$file || !file_exists($file)) continue;

        $html = file_get_contents($file);
        $replaced = STPA_replace_selector_in_html($html, $selector, $newHtml);
        if ($replaced !== null) {
            file_put_contents($file, $replaced);
            $updated[] = $page->ID;
        }
    }

    return $updated;
}
