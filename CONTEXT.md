# Static Page — Contexto para IAs

> Plug-in WordPress v1.9.3 — Generado automáticamente para que IAs entren en contexto rápido.

---

## ¿Qué hace este plugin?

Genera archivos HTML estáticos optimizados a partir de páginas de WordPress. Permite:
- Generación de HTML estático con un clic desde el editor de páginas
- Inlining de CSS (combina `<link>` y `<style>` en un solo bloque)
- Minificado de CSS y HTML
- Eliminación de la barra de administración de WordPress
- Restauración de imágenes lazy (`data-src` → `src`)
- Ignorado selectivo de CSS/JS
- Purga de CSS no utilizado
- Integración con Elementor (auto-regeneración al guardar)
- **Secciones Globales**: fragmentos HTML reutilizables que se actualizan automáticamente en todas las páginas estáticas
- Auto-update vía GitHub
- Sistema de logs

---

## Constantes globales

| Constante | Valor | Dónde se usa |
|---|---|---|
| `STPA_KEY` | `'STPA'` | Prefijo de opciones, meta keys, slugs |
| `STPA_CONFIG` | `'STPA_CONFIG'` | `wp_options` → configuración del plugin |
| `STPA_CONTENT` | `'STPA_CONTENT'` | `wp_options` → contenido |
| `STPA_DIR` | `plugin_dir_path(__FILE__)` | Base del plugin |
| `STPA_URL` | `plugin_dir_url(__FILE__)` | URL base del plugin |
| `STPA_KEY_SEPARETE` | `'____STPA____'` | Separador en valores de formularios |
| `STPA_LOG` | `true` | Habilita logs del plugin |
| `STPA_LOG_KEY` | `'STPA_LOG'` | Clave para opción de logs |
| `STPA_LOG_COUNT` | `100` | Máximo de entradas de log |
| `STPA_BASENAME` | `plugin_basename(__FILE__)` | Base name del plugin |

---

## Estructura de archivos

```
index.php               → Plugin header, constantes, auto-updater GitHub (vía FWUUpdate)
libs/                   → Composer vendor (franciscoblancojn/wordpress_utils)
src/
  _.php                 → Cargador maestro (require de todos los módulos)
  helpers.php           → STPA_get_output_dir()
  api/
    api.php             → STPA_API: Base REST API
    set-html.php        → STPA_API_SET_HTML: Guarda HTML estático
    set-post-config.php → STPA_API_SET_POST_CONFIG: Guarda config de página
    admin-ajax.php      → Handlers AJAX: toggle, delete, bulk, regenerate, global sections CRUD
  block/
    post.php            → STPA_PAGE_CONFIG: Meta box en editor de páginas
  css/
    global.php          → Estilos admin inline
  data/
    config.php          → STPA_USE_DATA_CONFIG: Config plugin (wp_options)
  hook/
    template_redirect.php → Servido de HTML estático + reemplazo de secciones globales
    assets.php          → STPA_ASSETS: Servido de CSS/JS estáticos
    elementor.php       → Integración Elementor (auto-regeneración)
  js/
    global.php          → JS admin: tabs, AJAX toggle/delete/bulk/regenerate/global sections
    procesing-html.php  → Motor de procesamiento HTML cliente-side (minificar, inlining CSS)
  page/
    add.php             → add_menu_page('Static Page')
    pages/config/       → Submenú "Páginas Estáticas"
    sections/
      list.php          → Tabla de páginas estáticas con filtros y acciones
      global-sections.php → Secciones globales (crear, listar, regenerar, eliminar)
      config.php        → Configuración global (tipo de salida, directorio, auto-regenerar)
  templates/
    respond.php         → STPA_Respond(): Notificaciones de estado
    tooltip.php         → STPA_Tooltip(): Tooltips informativos
```

---

## Clases y métodos clave

### STPA_API (`src/api/api.php`)
| Método | Descripción |
|---|---|
| `init()` | Registra ruta REST |
| `getApiKey()` | Obtener/generar API key |
| `validateApiKey($request)` | Valida header `api-key` |
| `validateUser($request)` | Valida nonce WP |
| `enpoint($request)` | Handler del endpoint |

### STPA_API_SET_HTML (`src/api/set-html.php`)
| Método | Descripción |
|---|---|
| `enpoint($request)` | Guarda HTML estático a archivo |

### STPA_API_SET_POST_CONFIG (`src/api/set-post-config.php`)
| Método | Descripción |
|---|---|
| `enpoint($request)` | Guarda config de generación en post meta |

### STPA_PAGE_CONFIG (`src/block/post.php`)
| Método | Descripción |
|---|---|
| `register()` | Registra meta box en páginas |
| `render($post)` | Renderiza checkboxes de configuración |
| `save($post_id)` | Guarda configuración |

### STPA_USE_DATA_CONFIG (`src/data/config.php`)
| Método | Descripción |
|---|---|
| `get()` | Obtiene config global |
| `set($data)` | Guarda config global |
| `setField($key, $value)` | Actualiza un campo individual |

### STPA_ASSETS (`src/hook/assets.php`)
| Método | Descripción |
|---|---|
| `serve()` | Sirve archivos CSS/JS estáticos vía query var |

