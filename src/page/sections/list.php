<?php

$pages = get_posts([
    'post_type' => 'page',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => STPA_PAGE_CONFIG::KEY_CONFIG,
            'compare' => 'EXISTS',
        ]
    ],
    'orderby' => 'title',
    'order' => 'ASC',
]);

$total = count($pages);
$active = 0;
$with_file = 0;

foreach ($pages as $p) {
    $cfg = get_post_meta($p->ID, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (is_array($cfg) && ($cfg[STPA_PAGE_CONFIG::KEY_ACTIVE] ?? false)) {
        $active++;
    }
    $f = get_post_meta($p->ID, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
    if ($f && file_exists($f)) {
        $with_file++;
    }
}

?>
<div class="wrap">
    <h2>
        Páginas con Static Page
        <span class="stpa-badge"><?= $total ?> páginas</span>
        <span class="stpa-badge" style="background:#1a7d36;"><?= $active ?> activas</span>
        <span class="stpa-badge" style="background:#2271b1;"><?= $with_file ?> con archivo</span>
    </h2>

    <?php if ($total === 0): ?>
        <div class="notice notice-info inline">
            <p>No hay páginas con la configuración de Static Page. Ve a editar una página y activa la opción "Activar Carga de Pagina Estatica" en el meta-box.</p>
        </div>
    <?php else: ?>
        <div class="stpa-bulk-actions">
            <select id="stpa-bulk-action">
                <option value="">— Acciones masivas —</option>
                <option value="activate">Activar</option>
                <option value="deactivate">Desactivar</option>
                <option value="delete">Eliminar archivos y desactivar</option>
            </select>
            <button type="button" id="stpa-bulk-apply" class="button" data-nonce="<?= wp_create_nonce('stpa_bulk') ?>">
                Aplicar
            </button>
        </div>

        <table class="wp-list-table widefat fixed striped stpa-table">
        <colgroup>
            <col style="width: 5%;">
            <col style="width: 5%;">
            <col style="width: 20%;">
            <col style="width: 10%;">
            <col style="width: 20%;">
            <col style="width: 40%;">
        </colgroup>
            <thead>
                <tr>
                    <th width="40">
                        <input type="checkbox" id="stpa-select-all">
                    </th>
                    <th width="60">ID</th>
                    <th>Título</th>
                    <th width="130">Estado</th>
                    <th>Archivo</th>
                    <th width="320">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page):
                    $config = get_post_meta($page->ID, STPA_PAGE_CONFIG::KEY_CONFIG, true);
                    if (!is_array($config)) $config = [];

                    $isActive = ($config[STPA_PAGE_CONFIG::KEY_ACTIVE] ?? false) === '1';
                    $htmlFile = get_post_meta($page->ID, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
                    $hasFile = $htmlFile && file_exists($htmlFile);
                    $fileSize = $hasFile ? size_format(filesize($htmlFile)) : '';
                    $editUrl = get_edit_post_link($page->ID);
                    $viewUrl = get_permalink($page->ID);
                ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="stpa-bulk-checkbox" value="<?= $page->ID ?>">
                        </td>
                        <td><?= $page->ID ?></td>
                        <td>
                            <strong><?= esc_html(get_the_title($page->ID)) ?></strong>
                        </td>
                        <td>
                            <span class="stpa-status <?= $isActive ? 'active' : 'inactive' ?>">
                                <span class="dashicons dashicons-<?= $isActive ? 'yes' : 'no' ?>"></span>
                                <?= $isActive ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($hasFile): ?>
                                <code class="stpa-file-info">
                                    page-<?= $page->ID ?>.html
                                </code>
                                <br>
                                <small class="stpa-file-info"><?= $fileSize ?></small>
                            <?php else: ?>
                                <span class="stpa-file-info" style="color:#999;">Sin archivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="stpa-actions">
                                <a href="<?= $editUrl ?>" class="button button-small" target="_blank">
                                    <span class="dashicons dashicons-edit" style="font-size:14px;width:14px;height:14px;margin-top:3px;"></span>
                                </a>

                                <button type="button"
                                    class="button button-small stpa-toggle-active <?= $isActive ? 'button-primary' : '' ?>"
                                    data-post-id="<?= $page->ID ?>"
                                    data-nonce="<?= wp_create_nonce('stpa_toggle_' . $page->ID) ?>">
                                    <?= $isActive ? 'Desactivar' : 'Activar' ?>
                                </button>

                                <?php if ($isActive): ?>
                                    <button type="button"
                                        class="button button-small stpa-regenerate"
                                        data-post-id="<?= $page->ID ?>"
                                        data-nonce="<?= wp_create_nonce('stpa_regen_' . $page->ID) ?>">
                                        Regenerar
                                    </button>
                                <?php endif; ?>

                                <?php if ($hasFile): ?>
                                    <button type="button"
                                        class="button button-small stpa-delete-file"
                                        data-post-id="<?= $page->ID ?>"
                                        data-nonce="<?= wp_create_nonce('stpa_delete_' . $page->ID) ?>">
                                        Eliminar
                                    </button>
                                <?php endif; ?>

                                <a href="<?= $viewUrl ?>" class="button button-small" target="_blank">
                                    Ver
                                </a>
                                <a href="<?= $viewUrl."?STPA_DISABLE" ?>" class="button button-small" target="_blank">
                                    Ver Pagina Original
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th><input type="checkbox" id="stpa-select-all-2" onchange="document.getElementById('stpa-select-all').checked=this.checked;document.querySelectorAll('.stpa-bulk-checkbox').forEach(function(cb){cb.checked=this.checked},this)"></th>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Archivo</th>
                    <th>Acciones</th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>
