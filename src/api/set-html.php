<?php

class STPA_API_SET_HTML extends STPA_API
{
    static protected $URL_ENDPOINT = "/html/(?P<id>\d+)";

    public static function validateEnpoint($request)
    {
        $post_id = (int) $request['id'];
        if (!current_user_can('edit_post', $post_id)) {
            throw new Exception('No tienes permiso para editar esta página');
        }
    }
    public static function permission_callback()
    {
        return is_user_logged_in();
    }
    public static function enpoint($request)
    {
        $post_id = (int) $request['id'];
        $post = get_post($post_id);

        if (!$post) {
            throw new Exception('Page not found');
        }

        $html = $request->get_param('html');

        if (is_null($html)) {
            throw new Exception('El parámetro html es requerido');
        }

        $dir = STPA_get_output_dir($post_id);
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        $file = $dir . "/page-{$post_id}.html";
        file_put_contents($file, $html);
        update_post_meta(
            $post_id,
            STPA_PAGE_CONFIG::KEY_HTML_FILE,
            $file
        );

        $css = $request->get_param('css');
        if (!is_null($css) && $css !== '') {
            file_put_contents($dir . "/page-{$post_id}.css", $css);
        }

        $js = $request->get_param('js');
        if (!is_null($js) && $js !== '') {
            file_put_contents($dir . "/page-{$post_id}.js", $js);
        }

        return [
            'success' => true,
            'message' => 'Página estática guardada correctamente.',
        ];
    }
}
STPA_API_SET_HTML::init();
