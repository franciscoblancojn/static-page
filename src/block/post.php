<?php

class STPA_PAGE_CONFIG
{
    const KEY_CONFIG = STPA_KEY . '_KEY_CONFIG';
    const KEY_ACTIVE = STPA_KEY . '_PAGE_STATIC_ACTIVE';
    const KEY_CSS_EXTERNO = STPA_KEY . '_PAGE_STATIC_CSS_EXTERNO';
    const KEY_CSS_INTERNO = STPA_KEY . '_PAGE_STATIC_CSS_INTERNO';
    const KEY_CSS_PURGE = STPA_KEY . '_PAGE_STATIC_CSS_PURGE';
    const KEY_CSS_FILE = STPA_KEY . '_PAGE_STATIC_CSS_FILE';
    const KEY_CSS_IGNORE_ENABLED = STPA_KEY . '_PAGE_STATIC_CSS_IGNORE';
    const KEY_CSS_IGNORE_LIST = STPA_KEY . '_PAGE_STATIC_CSS_IGNORE_LIST';
    const KEY_JS_EXTERNO = STPA_KEY . '_PAGE_STATIC_JS_EXTERNO';
    const KEY_JS_INTERNO = STPA_KEY . '_PAGE_STATIC_JS_INTERNO';
    const KEY_JS_FILE = STPA_KEY . '_PAGE_STATIC_JS_FILE';
    const KEY_JS_IGNORE_ENABLED = STPA_KEY . '_PAGE_STATIC_JS_IGNORE';
    const KEY_JS_IGNORE_LIST = STPA_KEY . '_PAGE_STATIC_JS_IGNORE_LIST';
    const KEY_HTML = STPA_KEY . '_PAGE_STATIC_HTML';
    const KEY_HTML_FILE = STPA_KEY . '_PAGE_STATIC_HTML_FILE';

