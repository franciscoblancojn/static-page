# Static Page Plugin Skill

## Core Concepts

### Static HTML Generation
1. Usuario configura opciones en meta box (CSS/JS processing, active flag)
2. JS cliente-side (`procesing-html.php`) orquesta el procesamiento:
   - POST config a REST API
   - Fetch página via `?STPA_DISABLE=1`
   - Procesa HTML (minificar, inlining CSS, admin bar removal, lazy images)
   - POST HTML procesado a REST API
3. Servidor escribe archivo `{output_dir}/page-{id}.html`
4. `template_redirect.php` sirve el archivo estático

### Secciones Globales
1. Creación: fetch página origen → DOMDocument+XPath → extraer elemento → guardar `{key}.html` + `{key}.json`
2. Reemplazo: al servir HTML estático, buscar elementos por tag/#id/.class y reemplazar con sección guardada
3. Archivos en `{output_dir}/section-global/`

### Output Directory
- Helper: `STPA_get_output_dir($post_id)`
- Modos: default (plano), slug, id_group, parent
- Base: `wp-content/uploads/{output_dir}/`

### Post Meta
- `STPA_KEY_CONFIG` → config completa (array)
- `STPA_PAGE_STATIC_ACTIVE` → flag activo
- `STPA_PAGE_STATIC_HTML_FILE` → ruta al HTML
- `STPA_PAGE_STATIC_CSS_FILE` → ruta al CSS
- `STPA_PAGE_STATIC_JS_FILE` → ruta al JS

### AJAX Endpoints
| Action | Purpose |
|---|---|
| `stpa_toggle_active` | Toggle activo/inactivo |
| `stpa_delete_file` | Delete HTML file |
| `stpa_bulk_action` | Bulk activate/deactivate/regenerate/delete |
| `stpa_regenerate` | Regenerate single page |
| `stpa_gs_create` | Create global section |
| `stpa_gs_regenerate` | Regenerate global section |
| `stpa_gs_delete` | Delete global section |
| `stpa_gs_view` | View global section HTML |

### Logging
- `FWUSystemLog::add(STPA_KEY, ['type' => 'TYPE', 'data' => $data])`
- Visible desde la barra de admin
