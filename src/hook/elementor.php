<?php

add_action('elementor/editor/after_enqueue_scripts', function () {
    $post_id = get_the_ID();
    if (!$post_id) return;

    $config = get_post_meta($post_id, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (!is_array($config)) return;

    $active = $config[STPA_PAGE_CONFIG::KEY_ACTIVE] ?? false;
    if ($active !== '1' && $active !== true && $active !== 1) return;

    $url = get_permalink($post_id);
    if (!$url) return;
    $url .= '?' . STPA_KEY . '_DISABLE=1';

    $css_url = home_url('/?' . STPA_KEY . '_ASSET=page-' . $post_id . '.css');
    $js_url = home_url('/?' . STPA_KEY . '_ASSET=page-' . $post_id . '.js');

    $bool_keys = [
        STPA_PAGE_CONFIG::KEY_ACTIVE,
        STPA_PAGE_CONFIG::KEY_CSS_EXTERNO,
        STPA_PAGE_CONFIG::KEY_CSS_INTERNO,
        STPA_PAGE_CONFIG::KEY_CSS_PURGE,
        STPA_PAGE_CONFIG::KEY_CSS_FILE,
        STPA_PAGE_CONFIG::KEY_CSS_IGNORE_ENABLED,
        STPA_PAGE_CONFIG::KEY_JS_EXTERNO,
        STPA_PAGE_CONFIG::KEY_JS_INTERNO,
        STPA_PAGE_CONFIG::KEY_JS_FILE,
        STPA_PAGE_CONFIG::KEY_JS_IGNORE_ENABLED,
    ];

    $js_config = [];
    foreach ($config as $key => $value) {
        if (in_array($key, $bool_keys, true)) {
            $js_config[$key] = $value === '1' || $value === true || $value === 1;
        } else {
            $js_config[$key] = $value;
        }
    }

    require_once STPA_DIR . 'src/js/procesing-html.php';
?>
    <script>
        (function() {
            const CONFIG = <?= json_encode($js_config) ?>;
            const URL = <?= json_encode($url) ?>;
            const POST_ID = <?= (int) $post_id ?>;
            const CSS_URL = <?= json_encode($css_url) ?>;
            const JS_URL = <?= json_encode($js_url) ?>;
            const HEADERS = {
                "Content-Type": "application/json",
                "X-WP-Nonce": <?= json_encode(wp_create_nonce('wp_rest')) ?>,
                "api-key": <?= json_encode(STPA_API::getApiKey()) ?>
            };

            function stpaNotify(message, type) {
                var colors = {
                    success: '#00a32a',
                    error: '#d63638',
                    info: '#2271b1',
                    warning: '#dba617'
                };
                var borderColor = colors[type] || colors.info;
                var $notice = document.createElement('div');
                $notice.textContent = message;
                Object.assign($notice.style, {
                    position: 'fixed',
                    top: '12px',
                    right: '12px',
                    zIndex: 999999,
                    background: '#fff',
                    borderLeft: '4px solid ' + borderColor,
                    padding: '10px 16px',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
                    color: "#424242ff",
                    fontSize: '13px',
                    fontFamily: '-apple-system,BlinkMacSystemFont,sans-serif',
                    borderRadius: '3px',
                    maxWidth: '400px',
                    lineHeight: '1.4'
                });
                document.body.appendChild($notice);
                setTimeout(function() {
                    $notice.style.transition = 'opacity 0.5s';
                    $notice.style.opacity = '0';
                    setTimeout(function() {
                        $notice.remove();
                    }, 500);
                }, 4000);
            }

            async function regenerateStaticPage() {
                try {
                    const html = await getCode(URL);
                    const cssHref = CONFIG['<?= STPA_PAGE_CONFIG::KEY_CSS_FILE ?>'] ? CSS_URL : null;
                    const jsHref = CONFIG['<?= STPA_PAGE_CONFIG::KEY_JS_FILE ?>'] ? JS_URL : null;
                    const result = await procesingHtml(html, URL, CONFIG, cssHref, jsHref);
                    const bodyData = {
                        html: result.html
                    };
                    if (CONFIG['<?= STPA_PAGE_CONFIG::KEY_CSS_FILE ?>'] && result.css) bodyData.css = result.css;
                    if (CONFIG['<?= STPA_PAGE_CONFIG::KEY_JS_FILE ?>'] && result.js) bodyData.js = result.js;
                    await fetch("/wp-json/<?= STPA_KEY ?>/html/" + POST_ID, {
                        method: "POST",
                        headers: HEADERS,
                        body: JSON.stringify(bodyData)
                    });
                    stpaNotify('Static Page Generada correctamente', 'success');
                } catch (e) {
                    stpaNotify('Error al generar Static Page: ' + e.message, 'error');
                    console.error("STPA: Error regenerando pagina estatica:", e);
                }
            }

            const waitForElementor = setInterval(function() {
                if (typeof elementor !== 'undefined' && elementor.channels && elementor.channels.editor) {
                    clearInterval(waitForElementor);
                    elementor.channels.editor.on('saved', function() {
                        stpaNotify('Procesando Static Page...', 'info');
                        setTimeout(regenerateStaticPage, 3000);
                    });
                }
            }, 500);
        })();
    </script>
<?php
});
