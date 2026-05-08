<?php


add_action('rest_api_init', function () {
    // /wp-json/STPA/html/1
    register_rest_route(STPA_KEY, '/html/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => function ($request) {

            $post_id = $request['id'];

            $post = get_post($post_id);

            if (!$post) {
                return new WP_Error('not_found', 'Page not found');
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
        },
        'permission_callback' => '__return_true'
    ]);
});
