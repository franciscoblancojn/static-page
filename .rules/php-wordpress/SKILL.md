# PHP WordPress Skill

## PHP Compatibility (7.0+)
- **NO** nullsafe `?->`
- **NO** named arguments
- **NO** `match` expression
- **NO** readonly properties
- **NO** union types
- **NO** typed properties (except in docblocks)
- **NO** `::class` on objects
- **YES** `??` (null coalescing)
- **YES** `...` (splat operator)
- **YES** `<=>` (spaceship)
- **YES** short array syntax `[]`
- **YES** `use` for anonymous functions

## WordPress Coding Standards
- Classes: `STPA_UPPER_SNAKE`
- Functions: `STPA_snake_case`
- Constants: `STPA_UPPER_SNAKE`
- Hooks: `add_action('hook', 'callback')`, `add_filter('hook', 'callback')`
- Nonces: `wp_verify_nonce()` or `check_ajax_referer()`
- Capabilities: `current_user_can('manage_options')`
- Sanitization: `sanitize_text_field()`, `intval()`, `esc_url_raw()`
- Escaping: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`

## AJAX
- Action: `wp_ajax_stpa_*`
- Nonce: `stpa_{action}_{id}` or `stpa_{action}`
- Response: `wp_send_json_success()` / `wp_send_json_error()`

## REST API
- Namespace: `STPA`
- Auth: `X-WP-Nonce` + `api-key` headers
