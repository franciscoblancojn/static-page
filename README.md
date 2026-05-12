# Static Page

Generate optimized static HTML versions of your WordPress pages. Improves performance by inlining CSS, removing the admin bar, fixing lazy images, and serving a fully self-contained HTML file instead of dynamically rendering the page on each visit.

**Contributors:** Francisco Blanco  
**Tags:** static page, performance, cache, optimization, elementor  
**Requires at least:** 5.0  
**Tested up to:** 6.0  
**Stable tag:** 1.0.1  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

---

## Description

Static Page converts your WordPress pages into **standalone static HTML**. Instead of loading WordPress on every visit (running PHP queries, hooks, filters), the plugin serves a pre-generated HTML file directly — resulting in faster load times and reduced server load.

### Features

- **One-click static generation** — from the page editor, click "Generar Pagina Estatica y Guardar"
- **CSS inlining** — combines all external (`<link>`) and internal (`<style>`) CSS into a single `<style>` tag
- **CSS minification** — removes comments, whitespace, and redundant characters
- **`@import` handling** — moves all `@import` rules to the top of the combined stylesheet (required by CSS spec)
- **Admin bar removal** — strips `#wpadminbar` and fixes the top margin
- **Lazy image restoration** — replaces `data-src`, `data-lazy-src`, etc. with real `src` attributes
- **HTML minification** — removes HTML comments, whitespace, and newlines
- **Elementor compatibility** — respects Elementor edit/preview modes
- **REST API** — endpoints for fetching page content and saving/retrieving static HTML
- **API key authentication** — secure access for external/CI build systems

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
4. The page will be automatically saved (published/updated)

Once generated, the plugin serves the static HTML directly, bypassing WordPress rendering entirely.

### Disable Static Page for a Page

Simply uncheck **"Activar Carga de Pagina Estatica"** and save the page. The page will revert to normal WordPress rendering.

### Regenerate

Click **"Generar Pagina Estatica y Guardar"** again at any time to regenerate the static HTML with the latest content and options.

---

## REST API

The plugin registers custom REST API routes under the `STPA` namespace.

### `GET /wp-json/STPA/html/{id}`

Returns the rendered page content (post content with `the_content` filter applied).

### `POST /wp-json/STPA/html/{id}`

Save the generated static HTML for a page.

**Authentication (choose one):**
- **WordPress nonce:** send `X-WP-Nonce` header (for admin requests)
- **API key:** send `api-key` header (for external/CI requests)

**Request body:**
```json
{
  "html": "<!DOCTYPE html>..."
}
```

---

## Architecture

```
post.php (meta box UI)
    ↓ config from checkboxes
procesing-html.php (client-side processing)
    ├── getCode()          — fetch page HTML via URL
    ├── eliminarWpadminbar() — remove admin bar, fix margin
    ├── convinarCssExterno() — download & inline external CSS
    ├── convinarCssInterno() — collect & combine <style> tags
    ├── cssMinfy()         — minify CSS (safe: no comma-in-string corruption)
    ├── fixLazyImages()    — restore lazy-loaded images
    └── htmlMinify()       — minify final HTML
    ↓ POST /wp-json/STPA/html/{id}
api/set-html.php            — saves HTML to post meta (STPA_PAGE_STATIC_HTML)
    ↓
hook/template_redirect.php  — serves static HTML, exits early
```

---

## API Endpoints (internal classes)

| Class | Route | Method | Purpose |
|-------|-------|--------|---------|
| `STPA_API_GET_HTML` | `/html/(?P<id>\d+)` | GET | Return page content as HTML |
| `STPA_API_SET_HTML` | `/html/(?P<id>\d+)` | POST | Save generated static HTML |

Both extend `STPA_API` which provides:
- API key generation & validation (`getApiKey()` / `validateApiKey()`)
- User authentication via WordPress nonce (`validateUser()`)
- Endpoint-specific validation (`validateEnpoint()`)

---

## Developer

- **Name:** Francisco Blanco
- **Website:** https://franciscoblanco.vercel.app/
- **Email:** blancofrancisco34@gmail.com

## Repository

https://github.com/franciscoblancojn/static-page

## License

This plugin is distributed under the terms of the GNU General Public License v2.0 or later.

