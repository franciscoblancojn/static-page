<?php

function STPA_get_global_sections()
{
    $dir = STPA_get_global_sections_dir();
    if (!is_dir($dir)) {
        return [];
    }

    $files = glob($dir . '/*.html') ?: [];
    $hidden = glob($dir . '/.*.html') ?: [];
    $files = array_merge($files, $hidden);
    $sections = [];

    foreach ($files as $file) {
        $key = basename($file, '.html');
        $metaFile = $dir . '/' . $key . '.json';
        $meta = [];
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?: [];
        }
        $sections[] = [
            'key' => $key,
            'file' => $file,
            'size' => filesize($file),
            'size_formatted' => size_format(filesize($file)),
            'modified' => filemtime($file),
            'meta' => $meta,
        ];
    }

    usort($sections, function ($a, $b) {
        return strcmp($a['key'], $b['key']);
    });

    return $sections;
}

$globalSections = STPA_get_global_sections();
$allPages = get_posts([
    'post_type' => 'page',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
]);

?>
<div class="wrap">
    <h2>Secciones Globales</h2>
    <p class="description">
        Las Secciones Globales permiten definir fragmentos HTML (como headers, footers, barras laterales) 
        que se actualizan automáticamente en todas las páginas estáticas existentes.
        Al servir una página estática, el plugin busca elementos HTML que coincidan con el nombre de etiqueta,
        ID o clase de cada sección global y los reemplaza con el contenido guardado.
        De esta forma, solo necesitas actualizar la sección global y todas las páginas se actualizarán.
    </p>
    <br>

    <h3>Crear nueva sección global</h3>
    <form id="stpa-gs-create-form">
        <table class="form-table">
            <tr>
                <th scope="row">Página de origen</th>
                <td>
                    <select name="gs_page_id" id="stpa-gs-page" class="regular-text" required>
                        <option value="">— Seleccionar página —</option>
                        <?php foreach ($allPages as $p): ?>
                            <option value="<?= $p->ID ?>"><?= esc_html(get_the_title($p->ID)) ?> (ID: <?= $p->ID ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">La página de la cual se extraerá el HTML de la sección.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Clave de búsqueda</th>
                <td>
                    <input type="text" name="gs_key" id="stpa-gs-key" class="regular-text" 
                           placeholder="header, footer, #seccion1, .clase" required>
                    <p class="description">
                        Nombre de etiqueta (ej: <code>header</code>, <code>footer</code>), 
                        ID (ej: <code>#mi-id</code>) o clase (ej: <code>.mi-clase</code>).
                        Al servir páginas estáticas, los elementos que coincidan serán
                        reemplazados automáticamente con el contenido de esta sección global.
                    </p>
                </td>
            </tr>
        </table>
        <div class="content-btn">
            <button type="submit" class="button button-primary" id="stpa-gs-create-btn">
                Crear sección global
            </button>
            <span id="stpa-gs-create-msg" class="stpa-message" style="display:none;"></span>
        </div>
    </form>

    <br><hr><br>

    <h3>
        Secciones globales existentes
        <span class="stpa-badge"><?= count($globalSections) ?> secciones</span>
    </h3>

    <?php if (empty($globalSections)): ?>
        <div class="notice notice-info inline">
            <p>No hay secciones globales creadas aún. Usa el formulario de arriba para crear la primera.</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped stpa-table">
            <colgroup>
                <col style="width: 15%;">
                <col style="width: 10%;">
                <col style="width: 15%;">
                <col style="width: 15%;">
                <col style="width: 45%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Tamaño</th>
                    <th>Página de origen</th>
                    <th>Modificado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($globalSections as $sec): 
                    $sourceId = $sec['meta']['source_page_id'] ?? 0;
                    $sourceTitle = $sourceId ? get_the_title($sourceId) : '—';
                ?>
                    <tr class="stpa-gs-row" data-gs-key="<?= esc_attr($sec['key']) ?>">
                        <td>
                            <strong><?= esc_html($sec['key']) ?></strong>
                        </td>
                        <td><?= $sec['size_formatted'] ?></td>
                        <td><?= esc_html($sourceTitle) ?></td>
                        <td><?= date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $sec['modified']) ?></td>
                        <td>
                            <div class="stpa-actions">
                                <button type="button"
                                    class="button button-small stpa-gs-regenerate"
                                    data-gs-key="<?= esc_attr($sec['key']) ?>"
                                    data-gs-page="<?= $sourceId ?>"
                                    data-nonce="<?= wp_create_nonce('stpa_gs_regen_' . $sec['key']) ?>">
                                    Regenerar
                                </button>
                                <button type="button"
                                    class="button button-small stpa-gs-view"
                                    data-gs-key="<?= esc_attr($sec['key']) ?>"
                                    data-nonce="<?= wp_create_nonce('stpa_gs_view_' . $sec['key']) ?>">
                                    Ver HTML
                                </button>
                                <button type="button"
                                    class="button button-small stpa-gs-delete"
                                    data-gs-key="<?= esc_attr($sec['key']) ?>"
                                    data-nonce="<?= wp_create_nonce('stpa_gs_delete_' . $sec['key']) ?>">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="stpa-gs-view-modal" class="stpa-gs-view-modal-overlay">
    <div class="stpa-gs-view-modal-box">
        <div class="stpa-gs-view-modal-header">
            <h3 id="stpa-gs-view-title">HTML de sección</h3>
            <button type="button" id="stpa-gs-view-close" class="stpa-gs-view-modal-close">&times;</button>
        </div>
        <pre id="stpa-gs-view-content" class="stpa-gs-view-modal-content"></pre>
    </div>
</div>