---

## Post Meta Keys

| Meta Key | Propósito |
|---|---|
| `STPA_KEY_CONFIG` | Configuración completa de la página (array serializado) |
| `STPA_PAGE_STATIC_ACTIVE` | Flag activar carga estática |
| `STPA_PAGE_STATIC_CSS_EXTERNO` | Procesar CSS externo |
| `STPA_PAGE_STATIC_CSS_INTERNO` | Procesar CSS interno |
| `STPA_PAGE_STATIC_CSS_PURGE` | Purgar CSS no usado |
| `STPA_PAGE_STATIC_CSS_FILE` | Ruta al archivo CSS generado |
| `STPA_PAGE_STATIC_CSS_IGNORE` | Ignorar CSS específico |
| `STPA_PAGE_STATIC_CSS_IGNORE_LIST` | Lista de CSS a ignorar |
| `STPA_PAGE_STATIC_CSS_EXTERNO_PROCESS` | Lista de CSS a procesar |
| `STPA_PAGE_STATIC_JS_EXTERNO` | Procesar JS externo |
| `STPA_PAGE_STATIC_JS_INTERNO` | Procesar JS interno |
| `STPA_PAGE_STATIC_JS_FILE` | Ruta al archivo JS generado |
| `STPA_PAGE_STATIC_JS_IGNORE` | Ignorar JS específico |
| `STPA_PAGE_STATIC_JS_IGNORE_LIST` | Lista de JS a ignorar |
| `STPA_PAGE_STATIC_JS_EXTERNO_PROCESS` | Lista de JS a procesar |
| `STPA_PAGE_STATIC_HTML` | HTML generado |
| `STPA_PAGE_STATIC_HTML_FILE` | Ruta al archivo HTML generado |

---

## wp_options Keys

| Option Key | Clase | Propósito |
|---|---|---|
| `STPA_CONFIG` | `STPA_USE_DATA_CONFIG` | Config global: output_type, output_dir, auto_regenerate, keep_css_js |
| `STPA_API_KEY` | `STPA_API` | API key para REST |
| `STPA_LOG` | `FWUSystemLog` | Logs del sistema |

---

## AJAX Endpoints

| Action | Handler | Propósito |
|---|---|---|
| `stpa_toggle_active` | admin-ajax.php | Activar/Desactivar página estática |
| `stpa_delete_file` | admin-ajax.php | Eliminar archivo estático |
| `stpa_bulk_action` | admin-ajax.php | Acciones masivas |
| `stpa_regenerate` | admin-ajax.php | Regenerar página individual |
| `stpa_gs_create` | admin-ajax.php | Crear sección global |
| `stpa_gs_regenerate` | admin-ajax.php | Regenerar sección global |
| `stpa_gs_delete` | admin-ajax.php | Eliminar sección global |
| `stpa_gs_view` | admin-ajax.php | Ver HTML de sección global |

---

## REST API

| Route | Methods | Handler | Propósito |
|---|---|---|---|
| `/STPA/` | POST | `STPA_API` | Base (no usado directamente) |
| `/STPA/post-config/(?P<id>\d+)` | GET, POST | `STPA_API_SET_POST_CONFIG` | Guardar config de página |
| `/STPA/html/(?P<id>\d+)` | POST | `STPA_API_SET_HTML` | Guardar HTML generado |

---

## Hooks de WordPress

### Acciones
```php
add_action('admin_menu', ...)                        → Registra menú principal
add_action('add_meta_boxes', ...)                    → Meta box en páginas
add_action('save_post', ...)                         → Guardado de config
add_action('template_redirect', ..., 1)              → Sirve HTML estático
add_action('template_redirect', ..., 1)              → Sirve assets CSS/JS
add_action('rest_api_init', ...)                     → Registra rutas REST
add_action('elementor/editor/after_enqueue_scripts', ...) → Integración Elementor
add_action('wp_ajax_stpa_*', ...)                    → Handlers AJAX
```

### Filtros
```php
add_filter('query_vars', ...)                        → Registra STPA_ASSET query var
add_filter('site_transient_update_plugins', ...)     → Auto-updater GitHub
add_filter('plugin_action_links_{basename}', ...)    → Link actualizar
```

---

## Secciones Globales

### Archivos
- `{output_dir}/section-global/{key}.html` → HTML de la sección
- `{output_dir}/section-global/{key}.json` → Metadatos (source_page_id, created, updated)

### Reemplazo en Frontend
En `template_redirect.php`, `STPA_replace_global_sections()`:
1. Escanea `section-global/*.html`
2. Para cada sección, busca elementos en el HTML estático por tag, `#id`, o `.class`
3. Reemplaza el outer HTML del elemento con el contenido guardado

### Creación
En `admin-ajax.php`, `stpa_gs_create`:
1. Fetch de la página origen con `STPA_DISABLE=1`
2. Usa DOMDocument+XPath para extraer el elemento
3. Guarda `.html` y `.json`

---

## Dependencias

- **WordPress** 5.0+
- **PHP** 7.0+
- **Composer**: `franciscoblancojn/wordpress_utils` (FWUUpdate, FWUSystemLog, FWUPage, FWURespond, FWUTooltip)
