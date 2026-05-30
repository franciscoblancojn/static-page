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
    const KEY_CSS_EXTERNO_PROCESS_LIST = STPA_KEY . '_PAGE_STATIC_CSS_EXTERNO_PROCESS';
    const KEY_JS_EXTERNO = STPA_KEY . '_PAGE_STATIC_JS_EXTERNO';
    const KEY_JS_INTERNO = STPA_KEY . '_PAGE_STATIC_JS_INTERNO';
    const KEY_JS_FILE = STPA_KEY . '_PAGE_STATIC_JS_FILE';
    const KEY_JS_IGNORE_ENABLED = STPA_KEY . '_PAGE_STATIC_JS_IGNORE';
    const KEY_JS_IGNORE_LIST = STPA_KEY . '_PAGE_STATIC_JS_IGNORE_LIST';
    const KEY_JS_EXTERNO_PROCESS_LIST = STPA_KEY . '_PAGE_STATIC_JS_EXTERNO_PROCESS';
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
        $css_url = home_url('/?' . STPA_KEY . '_ASSET=page-' . $post->ID . '.css');
        $js_url  = home_url('/?' . STPA_KEY . '_ASSET=page-' . $post->ID . '.js');
        $cssIgnoreList = $config[self::KEY_CSS_IGNORE_LIST] ?? [];
        $jsIgnoreList = $config[self::KEY_JS_IGNORE_LIST] ?? [];
        $cssProcessList = $config[self::KEY_CSS_EXTERNO_PROCESS_LIST] ?? [];
        $jsProcessList = $config[self::KEY_JS_EXTERNO_PROCESS_LIST] ?? [];
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
                /* max-height: 200px; */
                overflow-y: auto;
                border: 1px solid #e5e5e5;
                padding: 6px 8px;
                background: #fff;
                border-radius: 3px;
            }
            .stpa-ignore-items label {
                display: block;
                line-height: 1.6;
                word-break: break-all;
            }
            .stpa-ignore-loading {
                font-size: 12px;
                color: #666;
                margin-left: 16px;
            }
            .stpa-ignore-group {
                margin-bottom: 6px;
                padding: 4px 6px;
                background: #f5f5f5;
                border-radius: 3px;
            }
            .stpa-ignore-group-header {
                display: flex;
                align-items: center;
                gap: 6px;
                font-weight: 600;
                font-size: 12px;
                padding: 2px 0;
                border-bottom: 1px solid #ddd;
                margin-bottom: 4px;
            }
            .stpa-ignore-group-header input[type="checkbox"] {
                margin: 0;
            }
            .stpa-ignore-group-items {
                padding-left: 8px;
            }
            .stpa-ignore-group-items label {
                display: block;
                line-height: 1.6;
                word-break: break-all;
            }
            .stpa-list-collapse {
                margin: 6px 0 6px 16px;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
                background: #fff;
            }
            .stpa-list-collapse summary {
                padding: 4px 8px;
                cursor: pointer;
                font-size: 11px;
                font-weight: 600;
                color: #555;
                background: #f7f7f7;
                border-bottom: 1px solid #e5e5e5;
                user-select: none;
            }
            .stpa-list-collapse[open] summary {
                border-bottom: 1px solid #e5e5e5;
            }
            .stpa-list-collapse .stpa-list-content {
                padding: 4px 6px;
            }
        </style>

        <div class="stpa-field">
            <label>
                <input type="checkbox" name="<?= self::KEY_ACTIVE ?>" value="1" <?= checked($config[self::KEY_ACTIVE] ?? false, '1', false) ?>>
                <?= self::CONFIG[self::KEY_ACTIVE] ?>
            </label>
        </div>

        <details class="stpa-collapsible" open>
            <summary>Configuración de CSS</summary>
            <div class="stpa-collapsible-content">
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" class="stpa-ignore-toggle" data-type="css" name="<?= self::KEY_CSS_IGNORE_ENABLED ?>" value="1" <?= checked($config[self::KEY_CSS_IGNORE_ENABLED] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_CSS_IGNORE_ENABLED] ?>
                    </label>
                </div>
                <details class="stpa-list-collapse" data-list-type="ignore" data-type="css" <?= ($config[self::KEY_CSS_IGNORE_ENABLED] ?? false) ? 'open' : '' ?>>
                    <summary>Archivos a ignorar</summary>
                    <div class="stpa-list-content">
                        <input type="hidden" class="stpa-ignore-values" name="<?= self::KEY_CSS_IGNORE_LIST ?>" value='<?= json_encode($cssIgnoreList) ?>'>
                        <div class="stpa-ignore-items" data-type="css"></div>
                        <div class="stpa-ignore-loading" data-type="css" style="display:none;">Cargando archivos CSS...</div>
                    </div>
                </details>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" class="stpa-process-toggle" data-type="css" name="<?= self::KEY_CSS_EXTERNO ?>" value="1" <?= checked($config[self::KEY_CSS_EXTERNO] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_CSS_EXTERNO] ?>
                    </label>
                </div>
                <details class="stpa-list-collapse" data-list-type="process" data-type="css" <?= ($config[self::KEY_CSS_EXTERNO] ?? false) ? 'open' : '' ?>>
                    <summary>Archivos a procesar</summary>
                    <div class="stpa-list-content">
                        <input type="hidden" class="stpa-process-values" name="<?= self::KEY_CSS_EXTERNO_PROCESS_LIST ?>" value='<?= json_encode($cssProcessList) ?>'>
                        <div class="stpa-process-items" data-type="css"></div>
                        <div class="stpa-process-loading" data-type="css" style="display:none;">Cargando archivos CSS disponibles...</div>
                    </div>
                </details>
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
            </div>
        </details>

        <details class="stpa-collapsible" open>
            <summary>Configuración de JS</summary>
            <div class="stpa-collapsible-content">
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" class="stpa-ignore-toggle" data-type="js" name="<?= self::KEY_JS_IGNORE_ENABLED ?>" value="1" <?= checked($config[self::KEY_JS_IGNORE_ENABLED] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_JS_IGNORE_ENABLED] ?>
                    </label>
                </div>
                <details class="stpa-list-collapse" data-list-type="ignore" data-type="js" <?= ($config[self::KEY_JS_IGNORE_ENABLED] ?? false) ? 'open' : '' ?>>
                    <summary>Archivos a ignorar</summary>
                    <div class="stpa-list-content">
                        <input type="hidden" class="stpa-ignore-values" name="<?= self::KEY_JS_IGNORE_LIST ?>" value='<?= json_encode($jsIgnoreList) ?>'>
                        <div class="stpa-ignore-items" data-type="js"></div>
                        <div class="stpa-ignore-loading" data-type="js" style="display:none;">Cargando archivos JS...</div>
                    </div>
                </details>
                <div class="stpa-field">
                    <label>
                        <input type="checkbox" class="stpa-process-toggle" data-type="js" name="<?= self::KEY_JS_EXTERNO ?>" value="1" <?= checked($config[self::KEY_JS_EXTERNO] ?? false, '1', false) ?>>
                        <?= self::CONFIG[self::KEY_JS_EXTERNO] ?>
                    </label>
                </div>
                <details class="stpa-list-collapse" data-list-type="process" data-type="js" <?= ($config[self::KEY_JS_EXTERNO] ?? false) ? 'open' : '' ?>>
                    <summary>Archivos a procesar</summary>
                    <div class="stpa-list-content">
                        <input type="hidden" class="stpa-process-values" name="<?= self::KEY_JS_EXTERNO_PROCESS_LIST ?>" value='<?= json_encode($jsProcessList) ?>'>
                        <div class="stpa-process-items" data-type="js"></div>
                        <div class="stpa-process-loading" data-type="js" style="display:none;">Cargando archivos JS disponibles...</div>
                    </div>
                </details>
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
                    config['<?= self::KEY_CSS_EXTERNO_PROCESS_LIST ?>'] = JSON.parse(
                        document.querySelector(`input[name='<?= self::KEY_CSS_EXTERNO_PROCESS_LIST ?>']`)?.value || '[]'
                    )
                    config['<?= self::KEY_JS_EXTERNO_PROCESS_LIST ?>'] = JSON.parse(
                        document.querySelector(`input[name='<?= self::KEY_JS_EXTERNO_PROCESS_LIST ?>']`)?.value || '[]'
                    )
                    return config
                }

                const getGroupName = (url) => {
                    const match = url.match(/\/wp-content\/(?:plugins|themes)\/([^/]+)/)
                    return match ? match[1] : 'Otros'
                }

                const loadFileList = async (type) => {
                    const container = document.querySelector(`.stpa-ignore-items[data-type="${type}"]`)
                    const loading = document.querySelector(`.stpa-ignore-loading[data-type="${type}"]`)
                    const hiddenInput = document.querySelector(`input.stpa-ignore-values[name^="<?= STPA_KEY ?>_PAGE_STATIC_${type.toUpperCase()}_IGNORE_LIST"]`)
                    const currentIgnoreList = JSON.parse(hiddenInput?.value || '[]')
                    const toggle = document.querySelector(`.stpa-ignore-toggle[data-type="${type}"]`)

                    if (!toggle?.checked) return
                    if (container.children.length > 0) return

                    const updateHidden = () => {
                        const checked = container.querySelectorAll('input.stpa-ignore-file:checked')
                        const list = []
                        checked.forEach(function(cb) { list.push(cb.value) })
                        hiddenInput.value = JSON.stringify(list)
                    }

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

                        const groups = {}
                        files.forEach(function(file) {
                            const group = getGroupName(file)
                            if (!groups[group]) groups[group] = []
                            groups[group].push(file)
                        })

                        container.innerHTML = ''
                        Object.keys(groups).sort().forEach(function(groupName) {
                            const groupFiles = groups[groupName]
                            const groupDiv = document.createElement('div')
                            groupDiv.className = 'stpa-ignore-group'

                            const header = document.createElement('div')
                            header.className = 'stpa-ignore-group-header'

                            const selectAll = document.createElement('input')
                            selectAll.type = 'checkbox'
                            selectAll.addEventListener('change', function() {
                                const cbs = groupDiv.querySelectorAll('input.stpa-ignore-file')
                                cbs.forEach(function(cb) { cb.checked = selectAll.checked })
                                updateHidden()
                            })

                            const title = document.createElement('span')
                            title.textContent = groupName + ' (' + groupFiles.length + ')'

                            header.appendChild(selectAll)
                            header.appendChild(title)
                            groupDiv.appendChild(header)

                            const items = document.createElement('div')
                            items.className = 'stpa-ignore-group-items'

                            groupFiles.forEach(function(file) {
                                const label = document.createElement('label')
                                const cb = document.createElement('input')
                                cb.type = 'checkbox'
                                cb.className = 'stpa-ignore-file'
                                cb.value = file
                                cb.checked = currentIgnoreList.includes(file)
                                cb.addEventListener('change', updateHidden)
                                label.appendChild(cb)
                                label.appendChild(document.createTextNode(' ' + file))
                                items.appendChild(label)
                            })

                            groupDiv.appendChild(items)
                            container.appendChild(groupDiv)
                        })
                    } catch (e) {
                        container.innerHTML = '<span style="color:red;">Error al cargar archivos</span>'
                    }
                    loading.style.display = 'none'
                }

                const loadProcessList = async (type) => {
                    const container = document.querySelector(`.stpa-process-items[data-type="${type}"]`)
                    const loading = document.querySelector(`.stpa-process-loading[data-type="${type}"]`)
                    const hiddenInput = document.querySelector(`input.stpa-process-values[name^="<?= STPA_KEY ?>_PAGE_STATIC_${type.toUpperCase()}_EXTERNO_PROCESS"]`)
                    const currentProcessList = JSON.parse(hiddenInput?.value || '[]')
                    const toggle = document.querySelector(`.stpa-process-toggle[data-type="${type}"]`)
                    const ignoreHiddenInput = document.querySelector(`input.stpa-ignore-values[name^="<?= STPA_KEY ?>_PAGE_STATIC_${type.toUpperCase()}_IGNORE_LIST"]`)
                    const currentIgnoreList = JSON.parse(ignoreHiddenInput?.value || '[]')

                    if (!toggle?.checked) return
                    if (container.children.length > 0) return

                    const updateHidden = () => {
                        const checked = container.querySelectorAll('input.stpa-process-file:checked')
                        const list = []
                        checked.forEach(function(cb) { list.push(cb.value) })
                        hiddenInput.value = JSON.stringify(list)
                    }

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

                        files = files.filter(function(file) {
                            return !currentIgnoreList.some(function(ignored) {
                                return file.includes(ignored)
                            })
                        })

                        const groups = {}
                        files.forEach(function(file) {
                            const group = getGroupName(file)
                            if (!groups[group]) groups[group] = []
                            groups[group].push(file)
                        })

                        container.innerHTML = ''
                        Object.keys(groups).sort().forEach(function(groupName) {
                            const groupFiles = groups[groupName]
                            const groupDiv = document.createElement('div')
                            groupDiv.className = 'stpa-ignore-group'

                            const header = document.createElement('div')
                            header.className = 'stpa-ignore-group-header'

                            const selectAll = document.createElement('input')
                            selectAll.type = 'checkbox'
                            selectAll.checked = groupFiles.every(function(f) { return currentProcessList.includes(f) })
                            selectAll.addEventListener('change', function() {
                                const cbs = groupDiv.querySelectorAll('input.stpa-process-file')
                                cbs.forEach(function(cb) { cb.checked = selectAll.checked })
                                updateHidden()
                            })

                            const title = document.createElement('span')
                            title.textContent = groupName + ' (' + groupFiles.length + ')'

                            header.appendChild(selectAll)
                            header.appendChild(title)
                            groupDiv.appendChild(header)

                            const items = document.createElement('div')
                            items.className = 'stpa-ignore-group-items'

                            groupFiles.forEach(function(file) {
                                const label = document.createElement('label')
                                const cb = document.createElement('input')
                                cb.type = 'checkbox'
                                cb.className = 'stpa-process-file'
                                cb.value = file
                                cb.checked = currentProcessList.length === 0 || currentProcessList.includes(file)
                                cb.addEventListener('change', updateHidden)
                                label.appendChild(cb)
                                label.appendChild(document.createTextNode(' ' + file))
                                items.appendChild(label)
                            })

                            groupDiv.appendChild(items)
                            container.appendChild(groupDiv)
                        })

                        if (currentProcessList.length === 0) {
                            const allCheckboxes = container.querySelectorAll('input.stpa-process-file')
                            allCheckboxes.forEach(function(cb) { cb.checked = true })
                            updateHidden()
                        }
                    } catch (e) {
                        container.innerHTML = '<span style="color:red;">Error al cargar archivos</span>'
                    }
                    loading.style.display = 'none'
                }

                document.querySelectorAll('.stpa-process-toggle').forEach(toggle => {
                    toggle.addEventListener('change', () => {
                        const type = toggle.dataset.type
                        const details = document.querySelector(`details.stpa-list-collapse[data-list-type="process"][data-type="${type}"]`)
                        if (toggle.checked) {
                            details.open = true
                            loadProcessList(type)
                        } else {
                            details.open = false
                            const container = document.querySelector(`.stpa-process-items[data-type="${type}"]`)
                            if (container) container.innerHTML = ''
                            const hiddenInput = document.querySelector(`input.stpa-process-values[name^="<?= STPA_KEY ?>_PAGE_STATIC_${type.toUpperCase()}_EXTERNO_PROCESS"]`)
                            hiddenInput.value = '[]'
                        }
                    })
                    if (toggle.checked) {
                        loadProcessList(toggle.dataset.type)
                    }
                })

                document.querySelectorAll('.stpa-ignore-toggle').forEach(toggle => {
                    toggle.addEventListener('change', () => {
                        const type = toggle.dataset.type
                        const details = document.querySelector(`details.stpa-list-collapse[data-list-type="ignore"][data-type="${type}"]`)
                        if (toggle.checked) {
                            details.open = true
                            loadFileList(type)
                        } else {
                            details.open = false
                            const container = document.querySelector(`.stpa-ignore-items[data-type="${type}"]`)
                            if (container) container.innerHTML = ''
                        }
                        const processToggle = document.querySelector(`.stpa-process-toggle[data-type="${type}"]`)
                        if (processToggle?.checked) {
                            const processDetails = document.querySelector(`details.stpa-list-collapse[data-list-type="process"][data-type="${type}"]`)
                            if (processDetails) {
                                const processContainer = document.querySelector(`.stpa-process-items[data-type="${type}"]`)
                                if (processContainer) processContainer.innerHTML = ''
                                loadProcessList(type)
                            }
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
                        const bodyData = { html: finalHtml };
                        if (config?.<?= STPA_KEY ?>_PAGE_STATIC_CSS_FILE && finalCss) bodyData.css = finalCss;
                        if (config?.<?= STPA_KEY ?>_PAGE_STATIC_JS_FILE && finalJs) bodyData.js = finalJs;
                        const response = await fetch("/wp-json/<?= STPA_KEY ?>/html/<?= $post->ID ?>", {
                            method: "POST",
                            headers,
                            body: JSON.stringify(bodyData)
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
        $cssProcessList = isset($_POST[self::KEY_CSS_EXTERNO_PROCESS_LIST]) ? json_decode(stripslashes($_POST[self::KEY_CSS_EXTERNO_PROCESS_LIST]), true) : [];
        $jsProcessList = isset($_POST[self::KEY_JS_EXTERNO_PROCESS_LIST]) ? json_decode(stripslashes($_POST[self::KEY_JS_EXTERNO_PROCESS_LIST]), true) : [];
        $config[self::KEY_CSS_EXTERNO_PROCESS_LIST] = is_array($cssProcessList) ? $cssProcessList : [];
        $config[self::KEY_JS_EXTERNO_PROCESS_LIST] = is_array($jsProcessList) ? $jsProcessList : [];
        update_post_meta(
            $post_id,
            self::KEY_CONFIG,
            $config
        );
    }
}

STPA_PAGE_CONFIG::init();
