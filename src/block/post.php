<?php

class STPA_PAGE_CONFIG
{
    const KEY_CONFIG = STPA_KEY . '_KEY_CONFIG';
    const KEY_ACTIVE = STPA_KEY . '_PAGE_STATIC_ACTIVE';
    const KEY_CSS_EXTERNO = STPA_KEY . '_PAGE_STATIC_CSS_EXTERNO';
    const KEY_CSS_INTERNO = STPA_KEY . '_PAGE_STATIC_CSS_INTERNO';
    const KEY_JS_EXTERNO = STPA_KEY . '_PAGE_STATIC_JS_EXTERNO';
    const KEY_JS_INTERNO = STPA_KEY . '_PAGE_STATIC_JS_INTERNO';
    const KEY_HTML = STPA_KEY . '_PAGE_STATIC_HTML';

    const CONFIG = [
        self::KEY_ACTIVE => "Activar Carga de Pagina Estatica",
        self::KEY_CSS_EXTERNO => "Procesar CSS Externo",
        self::KEY_CSS_INTERNO => "Procesar CSS Interno",
        self::KEY_JS_EXTERNO  => "Procesar JS Externo (Beta)",
        self::KEY_JS_INTERNO => "Procesar JS Interno (Beta)",
    ];

    public static function init()
    {
        add_action('add_meta_boxes', [self::class, 'addMetaBox']);
        add_action('save_post', [self::class, 'save']);
    }

    /**
     * Registrar Postbox
     */
    public static function addMetaBox()
    {
        add_meta_box(
            STPA_KEY . '_page_config',
            'Configuración Pagina Estatica',
            [self::class, 'render'],
            ['page'], // tipos de post
            'side',
            'high'
        );
    }

    /**
     * Render del Postbox
     */
    public static function render($post)
    {
        wp_nonce_field(STPA_KEY . '_page_config_nonce', STPA_KEY . '_page_config_nonce');

        $config = get_post_meta($post->ID, self::KEY_CONFIG, true);
        foreach (
            self::CONFIG as $key => $value
        ) {
?>
            <div class="stpa-field">
                <label>
                    <input
                        type="checkbox"
                        name=<?= $key ?>
                        value="1"
                        <?= checked($config[$key] ?? false, '1', false) ?>>

                    <?= $value ?>
                </label>
            </div>
<?php
        }
    }

    /**
     * Guardar datos
     */
    public static function save($post_id)
    {
        if (
            !isset($_POST[STPA_KEY . '_page_config_nonce']) ||
            !wp_verify_nonce($_POST[STPA_KEY . '_page_config_nonce'], STPA_KEY . '_page_config_nonce')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $config = [];
        foreach (
            self::CONFIG as $key => $value
        ) {
            $enabled = isset($_POST[$key]) ? '1' : '0';
            $config[$key] = $enabled;
        }
        update_post_meta(
            $post_id,
            self::KEY_CONFIG,
            $config
        );
        if ($config[self::KEY_ACTIVE]) {
            $post = get_post($post_id);
            if ($post) {
                ob_start();
                echo apply_filters('the_content', $post->post_content);
                $html = ob_get_clean();
                $url = get_permalink($post_id);
                $html = STPA_PROCESS_HTML::procesingHtml(
                    $html,
                    $url,
                    $config
                );
                update_post_meta(
                    $post_id,
                    self::KEY_HTML,
                    $html
                );
            }
        } else {
            delete_post_meta(
                $post_id,
                self::KEY_HTML,
            );
        }
    }
}

STPA_PAGE_CONFIG::init();