    const CONFIG = [
        self::KEY_ACTIVE => "Activar Carga de Pagina Estatica",
        self::KEY_CSS_EXTERNO => "Procesar CSS Externo",
        self::KEY_CSS_INTERNO => "Procesar CSS Interno",
        self::KEY_CSS_PURGE => "Eliminar CSS No Usado",
        self::KEY_CSS_FILE => "Generar CSS Externo",
        self::KEY_CSS_IGNORE_ENABLED => "Ignorar CSS",
        self::KEY_JS_EXTERNO  => "Procesar JS Externo (Beta)",
        self::KEY_JS_INTERNO => "Procesar JS Interno (Beta)",
        self::KEY_JS_FILE => "Generar JS Externo",
        self::KEY_JS_IGNORE_ENABLED => "Ignorar JS",
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
            ['page'],
            'normal',
            'low'
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
        $css_url = home_url('/?' . STPA_KEY . '_ASSET=page-' . $post->ID . '.css');
        $js_url  = home_url('/?' . STPA_KEY . '_ASSET=page-' . $post->ID . '.js');
        $cssIgnoreList = $config[self::KEY_CSS_IGNORE_LIST] ?? [];
        $jsIgnoreList = $config[self::KEY_JS_IGNORE_LIST] ?? [];
        ?>
        <style>
            .stpa-collapsible {
                margin: 8px 0;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fafafa;
            }
            .stpa-collapsible summary {
                padding: 8px 12px;
                cursor: pointer;
                font-weight: 600;
                background: #f0f0f1;
                border-bottom: 1px solid #ddd;
            }
            .stpa-collapsible[open] summary {
                border-bottom: 1px solid #ddd;
            }
            .stpa-collapsible-content {
                padding: 8px 12px;
            }
            .stpa-ignore-items {
                margin-top: 6px;
                margin-left: 16px;
                max-height: 200px;
                overflow-y: auto;
                border: 1px solid #e5e5e5;
                padding: 6px 8px;
                background: #fff;
                border-radius: 3px;
            }
            .stpa-ignore-items label {
                display: block;
                font-size: 11px;
                line-height: 1.6;
                word-break: break-all;
            }
            .stpa-ignore-loading {
                font-size: 12px;
                color: #666;
                margin-left: 16px;
            }
        </style>

        <div class="stpa-field">
            <label>
                <input type="checkbox" name="<?= self::KEY_ACTIVE ?>" value="1" <?= checked($config[self::KEY_ACTIVE] ?? false, '1', false) ?>>
                <?= self::CONFIG[self::KEY_ACTIVE] ?>
            </label>
        </div>

        <details class="stpa-collapsible" <?= self::isCssSectionOpen($config) ? 'open' : '' ?>>
            <summary>Configuración de CSS</summary>
            <div class="stpa-collapsible-content">
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" name="<?= self::KEY_CSS_EXTERNO ?>" value="1" <?= checked($config[self::KEY_CSS_EXTERNO] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_CSS_EXTERNO] ?>
                    </label>
                </div>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" name="<?= self::KEY_CSS_INTERNO ?>" value="1" <?= checked($config[self::KEY_CSS_INTERNO] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_CSS_INTERNO] ?>
                    </label>
                </div>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" name="<?= self::KEY_CSS_PURGE ?>" value="1" <?= checked($config[self::KEY_CSS_PURGE] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_CSS_PURGE] ?>
                    </label>
                </div>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" name="<?= self::KEY_CSS_FILE ?>" value="1" <?= checked($config[self::KEY_CSS_FILE] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_CSS_FILE] ?>
                    </label>
                </div>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" class="stpa-ignore-toggle" data-type="css" name="<?= self::KEY_CSS_IGNORE_ENABLED ?>" value="1" <?= checked($config[self::KEY_CSS_IGNORE_ENABLED] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_CSS_IGNORE_ENABLED] ?>
                    </label>
                </div>
                <div class="stpa-ignore-list" data-type="css" style="<?= ($config[self::KEY_CSS_IGNORE_ENABLED] ?? false) ? '' : 'display:none;' ?>">
                    <input type="hidden" class="stpa-ignore-values" name="<?= self::KEY_CSS_IGNORE_LIST ?>" value='<?= json_encode($cssIgnoreList) ?>'>
                    <div class="stpa-ignore-items" data-type="css"></div>
                    <div class="stpa-ignore-loading" data-type="css" style="display:none;">Cargando archivos CSS...</div>
                </div>
            </div>
        </details>

        <details class="stpa-collapsible" <?= self::isJsSectionOpen($config) ? 'open' : '' ?>>
            <summary>Configuración de JS</summary>
            <div class="stpa-collapsible-content">
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" name="<?= self::KEY_JS_EXTERNO ?>" value="1" <?= checked($config[self::KEY_JS_EXTERNO] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_JS_EXTERNO] ?>
                    </label>
                </div>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" name="<?= self::KEY_JS_INTERNO ?>" value="1" <?= checked($config[self::KEY_JS_INTERNO] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_JS_INTERNO] ?>
                    </label>
                </div>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" name="<?= self::KEY_JS_FILE ?>" value="1" <?= checked($config[self::KEY_JS_FILE] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_JS_FILE] ?>
                    </label>
                </div>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" class="stpa-ignore-toggle" data-type="js" name="<?= self::KEY_JS_IGNORE_ENABLED ?>" value="1" <?= checked($config[self::KEY_JS_IGNORE_ENABLED] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_JS_IGNORE_ENABLED] ?>
                    </label>
                </div>
                <div class="stpa-ignore-list" data-type="js" style="<?= ($config[self::KEY_JS_IGNORE_ENABLED] ?? false) ? '' : 'display:none;' ?>">
                    <input type="hidden" class="stpa-ignore-values" name="<?= self::KEY_JS_IGNORE_LIST ?>" value='<?= json_encode($jsIgnoreList) ?>'>
                    <div class="stpa-ignore-items" data-type="js"></div>
                    <div class="stpa-ignore-loading" data-type="js" style="display:none;">Cargando archivos JS...</div>
                </div>
            </div>
        </details>

        <?php
        require_once STPA_DIR . 'src/js/procesing-html.php';
        ?>

        <div class="content-btn" style="margin-top: 1rem;">
            <div id="btn-onGeneratePaginaEstatica" value="Guardar" class="button button-primary">
                Generar Pagina Estatica y Guardar
            </div>
        </div>
        <div id="<?= STPA_KEY ?>-result"></div>

        <script>
            const <?= STPA_KEY ?>_onLoad = () => {
                const headers = {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": "<?= wp_create_nonce('wp_rest') ?>",
                    "api-key": "<?= STPA_API::getApiKey() ?>"
                }
                const btn = document.getElementById("btn-onGeneratePaginaEstatica")
                const resultContent = document.getElementById("<?= STPA_KEY ?>-result")
                const url = "<?= $url ?>?<?= STPA_KEY . "_DISABLE" ?>=1";

                const onGetConfig = () => {
                    const config = Object.keys(stpa_json_config_keys).reduce((a, c) => {
                        return {
                            ...a,
                            [c]: document.querySelector(`[name='${c}']`)?.checked ?? false
                        }
                    }, {})
                    config['<?= self::KEY_CSS_IGNORE_LIST ?>'] = JSON.parse(
                        document.querySelector(`input[name='<?= self::KEY_CSS_IGNORE_LIST ?>']`)?.value || '[]'
                    )
                    config['<?= self::KEY_JS_IGNORE_LIST ?>'] = JSON.parse(
                        document.querySelector(`input[name='<?= self::KEY_JS_IGNORE_LIST ?>']`)?.value || '[]'
                    )
                    return config
                }

                const loadFileList = async (type) => {
                    const container = document.querySelector(`.stpa-ignore-items[data-type="${type}"]`)
                    const loading = document.querySelector(`.stpa-ignore-loading[data-type="${type}"]`)
                    const hiddenInput = document.querySelector(`input.stpa-ignore-values[name^="<?= STPA_KEY ?>_PAGE_STATIC_${type.toUpperCase()}_IGNORE_LIST"]`)
                    const currentIgnoreList = JSON.parse(hiddenInput?.value || '[]')
                    const toggle = document.querySelector(`.stpa-ignore-toggle[data-type="${type}"]`)

                    if (!toggle?.checked) return
                    if (container.children.length > 0) return

                    loading.style.display = ''
                    try {
                        const html = await getCode(url)
                        const parser = new DOMParser()
                        const doc = parser.parseFromString(html, "text/html")
                        let files = []
                        if (type === 'css') {
                            const links = doc.querySelectorAll('link[rel="stylesheet"]')
                            links.forEach(link => {
                                const href = link.getAttribute('href')
                                if (href) files.push(href)
                            })
                        } else {
                            const scripts = doc.querySelectorAll('script[src]')
                            scripts.forEach(script => {
                                const src = script.getAttribute('src')
                                if (src) files.push(src)
                            })
                        }
                        container.innerHTML = ''
                        files.forEach(file => {
                            const label = document.createElement('label')
                            const cb = document.createElement('input')
                            cb.type = 'checkbox'
                            cb.checked = currentIgnoreList.includes(file)
                            cb.addEventListener('change', () => {
                                let list = JSON.parse(hiddenInput.value || '[]')
                                if (cb.checked) {
                                    if (!list.includes(file)) list.push(file)
                                } else {
                                    list = list.filter(f => f !== file)
                                }
                                hiddenInput.value = JSON.stringify(list)
                            })
                            label.appendChild(cb)
                            label.appendChild(document.createTextNode(' ' + file))
                            container.appendChild(label)
                        })
                    } catch (e) {
                        container.innerHTML = '<span style="color:red;">Error al cargar archivos</span>'
                    }
                    loading.style.display = 'none'
                }

                document.querySelectorAll('.stpa-ignore-toggle').forEach(toggle => {
                    toggle.addEventListener('change', () => {
                        const type = toggle.dataset.type
                        const list = document.querySelector(`.stpa-ignore-list[data-type="${type}"]`)
                        if (toggle.checked) {
                            list.style.display = ''
                            loadFileList(type)
                        } else {
                            list.style.display = 'none'
                            const container = document.querySelector(`.stpa-ignore-items[data-type="${type}"]`)
                            container.innerHTML = ''
                        }
                    })
                    if (toggle.checked) {
                        loadFileList(toggle.dataset.type)
                    }
                })

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

                        const html = await getCode(url);
                        const cssHref = config?.<?= STPA_KEY ?>_PAGE_STATIC_CSS_FILE ? "<?= $css_url ?>" : null;
                        const jsHref = config?.<?= STPA_KEY ?>_PAGE_STATIC_JS_FILE ? "<?= $js_url ?>" : null;
                        const { html: finalHtml, css: finalCss, js: finalJs } = await procesingHtml(html, url, config, cssHref, jsHref);
                        const response = await fetch("/wp-json/<?= STPA_KEY ?>/html/<?= $post->ID ?>", {
                            method: "POST",
                            headers,
                            body: JSON.stringify({
                                html: finalHtml,
                                css: finalCss,
                                js: finalJs
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

    private static function isCssSectionOpen($config)
    {
        return ($config[self::KEY_CSS_EXTERNO] ?? false) === '1'
            || ($config[self::KEY_CSS_INTERNO] ?? false) === '1'
            || ($config[self::KEY_CSS_PURGE] ?? false) === '1'
            || ($config[self::KEY_CSS_FILE] ?? false) === '1'
            || ($config[self::KEY_CSS_IGNORE_ENABLED] ?? false) === '1';
    }

    private static function isJsSectionOpen($config)
    {
        return ($config[self::KEY_JS_EXTERNO] ?? false) === '1'
            || ($config[self::KEY_JS_INTERNO] ?? false) === '1'
            || ($config[self::KEY_JS_FILE] ?? false) === '1'
            || ($config[self::KEY_JS_IGNORE_ENABLED] ?? false) === '1';
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
        $cssIgnoreList = isset($_POST[self::KEY_CSS_IGNORE_LIST]) ? json_decode(stripslashes($_POST[self::KEY_CSS_IGNORE_LIST]), true) : [];
        $jsIgnoreList = isset($_POST[self::KEY_JS_IGNORE_LIST]) ? json_decode(stripslashes($_POST[self::KEY_JS_IGNORE_LIST]), true) : [];
        $config[self::KEY_CSS_IGNORE_LIST] = is_array($cssIgnoreList) ? $cssIgnoreList : [];
        $config[self::KEY_JS_IGNORE_LIST] = is_array($jsIgnoreList) ? $jsIgnoreList : [];
        update_post_meta(
            $post_id,
            self::KEY_CONFIG,
            $config
        );
    }
}

STPA_PAGE_CONFIG::init();
