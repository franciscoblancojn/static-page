<?php

function STPA_get_output_dir($post_id = null)
{
    $config = get_option(STPA_CONFIG, []);
    $output_type = $config['output_type'] ?? 'default';
    $output_dir = !empty($config['output_dir']) ? sanitize_title($config['output_dir']) : STPA_KEY;

    if ($post_id) {
        $post = get_post($post_id);
    }

    switch ($output_type) {
        case 'slug':
            if ($post) {
                $slug = $post->post_name;
                $subdir = "page-{$post_id}";
            } else {
                $subdir = "page-{$post_id}";
            }
            break;

        case 'id_group':
            $group = floor($post_id / 100) * 100;
            $subdir = "group-{$group}";
            break;

        case 'parent':
            if ($post && $post->post_parent) {
                $parent = get_post($post->post_parent);
                $subdir = $parent ? $parent->post_name : 'sin-padre';
            } else {
                $subdir = 'sin-padre';
            }
            break;

        default:
            $subdir = '';
            break;
    }

    $upload_dir = wp_upload_dir();
    if ($subdir) {
        return $upload_dir['basedir'] . '/' . $output_dir . '/' . $subdir;
    }
    return $upload_dir['basedir'] . '/' . $output_dir;
}
