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

        const regenBtns = document.querySelectorAll('.stpa-regenerate');
        regenBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                const nonce = this.dataset.nonce;
                const btnEl = this;
                const originalText = btnEl.textContent;

                btnEl.textContent = 'Regenerando...';
                btnEl.classList.add('stpa-loader');
                btnEl.disabled = true;

                fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'stpa_regenerate',
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
                            alert('Error: ' + (data.data?.message || 'Error al regenerar'));
                            btnEl.textContent = originalText;
                            btnEl.classList.remove('stpa-loader');
                            btnEl.disabled = false;
                        }
                    })
                    .catch(function() {
                        alert('Error de conexión');
                        btnEl.textContent = originalText;
                        btnEl.classList.remove('stpa-loader');
                        btnEl.disabled = false;
                    });
            });
        });
    });
</script>