# Static Page 🚀

**Version:** 1.10.1 | **License:** GPLv2+

Static Page convierte tus páginas de WordPress en **archivos HTML estáticos optimizados** almacenados en `wp-content/uploads/STPA/`. En lugar de cargar WordPress en cada visita (ejecutando consultas PHP, hooks y filtros), el plugin sirve el archivo HTML pre-generado directamente — resultando en tiempos de carga más rápidos y menor carga en el servidor.

---

## ✨ Características

- ⚡ **Generación con un clic** — Desde el editor de páginas, haz clic en "Generar Pagina Estatica y Guardar".
- 🎨 **Inlining de CSS** — Combina todo el CSS externo (`<link>`) e interno (`<style>`) en una sola etiqueta `<style>`.
- ✂️ **Minificado de CSS** — Elimina comentarios, espacios en blanco y caracteres redundantes.
- 🧹 **Minificado de HTML** — Elimina comentarios HTML, espacios y saltos de línea.
- 🔄 **Manejo de `@import`** — Mueve todas las reglas `@import` al inicio del stylesheet combinado.
- 🚫 **Eliminación de barra de admin** — Elimina `#wpadminbar` y corrige el margen superior.
- 🖼️ **Restauración de imágenes lazy** — Reemplaza `data-src`, `data-lazy-src`, etc. con atributos `src` reales.
- 📦 **Secciones Globales** — Define fragmentos HTML reutilizables (headers, footers) que se actualizan automáticamente en todas las páginas estáticas.
- 🎯 **Ignorado selectivo de CSS/JS** — Selecciona archivos específicos para excluir del procesamiento.
- 🧩 **Procesamiento selectivo** — Elige exactamente qué archivos no ignorados procesar (agrupados por plugin/theme).
- 🏗️ **Generación de CSS/JS externos** — Guarda CSS/JS como archivos separados.
- 🔗 **Reemplazo de `<a tabindex="0">`** — Convierte automáticamente enlaces con `tabindex="0"` a elementos `<span>` semánticos.
- 🔌 **Compatibilidad con Elementor** — Respeta los modos de edición/previsualización de Elementor.
- 🔄 **Auto-Update vía GitHub** — El plugin se actualiza automáticamente desde GitHub Releases.
- 📋 **Sistema de Logs** — Registro de actividad del plugin accesible desde la barra de administración.
- 🔑 **Autenticación REST API** — Requiere headers `X-WP-Nonce` y `api-key`.

---

## 📋 Requisitos

