# Static Page

Generate optimized static HTML files from your WordPress pages. Inlines CSS, removes the admin bar, fixes lazy images, and serves a pre-generated static HTML file instead of dynamically rendering the page on each visit.

**Contributors:** Francisco Blanco  
**Tags:** static page, performance, cache, optimization, elementor  
**Requires at least:** 5.0  
**Tested up to:** 6.0  
**Stable tag:** 1.3.5
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
2. In the sidebar meta box **"Configuración Pagina Estatica"**, check the options you want:
   - **Activar Carga de Pagina Estatica** — required, enables static page serving
   - **Procesar CSS Externo** — inline all `<link rel="stylesheet">` from `wp-content/`
   - **Procesar CSS Interno** — combine all `<style>` tags into one
   - **Procesar JS Externo (Beta)** — inline external JavaScript files
   - **Procesar JS Interno (Beta)** — combine inline scripts
3. Click **"Generar Pagina Estatica y Guardar"**

The plugin will:
1. Save the config via `POST /wp-json/STPA/post-config/{id}`
2. Fetch the page HTML with `?STPA_DISABLE=1` (bypasses static serving)
3. Process the HTML (inline CSS, minify, remove admin bar, fix images)
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
    "STPA_PAGE_STATIC_CSS_INTERNO": true
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
    ├── convinarCssExterno()   — download & inline <link> CSS, minify
    ├── convinarCssInterno()   — collect & combine <style> tags
    ├── cssMinfy()             — minify CSS (safe: no comma-in-string corruption)
    ├── fixLazyImages()        — restore lazy-loaded images
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

