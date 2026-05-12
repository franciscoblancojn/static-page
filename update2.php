<?php

if (!function_exists("github_updater_plugin_wordpress2")) {

    function github_updater_plugin_wordpress2($config)
    {

        if (!is_admin()) {
            return;
        }

        /**
         * Evitar demasiadas consultas a GitHub
         * solo refrescar cada 6 horas
         */
        if (!get_transient('github_updater_plugin_wordpress_check')) {

            delete_site_transient('update_plugins');

            set_transient(
                'github_updater_plugin_wordpress_check',
                true,
                1 * HOUR_IN_SECONDS
            );
        }

        add_filter('site_transient_update_plugins', function ($transient) use ($config) {

            if (empty($transient->checked)) {
                return $transient;
            }

            /**
             * Configuración básica
             */
            $plugin_slug = basename(rtrim($config['dir'], '/'));
            $plugin_file_php = $config['file'];
            $plugin_file = $plugin_slug . '/' . $plugin_file_php;

            /**
             * URL API GitHub
             */
            $github_api_url = 'https://api.github.com/repos/' .
                $config['path_repository'] .
                '/releases/latest';

            /**
             * Request a GitHub
             * NO necesita token si el repo es público
             */
            $response = wp_remote_get($github_api_url, [
                'headers' => [
                    'User-Agent' => 'WordPress-GitHub-Updater',
                    'Accept' => 'application/vnd.github+json',
                ],
                'timeout' => 20,
            ]);

            /**
             * Error request
             */
            if (is_wp_error($response)) {
                return $transient;
            }

            /**
             * Obtener release
             */
            $release = json_decode(
                wp_remote_retrieve_body($response)
            );

            if (
                empty($release) ||
                !isset($release->tag_name)
            ) {
                return $transient;
            }

            /**
             * Versión latest GitHub
             */
            $latest_version = ltrim($release->tag_name, 'v');

            /**
             * Obtener versión instalada
             */
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $plugin_path = $config['dir'] . $plugin_file_php;

            $plugin_data = get_plugin_data($plugin_path);

            $current_version = $plugin_data['Version'];

            /**
             * Comparar versiones
             */
            if (version_compare($current_version, $latest_version, '<')) {

                /**
                 * Buscar ZIP personalizado en assets
                 */
                $package_url = null;

                if (
                    isset($release->assets) &&
                    is_array($release->assets)
                ) {

                    foreach ($release->assets as $asset) {

                        /**
                         * Buscar primer ZIP
                         */
                        if (
                            isset($asset->browser_download_url) &&
                            str_ends_with($asset->name, '.zip')
                        ) {

                            $package_url = $asset->browser_download_url;
                            break;
                        }
                    }
                }

                /**
                 * Fallback:
                 * usar zipball oficial GitHub
                 */
                if (!$package_url && isset($release->zipball_url)) {
                    $package_url = $release->zipball_url;
                }

                /**
                 * Registrar actualización
                 */
                $transient->response[$plugin_file] = (object) [
                    'slug'        => $plugin_slug,
                    'plugin'      => $plugin_file,
                    'new_version' => $latest_version,
                    'package'     => $package_url,
                    'url'         => 'https://github.com/' . $config['path_repository'],
                ];
            }

            return $transient;
        });

        /**
         * Botón actualizar
         */
        add_filter(
            'plugin_action_links_' . $config['basename'],
            function ($links, $file) use ($config) {

                if ($file !== $config['basename']) {
                    return $links;
                }

                $actualizar_url = wp_nonce_url(
                    admin_url(
                        'update.php?action=upgrade-plugin&plugin=' . $file
                    ),
                    'upgrade-plugin_' . $file
                );

                $links[] = '
                    <a 
                        href="' . esc_url($actualizar_url) . '" 
                        style="color:#2271b1;font-weight:600;"
                    >
                        Actualizar
                    </a>
                ';

                return $links;
            },
            10,
            2
        );
    }
}