<?php

class STPA_API_GET_HTML extends STPA_API
{
    static protected $URL_ENDPOINT = "/html/(?P<id>\d+)";
    static protected $METHODS = 'GET';
    public static function enpoint($request)
    {
        $post_id = $request['id'];

        $post = get_post($post_id);

        if (!$post) {
            throw new Exception('Page not found');
        }

        setup_postdata($post);

        ob_start();

        echo apply_filters('the_content', $post->post_content);

        $html = ob_get_clean();

        wp_reset_postdata();

        return [
            'success' => true,
            'html' => $html,
        ];
    }
}
STPA_API_GET_HTML::init();
