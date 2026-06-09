# Static Page

Generate optimized static HTML files from your WordPress pages. Inlines CSS, removes the admin bar, fixes lazy images, and serves a pre-generated static HTML file instead of dynamically rendering the page on each visit.

**Contributors:** Francisco Blanco  
**Tags:** static page, performance, cache, optimization, elementor  
**Requires at least:** 5.0  
**Tested up to:** 6.0  
**Stable tag:** 1.9.1
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

---

## Description

Static Page converts your WordPress pages into **standalone static HTML files** stored in `wp-content/uploads/STPA/`. Instead of loading WordPress on every visit (running PHP queries, hooks, filters), the plugin serves the pre-generated HTML file directly — resulting in faster load times and reduced server load.

### Features

- **One-click static generation** — from the page editor, click "Generar Pagina Estatica y Guardar"
- **Config saved via REST API** — checkbox settings are sent to `/wp-json/STPA/post-config/{id}` before generation
- **Static file storage** — generated HTML is saved to `wp-content/uploads/STPA/page-{id}.html`
- **CSS inlining** — combines all external (`<link>`) and internal (`<style>`) CSS into a single `<style>` tag
- **CSS minification** — removes comments, whitespace, and redundant characters
- **`@import` handling** — moves all `@import` rules to the top of the combined stylesheet (required by CSS spec)
- **HTML comment stripping** — removes `<!-- -->` wrappers commonly found inside WordPress `<style>` tags
- **Admin bar removal** — strips `#wpadminbar` and fixes the top margin
- **Lazy image restoration** — replaces `data-src`, `data-lazy-src`, etc. with real `src` attributes
- **HTML minification** — removes HTML comments, whitespace, and newlines
- **Elementor compatibility** — respects Elementor edit/preview modes and `?action=elementor`
- **CSS/JS ignore** — select specific CSS or JS files to exclude from processing; ignored files are removed from the final HTML
- **CSS/JS process selection** — with "Procesar CSS/JS Externo" enabled, choose exactly which non-ignored files to process (grouped by plugin/theme with select all)
- **Collapsible file lists** — ignore and process lists are collapsible, allowing you to compress them without disabling the option
- **External file generation only when active** — the `.css`/`.js` file is only written to disk when "Generar CSS/JS Externo" is checked
- **`<a tabindex="0">` to `<span>`** — automatically replaces anchor tags with `tabindex="0"` by semantic `<span>` elements
- **Disable query param** — append `?STPA_DISABLE=1` to view the original WordPress-rendered page
- **REST API authentication** — requires both `X-WP-Nonce` and `api-key` headers

---

## Installation

