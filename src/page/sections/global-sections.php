<?php

$stpa_gs_all = STPA_GLOBAL_SECTIONS_DATA::getAll();
$stpa_gs_pages = get_posts([
    'post_type' => 'page',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
]);
$stpa_gs_base_url = STPA_get_global_sections_url();
$stpa_gs_nonce = wp_create_nonce('stpa_global_section_action');

?>
<div class="wrap">
    <h2>Secciones Globales</h2>
    <p class="description">
        Extrae una sección (por ejemplo <code>header</code>, <code>footer</code> o <code>#seccion1</code>) de una página
        y guárdala como Sección Global. Cuando cualquier página genere su Static Page, buscará esa misma sección en su
        HTML y la reemplazará automáticamente con el contenido guardado aquí — así solo necesitas actualizar la
        Sección Global una vez, sin regenerar cada página que la use.
    </p>

    <h3>Crear Sección Global</h3>
    <table class="form-table">
        <tr>
            <th scope="row">Página de origen</th>
            <td>
                <select id="stpa-gs-post-id" class="regular-text">
                    <?php foreach ($stpa_gs_pages as $p): ?>
                        <option value="<?= $p->ID ?>"><?= esc_html(get_the_title($p->ID)) ?> (#<?= $p->ID ?>)</option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?= STPA_Tooltip('Clave de búsqueda', 'Selector de la sección a extraer: nombre de etiqueta (header, footer), #id o .clase.') ?>
            </th>
            <td>
                <input type="text" id="stpa-gs-selector" class="regular-text" placeholder="header, footer, #seccion1">
            </td>
        </tr>
    </table>
    <div class="content-btn">
        <button type="button" id="stpa-gs-create-btn" class="button button-primary" data-nonce="<?= $stpa_gs_nonce ?>">
            Crear Sección Global
        </button>
    </div>
    <div id="stpa-gs-create-result"></div>

    <hr>

    <h3>Secciones Globales Existentes</h3>
    <?php if (empty($stpa_gs_all)): ?>
        <div class="notice notice-info inline">
            <p>Aún no has creado ninguna Sección Global.</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped stpa-table">
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Página de origen</th>
                    <th>Archivo</th>
                    <th>Actualizado</th>
                    <th width="320">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stpa_gs_all as $slug => $entry):
                    $file = $entry['file'] ?? '';
                    $hasFile = $file && file_exists($file);
                    $sourcePost = get_post($entry['source_post_id'] ?? 0);
                ?>
                    <tr class="stpa-gs-row" data-slug="<?= esc_attr($slug) ?>">
                        <td><code><?= esc_html($entry['selector'] ?? $slug) ?></code></td>
                        <td>
                            <?php if ($sourcePost): ?>
                                <a href="<?= esc_url(get_edit_post_link($sourcePost->ID)) ?>" target="_blank">
                                    <?= esc_html(get_the_title($sourcePost->ID)) ?>
                                </a>
                            <?php else: ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($hasFile): ?>
                                <code class="stpa-file-info"><?= esc_html($slug) ?>.html</code>
                                <br><small class="stpa-file-info"><?= size_format(filesize($file)) ?></small>
                            <?php else: ?>
                                <span class="stpa-file-info" style="color:#999;">Sin archivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc_html($entry['updated_at'] ?? '') ?></td>
                        <td>
                            <div class="stpa-actions">
                                <?php if ($hasFile): ?>
                                    <a href="<?= esc_url($stpa_gs_base_url . '/' . $slug . '.html') ?>" class="button button-small" target="_blank">Ver HTML</a>
                                <?php endif; ?>
                                <button type="button" class="button button-small stpa-gs-regenerate" data-slug="<?= esc_attr($slug) ?>" data-nonce="<?= $stpa_gs_nonce ?>">Regenerar</button>
                                <button type="button" class="button button-small stpa-gs-delete" data-slug="<?= esc_attr($slug) ?>" data-nonce="<?= $stpa_gs_nonce ?>">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
