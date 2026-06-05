<script>
    const onLoad = () => {
        try {
            document.querySelectorAll('.nav-tab').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.nav-tab, .tab-content')
                        .forEach(el => el.classList.remove('nav-tab-active'));

                    btn.classList.add('nav-tab-active');
                    const tabContent = document.getElementById(btn.dataset.tab);
                    if (tabContent) tabContent.classList.add('nav-tab-active');
                });
            });

            const hash = window.location.hash
            if (hash) {
                const btn = document.querySelector(".nav-tab[href='" + hash + "']")
                if (btn) btn.click()
            }

            const page = document.getElementById("page-<?= GPAI_KEY ?>")
            if (page) {
                const btns = page.querySelectorAll('[type="submit"]')
                btns.forEach((e, i) => e.addEventListener('click', (ele) => {
                    btns[i].classList.add('loader')
                }))
            }
        } catch (e) {
            console.error('GPAI init error:', e)
        }
    }
    window.addEventListener('DOMContentLoaded', onLoad);
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtns = document.querySelectorAll('.stpa-toggle-active');
        toggleBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                const nonce = this.dataset.nonce;
                const row = this.closest('tr');

                fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'stpa_toggle_active',
                            post_id: postId,
                            nonce: nonce
                        })
                    })
                    .then(function(r) {
                        return r.json()
                    })
                    .then(function(data) {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (data.data?.message || 'Error al cambiar estado'));
                        }
                    })
                    .catch(function() {
                        alert('Error de conexión');
                    });
            });
        });

        const deleteBtns = document.querySelectorAll('.stpa-delete-file');
        deleteBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (!confirm('¿Eliminar el archivo estático de esta página?')) return;
                const postId = this.dataset.postId;
                const nonce = this.dataset.nonce;
                const row = this.closest('tr');

                fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'stpa_delete_file',
                            post_id: postId,
                            nonce: nonce
                        })
                    })
                    .then(function(r) {
                        return r.json()
                    })
                    .then(function(data) {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (data.data?.message || 'Error al eliminar archivo'));
                        }
                    })
                    .catch(function() {
                        alert('Error de conexión');
                    });
            });
        });

        const bulkSelectAll = document.getElementById('stpa-select-all');
        if (bulkSelectAll) {
            bulkSelectAll.addEventListener('change', function() {
                document.querySelectorAll('.stpa-bulk-checkbox').forEach(function(cb) {
                    cb.checked = bulkSelectAll.checked;
                });
            });
        }

        const bulkApplyBtn = document.getElementById('stpa-bulk-apply');
        if (bulkApplyBtn) {
            bulkApplyBtn.addEventListener('click', function() {
                const action = document.getElementById('stpa-bulk-action').value;
                if (!action) {
                    alert('Selecciona una acción');
                    return;
                }

                const checked = document.querySelectorAll('.stpa-bulk-checkbox:checked');
                if (checked.length === 0) {
                    alert('Selecciona al menos una página');
                    return;
                }

                if (!confirm('¿Aplicar "' + action + '" a ' + checked.length + ' página(s)?')) return;

                if (action === 'regenerate') {
                    const postIds = [];
                    checked.forEach(function(cb) {
                        postIds.push(cb.value);
                    });

                    bulkApplyBtn.disabled = true;
                    bulkApplyBtn.textContent = 'Regenerando 0/' + postIds.length + '...';

                    (async function() {
                        for (let i = 0; i < postIds.length; i++) {
                            const postId = postIds[i];
                            bulkApplyBtn.textContent = 'Regenerando ' + (i + 1) + '/' + postIds.length + ' (ID ' + postId + ')...';

                            const row = document.querySelector('.stpa-page-row[data-url] input.stpa-bulk-checkbox[value="' + postId + '"]');
                            const regenRow = row ? row.closest('.stpa-page-row') : null;
                            const url = regenRow ? regenRow.dataset.url : null;

                            if (!url) {
                                console.warn('Error: URL no disponible para ID ' + postId);
                                continue;
                            }

                            try {
                                await stpaRegeneratePost(postId, url);
                            } catch (e) {
                                console.warn('Error regenerando ID ' + postId + ': ' + e.message);
                            }
                        }

                        bulkApplyBtn.textContent = '¡Completado!';
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    })();

                    return;
                }

                const postIds = [];
                checked.forEach(function(cb) {
                    postIds.push(cb.value);
                });
                const nonce = bulkApplyBtn.dataset.nonce;

                fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'stpa_bulk_action',
                            bulk_action: action,
                            post_ids: postIds.join(','),
                            nonce: nonce
                        })
                    })
                    .then(function(r) {
                        return r.json()
                    })
                    .then(function(data) {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (data.data?.message || 'Error en acción masiva'));
                        }
                    })
                    .catch(function() {
                        alert('Error de conexión');
                    });
            });
        }

        document.querySelectorAll('.stpa-group').forEach(function(group) {
            const header = group.querySelector('.stpa-group-header');
            if (!header) return;
            const toggle = header.querySelector('.stpa-group-toggle');
            const rows = group.querySelectorAll('.stpa-page-row');

            header.addEventListener('click', function() {
                const isCollapsed = toggle.classList.contains('dashicons-arrow-right');
                rows.forEach(function(row) {
                    row.style.display = isCollapsed ? '' : 'none';
                });
                toggle.classList.toggle('dashicons-arrow-down', isCollapsed);
                toggle.classList.toggle('dashicons-arrow-right', !isCollapsed);
            });
        });

        const STPA_HEADERS = {
            "Content-Type": "application/json",
            "X-WP-Nonce": <?= json_encode(wp_create_nonce('wp_rest')) ?>,
            "api-key": <?= json_encode(STPA_API::getApiKey()) ?>
        };

        async function stpaRegeneratePost(postId, url) {
            const configResp = await fetch("/wp-json/<?= STPA_KEY ?>/post-config/" + postId, { headers: STPA_HEADERS });
            const configData = await configResp.json();
            if (!configData.success) throw new Error(configData?.message || 'Error al obtener config');
            const config = configData.config;

            const html = await getCode(url + "?<?= STPA_KEY ?>_DISABLE=1");
            const result = await procesingHtml(html, url, config);

            const bodyData = { html: result.html };
            if (result.css) bodyData.css = result.css;
            if (result.js) bodyData.js = result.js;

            const saveResp = await fetch("/wp-json/<?= STPA_KEY ?>/html/" + postId, {
                method: "POST",
                headers: STPA_HEADERS,
                body: JSON.stringify(bodyData)
            });
            const saveData = await saveResp.json();
            if (!saveData.success) throw new Error(saveData?.message || 'Error al guardar');
        }

        const regenBtns = document.querySelectorAll('.stpa-regenerate');
        regenBtns.forEach(function(btn) {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                const row = this.closest('.stpa-page-row');
                const url = row ? row.dataset.url : null;
                const btnEl = this;
                const originalText = btnEl.textContent;

                if (!url) {
                    alert('Error: URL de página no disponible');
                    return;
                }

                btnEl.textContent = 'Regenerando...';
                btnEl.classList.add('stpa-loader');
                btnEl.disabled = true;

                try {
                    await stpaRegeneratePost(postId, url);
                    location.reload();
                } catch (e) {
                    alert('Error: ' + e.message);
                    btnEl.textContent = originalText;
                    btnEl.classList.remove('stpa-loader');
                    btnEl.disabled = false;
                }
            });
        });
    });
</script>