1. Download the plugin from the [GitHub repository](https://github.com/franciscoblancojn/static-page)
2. Go to **Plugins → Add New** in your WordPress admin
3. Click **Upload Plugin**, select the `.zip` file, and click **Install Now**
4. Activate the plugin

---

## Usage

### Generate a Static Page

1. Edit any **Page** in WordPress
2. In the meta box **"Configuración Pagina Estatica"** (below the editor), check the options you want:
   - **Activar Carga de Pagina Estatica** — required, enables static page serving
   - Expand **Configuración de CSS** to access:
      - **Ignorar CSS** — select specific CSS files to exclude; they are removed from the final HTML (collapsible list)
      - **Procesar CSS Externo** — inline `<link rel="stylesheet">` files from `wp-content/`. Enables a collapsible list to select which non-ignored files to process (grouped by plugin/theme)
      - **Procesar CSS Interno** — combine all `<style>` tags into one
      - **Eliminar CSS No Usado** — purge unused CSS rules from the combined stylesheet
      - **Generar CSS Externo** — save CSS as a separate file instead of inlining (only writes to disk when active)
   - Expand **Configuración de JS** to access:
      - **Ignorar JS** — select specific JS files to exclude; they are removed from the final HTML (collapsible list)
      - **Procesar JS Externo (Beta)** — inline external JavaScript files. Enables a collapsible list to select which non-ignored files to process (grouped by plugin/theme)
      - **Procesar JS Interno (Beta)** — combine inline scripts
      - **Generar JS Externo** — save JS as a separate file instead of inlining
3. Click **"Generar Pagina Estatica y Guardar"**

The plugin will:
1. Save the config via `POST /wp-json/STPA/post-config/{id}`
2. Fetch the page HTML with `?STPA_DISABLE=1` (bypasses static serving)
3. Process the HTML (inline CSS, minify, remove admin bar, fix images, replace `<a tabindex="0">` with `<span>`)
4. Save the final HTML to `wp-content/uploads/STPA/page-{id}.html`
5. Reload the page

Once generated, the plugin serves the static HTML file directly via `file_get_contents()`, bypassing WordPress rendering entirely.

### Disable Static Page for a Page

Uncheck **"Activar Carga de Pagina Estatica"**, generate again, or simply delete the file at `wp-content/uploads/STPA/page-{id}.html`.

### Regenerate

Click **"Generar Pagina Estatica y Guardar"** again at any time to overwrite the static HTML file.

---

## REST API

All endpoints require both **`X-WP-Nonce`** and **`api-key`** headers. The API key is auto-generated on plugin activation (stored in `wp_options` under `STPA_API_KEY`).

### `POST /wp-json/STPA/post-config/{id}`

Save the generation config (checkbox states) for a page.

```json
{
  "config": {
    "STPA_PAGE_STATIC_ACTIVE": true,
    "STPA_PAGE_STATIC_CSS_EXTERNO": true,
    "STPA_PAGE_STATIC_CSS_INTERNO": true,
    "STPA_PAGE_STATIC_CSS_IGNORE": true,
    "STPA_PAGE_STATIC_CSS_IGNORE_LIST": ["https://example.com/wp-content/themes/theme/style.css"],
    "STPA_PAGE_STATIC_CSS_EXTERNO_PROCESS": ["https://example.com/wp-content/plugins/plugin/styles.css"],
    "STPA_PAGE_STATIC_JS_IGNORE": true,
    "STPA_PAGE_STATIC_JS_IGNORE_LIST": ["https://example.com/wp-content/plugins/plugin/script.js"],
    "STPA_PAGE_STATIC_JS_EXTERNO_PROCESS": ["https://example.com/wp-content/themes/theme/app.js"]
  }
}
```

### `POST /wp-json/STPA/html/{id}`

Save the generated static HTML. The HTML is written to `wp-content/uploads/STPA/page-{id}.html`.

```json
{
  "html": "<!DOCTYPE html>..."
}
```

---

## Architecture

```
post.php (meta box UI)
    ↓ POST /wp-json/STPA/post-config/{id}
api/set-post-config.php     — saves config to post meta
    ↓
procesing-html.php (client-side JS processing)
    ├── getCode()              — fetch page HTML via URL (?STPA_DISABLE=1)
    ├── eliminarWpadminbar()   — remove #wpadminbar, fix top margin
    ├── removeIgnoredAssets()  — remove ignored CSS/JS from DOM unconditionally
    ├── convinarCssExterno()   — download & inline user-selected <link> CSS (filtered by process list), minify
    ├── convinarCssInterno()   — collect & combine <style> tags
    ├── cssMinfy()             — minify CSS (safe: no comma-in-string corruption)
    ├── fixLazyImages()        — restore lazy-loaded images
    ├── replaceAnchorsWithTabindexZero() — convert <a tabindex="0"> to <span>
    └── htmlMinify()           — minify final HTML
    ↓ POST /wp-json/STPA/html/{id}
api/set-html.php            — writes HTML to wp-content/uploads/STPA/page-{id}.html
    ↓
hook/template_redirect.php  — reads file & serves it, exits early
```

---

## File Storage

Generated static files are stored at:

```
wp-content/uploads/STPA/page-{id}.html
```

This directory is created automatically on first generation. Files are served directly via `file_get_contents()` when the page is requested and the static feature is active.

---

## Internal Classes

| Class | Route | Method | Purpose |
|-------|-------|--------|---------|
| `STPA_API_SET_POST_CONFIG` | `/post-config/(?P<id>\d+)` | POST | Save page generation config |
| `STPA_API_SET_HTML` | `/html/(?P<id>\d+)` | POST | Save generated static HTML to file |

Both extend `STPA_API` which provides:
- API key validation (`validateApiKey()`)
- WordPress nonce validation (`validateUser()`)
- Endpoint-specific permission checks (`validateEnpoint()`)

---

## Developer

- **Name:** Francisco Blanco
- **Website:** https://franciscoblanco.vercel.app/
- **Email:** blancofrancisco34@gmail.com

## Repository

https://github.com/franciscoblancojn/static-page

## License

This plugin is distributed under the terms of the GNU General Public License v2.0 or later.

