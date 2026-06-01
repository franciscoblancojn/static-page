<?php

$respond_config = null;

if (isset($_POST['save']) && $_POST['save'] === 'stpa_config') {
    $output_type = $_POST['output_type'] ?? 'default';

    $CONFIG['output_type'] = $output_type;
    $CONFIG['output_dir'] = isset($_POST['output_dir']) ? sanitize_text_field($_POST['output_dir']) : '';
    $CONFIG['auto_regenerate'] = isset($_POST['auto_regenerate']) ? '1' : '0';
    $CONFIG['keep_css_js'] = isset($_POST['keep_css_js']) ? '1' : '0';

    $STPA_USE_DATA_CONFIG->set($CONFIG);

    $respond_config = [
        'status' => 'ok',
        'message' => 'Configuración guardada correctamente.',
    ];
}

$output_type = $CONFIG['output_type'] ?? 'default';
$output_dir = $CONFIG['output_dir'] ?? '';
$auto_regenerate = $CONFIG['auto_regenerate'] ?? false;
$keep_css_js = $CONFIG['keep_css_js'] ?? false;

?>
<form method="post">
    <?= STPA_Respond($respond_config) ?>
    <input type="hidden" name="save" value="stpa_config">
    <table class="form-table">
        <tr>
            <th scope="row">
                <?= STPA_Tooltip('Tipo de salida', 'Define cómo se organizan los archivos estáticos en el sistema de carpetas.') ?>
            </th>
            <td>
                <select name="output_type" id="stpa-output-type" class="regular-text">
                    <option value="default" <?= selected($output_type, 'default', false) ?>>
                        Plano (todo en /uploads/STPA/)
                    </option>
                    <option value="slug" <?= selected($output_type, 'slug', false) ?>>
                        Por slug (/uploads/STPA/{slug}/)
                    </option>
                    <option value="id_group" <?= selected($output_type, 'id_group', false) ?>>
                        Por ID en grupos (/uploads/STPA/group-{id}/)
                    </option>
                    <option value="parent" <?= selected($output_type, 'parent', false) ?>>
                        Por página padre (/uploads/STPA/{parent-slug}/)
                    </option>
                </select>
                <p class="description">
                    Selecciona cómo se organizarán los archivos estáticos en carpetas.
                    <br><strong>Plano:</strong> Todos los archivos en una misma carpeta.
                    <br><strong>Por slug:</strong> Cada página en su propia carpeta usando el slug de la página.
                    <br><strong>Por ID en grupos:</strong> Agrupa páginas en lotes de 100 IDs.
                    <br><strong>Por página padre:</strong> Organiza por el slug de la página padre.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?= STPA_Tooltip('Directorio de salida', 'Personaliza el directorio base dentro de /uploads/ (opcional).') ?>
            </th>
            <td>
                <input type="text" name="output_dir" value="<?= esc_attr($output_dir) ?>" class="regular-text" placeholder="STPA">
                <p class="description">
                    Nombre de la carpeta base dentro de <code>wp-content/uploads/</code>. Por defecto: <code>STPA</code>.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?= STPA_Tooltip('Auto-regenerar', 'Al activar, al guardar una página desde el editor se regenerará automáticamente el archivo estático.') ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="auto_regenerate" value="1" <?= checked($auto_regenerate, '1', false) ?>>
                    Regenerar automáticamente al guardar la página
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?= STPA_Tooltip('Conservar CSS/JS', 'Al eliminar el archivo HTML, conservar los archivos CSS y JS externos.') ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="keep_css_js" value="1" <?= checked($keep_css_js, '1', false) ?>>
                    Conservar archivos CSS y JS al eliminar
                </label>
            </td>
        </tr>
    </table>

    <div class="content-btn">
        <button type="submit" name="submit" value="Guardar" class="button button-primary">
            Guardar configuración
        </button>
    </div>
</form>
