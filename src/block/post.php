<?php

class STPA_PAGE_CONFIG
{
    const KEY_CONFIG = STPA_KEY . '_KEY_CONFIG';
    const KEY_ACTIVE = STPA_KEY . '_PAGE_STATIC_ACTIVE';
    const KEY_CSS_EXTERNO = STPA_KEY . '_PAGE_STATIC_CSS_EXTERNO';
    const KEY_CSS_INTERNO = STPA_KEY . '_PAGE_STATIC_CSS_INTERNO';
    const KEY_CSS_PURGE = STPA_KEY . '_PAGE_STATIC_CSS_PURGE';
    const KEY_JS_EXTERNO = STPA_KEY . '_PAGE_STATIC_JS_EXTERNO';
    const KEY_JS_INTERNO = STPA_KEY . '_PAGE_STATIC_JS_INTERNO';
    const KEY_HTML = STPA_KEY . '_PAGE_STATIC_HTML';
    const KEY_HTML_FILE = STPA_KEY . '_PAGE_STATIC_HTML_FILE';

    const CONFIG = [
        self::KEY_ACTIVE => "Activar Carga de Pagina Estatica",
        self::KEY_CSS_EXTERNO => "Procesar CSS Externo",
        self::KEY_CSS_INTERNO => "Procesar CSS Interno",
        self::KEY_CSS_PURGE => "Eliminar CSS No Usado",
        self::KEY_JS_EXTERNO  => "Procesar JS Externo (Beta)",
        self::KEY_JS_INTERNO => "Procesar JS Interno (Beta)",
    ];

    public static function init()
    {
        add_action('add_meta_boxes', [self::class, 'addMetaBox']);
        add_action('save_post', [self::class, 'save']);
    }

    /**
     * Registrar Postbox
     */
    public static function addMetaBox()
    {
        add_meta_box(
            STPA_KEY . '_page_config',
            'Configuración Pagina Estatica',
            [self::class, 'render'],
            ['page'], // tipos de post
            'side',
            'high'
        );
    }

    /**
     * Render del Postbox
     */
    public static function render($post)
    {
        wp_nonce_field(STPA_KEY . '_page_config_nonce', STPA_KEY . '_page_config_nonce');

        $config = get_post_meta($post->ID, self::KEY_CONFIG, true);
        $url = get_permalink($post->ID);
        $upload_dir = wp_upload_dir();
        $css_url = $upload_dir['baseurl'] . '/' . STPA_KEY . '/page-' . $post->ID . '.css';
        foreach (
            self::CONFIG as $key => $value
        ) {
?>
            <div class="stpa-field">
                <label>
                    <input
                        type="checkbox"
                        name=<?= $key ?>
                        value="1"
                        <?= checked($config[$key] ?? false, '1', false) ?>>

                    <?= $value ?>
                </label>
            </div>
        <?php
        }
        require_once STPA_DIR . 'src/js/procesing-html.php';
        ?>

        <div
            class="content-btn"
            style="margin-top: 1rem;">
            <div
                id="btn-onGeneratePaginaEstatica"
                value="Guardar"
                class="button button-primary">
                Generar Pagina Estatica y Guardar
            </div>
        </div>
        <div id="<?= STPA_KEY ?>-result">

        </div>

        <script>
            const <?= STPA_KEY ?>_onLoad = () => {
                const headers = {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": "<?= wp_create_nonce('wp_rest') ?>",
                    "api-key": "<?= STPA_API::getApiKey() ?>"
                }
                const btn = document.getElementById("btn-onGeneratePaginaEstatica")
                const resultContent = document.getElementById("<?= STPA_KEY ?>-result")
                const onGetConfig = () => {
                    return Object.keys(stpa_json_config_keys).reduce((a, c) => {
                        return {
                            ...a,
                            [c]: document.querySelector(`[name='${c}']`)?.checked ?? false
                        }
                    }, {})
                }
                const onGeneratePaginaEstatica = async () => {
                    try {
                        btn.classList.add("loader")
                        btn.textContent = "Generando..."
                        const config = onGetConfig();
                        const response_config = await fetch("/wp-json/<?= STPA_KEY ?>/post-config/<?= $post->ID ?>", {
                            method: "POST",
                            headers,
                            body: JSON.stringify({
                                config
                            })
                        });
                        const data_config = await response_config.json();
                        if (!data_config.success) {
                            throw new Error(data_config?.message ?? "Error al guardar")
                        }

                        const url = "<?= $url ?>?<?= STPA_KEY . "_DISABLE" ?>=1";
                        const html = await getCode(url);
                        const { html: finalHtml, css: finalCss } = await procesingHtml(html, url, config, "<?= $css_url ?>");
                        const response = await fetch("/wp-json/<?= STPA_KEY ?>/html/<?= $post->ID ?>", {
                            method: "POST",
                            headers,
                            body: JSON.stringify({
                                html: finalHtml,
                                css: finalCss
                            })
                        });
                        const data = await response.json();
                        if (!data.success) {
                            throw new Error(data?.message ?? "Error al guardar")
                        }
                        resultContent.textContent = data?.message ?? "Guardado ✓"

                        btn.textContent = "Guardando..."
                        setTimeout(() => {
                            window.location.reload()
                        }, 500);
                    } catch (error) {
                        resultContent.textContent = error.message
                        btn.textContent = "Generar Pagina Estatica"
                        btn.classList.remove("loader")
                    }
                }
                btn.addEventListener("click", onGeneratePaginaEstatica)
            }
            <?= STPA_KEY ?>_onLoad();
        </script>
<?php
    }

    /**
     * Guardar datos
     */
    public static function save($post_id)
    {
        if (
            !isset($_POST[STPA_KEY . '_page_config_nonce']) ||
            !wp_verify_nonce($_POST[STPA_KEY . '_page_config_nonce'], STPA_KEY . '_page_config_nonce')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $config = [];
        foreach (
            self::CONFIG as $key => $value
        ) {
            $enabled = isset($_POST[$key]) ? '1' : '0';
            $config[$key] = $enabled;
        }
        update_post_meta(
            $post_id,
            self::KEY_CONFIG,
            $config
        );
    }
}

STPA_PAGE_CONFIG::init();
