# Static Page — Reglas para IAs

Este archivo contiene las reglas, validaciones y convenciones que toda IA debe seguir al programar en este proyecto.

---

## 1. Estándares de Código

### PHP
- **WordPress Coding Standards**: Sigue los estándares de codificación de WordPress para PHP.
- **PHP 7.0+**: No uses sintaxis moderna de PHP (nullsafe `?->`, named arguments, match, readonly properties, etc). El operador `??` (null coalescing) está permitido.
- **Nombrado**: Las clases usan prefijo `STPA_` (ej: `STPA_API`, `STPA_PAGE_CONFIG`). Métodos y propiedades en `camelCase` o `UPPER_SNAKE` para constantes.
- **Sanitización**: Toda salida de datos debe escaparse. Usa `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` según contexto.
- **Nonces**: Todo formulario y AJAX debe verificar nonce con `wp_verify_nonce()` o `check_ajax_referer()`.
- **Capabilities**: Toda operación admin debe verificar `current_user_can('manage_options')`.

### JavaScript
- **ES5**: Usa ES5 (var, function expressions, no arrow functions, no let/const, no template literals).
- **AJAX**: Toda llamada AJAX debe usar `fetch(ajaxurl, ...)` con action registrada via `wp_ajax_*` y nonce.
- **Nombrado**: Funciones en `snake_case` con prefijo `stpa_` (ej: `stpaRegeneratePost`).

### CSS
- **Prefijo**: Todas las clases CSS deben llevar prefijo `stpa-`.
- **Especificidad**: Evita `!important`. Usa clases con la especificidad adecuada.

---

## 2. Arquitectura del Plugin

### Sistema de Archivos
- `index.php` → Plugin header y constantes globales. No agregues lógica aquí.
- `src/_.php` → Cargador maestro. Todo nuevo módulo debe ser require desde aquí.
- `src/api/` → APIs REST y AJAX.
- `src/data/` → Capa de datos (wp_options CRUD).
- `src/page/` → Páginas admin y secciones.
- `src/templates/` → Templates reutilizables.
- `src/css/` → Estilos admin.
- `src/js/` → JavaScript admin.
- `src/hook/` → Hooks de WordPress (template_redirect, Elementor).
- `src/block/` → Meta box de páginas.

### Constantes
Usa las constantes definidas en `index.php`:
- `STPA_KEY` para prefijos de opciones y meta keys
- `STPA_CONFIG` para la configuración global
- `STPA_DIR` y `STPA_URL` para paths del plugin
- Nunca hardcodees strings como `'STPA'` o `'STPA_CONFIG'`

### Post Meta
Toda meta key del sistema usa prefijo `STPA_`. Definidas en `STPA_PAGE_CONFIG`:
- `STPA_KEY_CONFIG` → Configuración de página
- `STPA_PAGE_STATIC_ACTIVE` → Flag activo/inactivo
- `STPA_PAGE_STATIC_HTML_FILE` → Ruta al archivo HTML generado
- `STPA_PAGE_STATIC_CSS_FILE` → Ruta al archivo CSS generado
- `STPA_PAGE_STATIC_JS_FILE` → Ruta al archivo JS generado

### Output Directory
Los archivos estáticos se almacenan en `wp-content/uploads/{output_dir}/`. Usa `STPA_get_output_dir($post_id)` para obtener la ruta.

---

## 3. Validaciones de Seguridad

1. **Nunca** hardcodees tokens en el código. Usa `token_array_split` para el auto-updater.
2. **Siempre** sanitiza input: `$_POST`, `$_GET`, `$_REQUEST` deben pasar por `sanitize_text_field()`, `intval()`, etc.
3. **Siempre** valida nonces en handlers AJAX.
4. **Siempre** valida capabilities: `current_user_can('manage_options')`.
5. **Nunca** ejecutes `eval()` o `unserialize()` con datos externos.

---

## 4. Convenciones del Proyecto

### AJAX Endpoints
- Action registrada: `wp_ajax_{action}` donde action usa prefijo `stpa_`.
- Nonce action: `stpa_{action}_{id}` o `stpa_{action}`.
- Los handlers deben estar en `src/api/admin-ajax.php`.
- Respuesta siempre en JSON: `wp_send_json_success($data)` o `wp_send_json_error($message)`.

### REST API
- Namespace: `STPA` (`STPA_KEY`).
- Endpoints:
  - `POST /STPA/post-config/{id}` → Guarda config de generación.
  - `POST /STPA/html/{id}` → Guarda HTML generado.
- Autenticación via headers `X-WP-Nonce` y `api-key`.

### Hooks
- Acciones: `add_action('hook', 'callback', priority)`.
- Filtros: `add_filter('hook', 'callback', priority, args)`.
- No registres hooks en el scope global. Deben estar dentro de closures o funciones.

### Logging
- Usa siempre `FWUSystemLog::add(STPA_KEY, $message)` para errores.
- No uses `error_log()`, `var_dump()`, `print_r()` en producción.

---

## 5. Git Workflow

1. **Commits**: No hacer commits automaticamente, solo dar sugerencias de commits.

---

## 6. Secciones Globales

Las secciones globales permiten definir fragmentos HTML reutilizables. 
- Los archivos se guardan en `{output_dir}/section-global/{key}.html`
- Al servir una página estática, se buscan elementos que coincidan por tag, `#id`, o `.class` y se reemplazan.
- Los AJAX handlers están en `src/api/admin-ajax.php`.
- La vista admin está en `src/page/sections/global-sections.php`.

---

## 7. Lo que NO debes hacer

- ✗ NO modifiques `index.php` (plugin header) sin autorización explícita.
- ✗ NO elimines el prefijo `STPA_` de ninguna clase/función.
- ✗ NO agregues dependencias npm/composer sin autorización explícita.
- ✗ NO edites archivos en `libs/` (vendor de Composer).
- ✗ NO uses sintaxis moderna de PHP (>=7.0).
- ✗ NO hardcodees URLs o paths — usa `STPA_URL`, `STPA_DIR`.
- ✗ NO añadas archivos nuevos sin require desde `src/_.php` o desde subcarpetas `src/*/_.php`.
