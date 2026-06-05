<?php

$search = trim($_GET['stpa_search'] ?? '');
$statusFilter = $_GET['stpa_status'] ?? '';
$fileFilter = $_GET['stpa_file'] ?? '';

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

$allPages = $pages;
$total = count($allPages);
$active = 0;
$with_file = 0;

foreach ($allPages as $p) {
    $cfg = get_post_meta($p->ID, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (is_array($cfg) && ($cfg[STPA_PAGE_CONFIG::KEY_ACTIVE] ?? false)) {
        $active++;
    }
    $f = get_post_meta($p->ID, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
    if ($f && file_exists($f)) {
        $with_file++;
    }
}

$filtered = [];

foreach ($allPages as $p) {
    $cfg = get_post_meta($p->ID, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (!is_array($cfg)) $cfg = [];
    $isActive = ($cfg[STPA_PAGE_CONFIG::KEY_ACTIVE] ?? false);
    $htmlFile = get_post_meta($p->ID, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
    $hasFile = $htmlFile && file_exists($htmlFile);
    $title = get_the_title($p->ID);

    if ($statusFilter === 'active' && !$isActive) continue;
    if ($statusFilter === 'inactive' && $isActive) continue;
    if ($fileFilter === 'with' && !$hasFile) continue;
    if ($fileFilter === 'without' && $hasFile) continue;
    if ($search !== '') {
        $matchId = strpos((string)$p->ID, $search) !== false;
        $matchTitle = stripos($title, $search) !== false;
        if (!$matchId && !$matchTitle) continue;
    }

    $filtered[] = $p;
}

$showFilters = $total > 0;

$filteredIds = [];
$children = [];
foreach ($filtered as $p) {
    $filteredIds[$p->ID] = true;
    if ($p->post_parent > 0) {
        $children[$p->post_parent][] = $p;
    }
}

$displayGroups = [];
$standalone = [];
$rendered = [];

foreach ($filtered as $p) {
    if (in_array($p->ID, $rendered)) continue;

    if (isset($children[$p->ID])) {
        usort($children[$p->ID], function ($a, $b) {
            return strcmp(get_the_title($a->ID), get_the_title($b->ID));
        });
        $displayGroups[] = [
            'parent' => $p,
            'children' => $children[$p->ID],
        ];
        $rendered[] = $p->ID;
        foreach ($children[$p->ID] as $child) {
            $rendered[] = $child->ID;
        }
    } elseif ($p->post_parent > 0 && isset($filteredIds[$p->post_parent])) {
        continue;
    } else {
        $standalone[] = $p;
        $rendered[] = $p->ID;
    }
}

function stpa_render_page_row($page, $isChild = false) {
    $config = get_post_meta($page->ID, STPA_PAGE_CONFIG::KEY_CONFIG, true);
    if (!is_array($config)) $config = [];
    $isActive = ($config[STPA_PAGE_CONFIG::KEY_ACTIVE] ?? false);
    $htmlFile = get_post_meta($page->ID, STPA_PAGE_CONFIG::KEY_HTML_FILE, true);
    $hasFile = $htmlFile && file_exists($htmlFile);
    $fileSize = $hasFile ? size_format(filesize($htmlFile)) : '';
    $editUrl = get_edit_post_link($page->ID);
    $viewUrl = get_permalink($page->ID);
    $indent = $isChild ? ' style="padding-left:36px;"' : '';
    ?>
    <tr class="stpa-page-row" data-parent-id="<?= $page->post_parent ?>" data-regen-nonce="<?= wp_create_nonce('stpa_regen_' . $page->ID) ?>" data-url="<?= esc_url($viewUrl) ?>">
        <td>
            <input type="checkbox" class="stpa-bulk-checkbox" value="<?= $page->ID ?>">
        </td>
        <td><?= $page->ID ?></td>
        <td<?= $indent ?>>
            <?php if ($isChild): ?>
                <span class="dashicons dashicons-arrow-right stpa-child-icon"></span>
            <?php endif; ?>
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
                <code class="stpa-file-info">page-<?= $page->ID ?>.html</code>
                <br>
                <small class="stpa-file-info"><?= $fileSize ?></small>
            <?php else: ?>
                <span class="stpa-file-info" style="color:#999;">Sin archivo</span>
            <?php endif; ?>
        </td>
        <td>
            <div class="stpa-actions">
                <a href="<?= $editUrl ?>" class="button button-small" target="_blank" style="display:flex;align-items:center;">
                    <span class="dashicons dashicons-edit" style="font-size:14px;width:14px;height:16px;"></span>
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
                <a href="<?= $viewUrl ?>" class="button button-small" target="_blank">Ver</a>
                <a href="<?= $viewUrl."?STPA_DISABLE" ?>" class="button button-small" target="_blank">Ver Original</a>
                <a href="https://pagespeed.web.dev/analysis?url=<?= urlencode($viewUrl) ?>&form_factor=mobile"
                   class="button button-small" target="_blank" title="Analizar con PageSpeed Insights">
                    <span class="dashicons dashicons-chart-area" style="font-size:14px;width:14px;height:14px;margin-top:3px;"></span>
                </a>
            </div>
        </td>
    </tr>
<?php }

?>
<div class="wrap">
    <h2>
        Páginas con Static Page
        <span class="stpa-badge"><?= $total ?> páginas</span>
        <span class="stpa-badge" style="background:#1a7d36;"><?= $active ?> activas</span>
        <span class="stpa-badge" style="background:#2271b1;"><?= $with_file ?> con archivo</span>
    </h2>
    <br>

    <?php if ($total === 0): ?>
        <div class="notice notice-info inline">
            <p>No hay páginas con la configuración de Static Page. Ve a editar una página y activa la opción "Activar Carga de Pagina Estatica" en el meta-box.</p>
        </div>
    <?php else: ?>
        <form class="stpa-filters" method="get">
            <input type="hidden" name="page" value="<?= esc_attr($_GET['page'] ?? '') ?>">
            <input type="text" name="stpa_search" class="stpa-search-input" placeholder="Buscar por nombre o ID..." value="<?= esc_attr($search) ?>">
            <select name="stpa_status">
                <option value="">Todos los estados</option>
                <option value="active" <?= selected($statusFilter, 'active', false) ?>>Activos</option>
                <option value="inactive" <?= selected($statusFilter, 'inactive', false) ?>>Inactivos</option>
            </select>
            <select name="stpa_file">
                <option value="">Todos los archivos</option>
                <option value="with" <?= selected($fileFilter, 'with', false) ?>>Con archivo</option>
                <option value="without" <?= selected($fileFilter, 'without', false) ?>>Sin archivo</option>
            </select>
            <button type="submit" class="button">Filtrar</button>
            <?php if ($search !== '' || $statusFilter !== '' || $fileFilter !== ''): ?>
                <a href="<?= esc_url(remove_query_arg(['stpa_search', 'stpa_status', 'stpa_file'])) ?>" class="button">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="stpa-bulk-actions">
            <select id="stpa-bulk-action">
                <option value="">— Acciones masivas —</option>
                <option value="activate">Activar</option>
                <option value="deactivate">Desactivar</option>
                <option value="regenerate">Regenerar</option>
                <option value="delete">Eliminar archivos y desactivar</option>
            </select>
            <button type="button" id="stpa-bulk-apply" class="button" data-nonce="<?= wp_create_nonce('stpa_bulk') ?>">
                Aplicar
            </button>
            <span class="stpa-filter-count"><?= count($filtered) ?> página(s) filtrada(s)</span>
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
            <?php if (count($filtered) === 0): ?>
                <tbody>
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:#999;">No se encontraron páginas con los filtros seleccionados.</td></tr>
                </tbody>
            <?php endif; ?>
            <?php foreach ($displayGroups as $group):
                $parent = $group['parent'];
            ?>
                <tbody class="stpa-group" data-group-id="<?= $parent->ID ?>">
                    <tr class="stpa-group-header">
                        <td colspan="6">
                            <span class="stpa-group-toggle dashicons dashicons-arrow-down"></span>
                            <strong><?= esc_html(get_the_title($parent->ID)) ?></strong>
                            <span class="stpa-badge" style="background:#787c82;"><?= count($group['children']) ?> subpáginas</span>
                        </td>
                    </tr>
                    <?php stpa_render_page_row($parent); ?>
                    <?php foreach ($group['children'] as $child):
                        stpa_render_page_row($child, true);
                    endforeach; ?>
                </tbody>
            <?php endforeach; ?>
            <?php if (count($standalone) > 0): ?>
                <tbody class="stpa-standalone">
                    <?php foreach ($standalone as $page):
                        stpa_render_page_row($page);
                    endforeach; ?>
                </tbody>
            <?php endif; ?>
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