- WordPress 5.0+
- PHP 7.0+
- Plugin [Elementor](https://wordpress.org/plugins/elementor/) (opcional, para auto-regeneración al guardar)

---

## ⚙️ Instalación

1. Descarga el plugin desde [aquí](https://github.com/franciscoblancojn/static-page/archive/refs/heads/master.zip).
2. Ve a **Plugins → Añadir Nuevo** en tu WordPress admin.
3. Haz clic en **Subir Plugin**, selecciona el archivo `.zip`, y haz clic en **Instalar Ahora**.
4. Activa el plugin.
5. Ve a **Static Page → Páginas Estáticas** para ver la configuración.

---

## 🚀 Uso

### Generar una Página Estática

1. Edita cualquier **Página** en WordPress.
2. En el meta box **"Configuración Pagina Estatica"** (debajo del editor), configura las opciones:
   - **Activar Carga de Pagina Estatica** — requerido, habilita el servido de página estática.
   - Expande **Configuración de CSS** para acceder a opciones de ignorado, procesado y purga.
   - Expande **Configuración de JS** para opciones de ignorado y procesado.
3. Haz clic en **"Generar Pagina Estatica y Guardar"**.

El plugin:
1. Guarda la configuración vía REST API.
2. Obtiene el HTML de la página con `?STPA_DISABLE=1`.
3. Procesa el HTML (inline CSS, minificar, admin bar removal, lazy images).
4. Guarda el HTML final en `wp-content/uploads/STPA/page-{id}.html`.

Una vez generado, el plugin sirve el archivo HTML estático directamente, evitando la renderización de WordPress.

### Secciones Globales

1. Ve a **Static Page → Secciones Globales**.
2. Selecciona una **página de origen** y una **clave de búsqueda** (ej: `header`, `#seccion1`, `.clase`).
3. Haz clic en **Crear sección global**.
4. El fragmento HTML extraído se guarda y se reemplazará automáticamente en todas las páginas estáticas que contengan elementos coincidentes.

---

## 🌐 REST API

Todos los endpoints requieren los headers **`X-WP-Nonce`** y **`api-key`**.

### `POST /wp-json/STPA/post-config/{id}`
Guarda la configuración de generación para una página.

### `POST /wp-json/STPA/html/{id}`
Guarda el HTML estático generado.

---

## 🏗️ Arquitectura

```
post.php (meta box UI)
    ↓ POST /wp-json/STPA/post-config/{id}
api/set-post-config.php     — guarda config en post meta
    ↓
procesing-html.php (JS cliente-side)
    ├── getCode()              — fetch página (?STPA_DISABLE=1)
    ├── eliminarWpadminbar()   — eliminar #wpadminbar
    ├── removeIgnoredAssets()  — eliminar CSS/JS ignorados
    ├── convinarCssExterno()   — descargar e inline CSS externo
    ├── convinarCssInterno()   — combinar <style> tags
    ├── cssMinfy()             — minificar CSS
    ├── fixLazyImages()        — restaurar imágenes lazy
    ├── replaceAnchorsWithTabindexZero() — <a tabindex="0"> → <span>
    └── htmlMinify()           — minificar HTML final
    ↓ POST /wp-json/STPA/html/{id}
api/set-html.php            — escribe HTML en wp-content/uploads/STPA/
    ↓
hook/template_redirect.php  — lee archivo, reemplaza secciones globales, sirve
```

---

## 📁 Almacenamiento

```
wp-content/uploads/STPA/
├── page-{id}.html               # HTML estático generado
├── page-{id}.css                # CSS externo (opcional)
├── page-{id}.js                 # JS externo (opcional)
└── section-global/
    ├── {key}.html                # HTML de sección global
    └── {key}.json                # Metadatos de sección global
```

---

## 🧩 Clases Internas

| Clase | Archivo | Propósito |
|---|---|---|
| `STPA_API` | `src/api/api.php` | Base REST API con validación de API key y nonce |
| `STPA_API_SET_POST_CONFIG` | `src/api/set-post-config.php` | Guarda config de página |
| `STPA_API_SET_HTML` | `src/api/set-html.php` | Guarda HTML estático a archivo |
| `STPA_PAGE_CONFIG` | `src/block/post.php` | Meta box en editor de páginas |
| `STPA_USE_DATA_CONFIG` | `src/data/config.php` | Config global del plugin |
| `STPA_ASSETS` | `src/hook/assets.php` | Sirve CSS/JS estáticos |

---

## 🎣 Hooks

### Acciones
| Hook | Archivo | Propósito |
|---|---|---|
| `admin_menu` | `page/add.php` | Registrar menú admin |
| `add_meta_boxes` | `block/post.php` | Registrar meta box |
| `save_post` | `block/post.php` | Guardar config |
| `template_redirect` (priority 1) | `hook/template_redirect.php` | Servir HTML estático |
| `rest_api_init` | `api/api.php` | Registrar rutas REST |
| `wp_ajax_stpa_*` | `api/admin-ajax.php` | Handlers AJAX |

### Filtros
| Hook | Archivo | Propósito |
|---|---|---|
| `query_vars` | `hook/assets.php` | Registrar query var |
| `site_transient_update_plugins` | `FWUUpdate` | Auto-updater |
| `plugin_action_links_{basename}` | `FWUUpdate` | Link actualizar |

---

## 👨‍💻 Developer

- **Name:** Francisco Blanco
- **Website:** https://franciscoblanco.vercel.app/
- **Email:** blancofrancisco34@gmail.com

## 📦 Repositorio

https://github.com/franciscoblancojn/static-page

## 📄 Licencia

Este plugin se distribuye bajo los términos de la GNU General Public License v2.0 o posterior.
