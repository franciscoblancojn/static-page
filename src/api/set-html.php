<?php

class STPA_API_SET_HTML extends STPA_API
{
    static protected $URL_ENDPOINT = "/html/(?P<id>\d+)";

    public static function validateEnpoint($request)
    {
        $post_id = (int) $request['id'];
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error('forbidden', 'No tienes permiso para editar esta página', ['status' => 403]);
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
            return new WP_Error('not_found', 'Page not found', ['status' => 404]);
        }

        $html = $request->get_param('html');

        if (is_null($html)) {
            return new WP_Error('missing_param', 'El parámetro html es requerido', ['status' => 400]);
        }

        update_post_meta(
            $post_id,
            STPA_PAGE_CONFIG::KEY_HTML,
            $html
        );

        return [
            'success' => true,
            'message' => 'Página estática guardada correctamente.',
        ];
    }
}
STPA_API_SET_HTML::init();
