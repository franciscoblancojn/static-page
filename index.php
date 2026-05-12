<?php
/*
Plugin Name: Static Page
Plugin URI: https://github.com/franciscoblancojn/static-page
Description: It is an plugin of wordpress, for create static page of your pages.
Version: 1.1.0
Author: franciscoblancojn
Author URI: https://franciscoblanco.vercel.app/
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wc-static-page
*/

if (!function_exists('is_plugin_active'))
    require_once(ABSPATH . '/wp-admin/includes/plugin.php');

require_once __DIR__ . '/libs/autoload.php';

//STPA_
define("STPA_KEY", 'STPA');
define("STPA_MODE_DEV", true);
define("STPA_KEY_SEPARETE", '____STPA____');
define("STPA_CONFIG", 'STPA_CONFIG');
define("STPA_CONTENT", 'STPA_CONTENT');
define("STPA_LOG", true);
define("STPA_LOG_KEY", "STPA_LOG");
define("STPA_LOG_COUNT", 100);
define("STPA_BASENAME", plugin_basename(__FILE__));
define("STPA_DIR", plugin_dir_path(__FILE__));
define("STPA_URL", plugin_dir_url(__FILE__));

require_once STPA_DIR . 'update2.php';
github_updater_plugin_wordpress2([
    'basename' => STPA_BASENAME,
    'dir' => STPA_DIR,
    'file' => "index.php",
    'path_repository' => 'franciscoblancojn/static-page',
    'branch' => 'master'
]);
// github_updater_plugin_wordpress([
//     'basename' => STPA_BASENAME,
//     'dir' => STPA_DIR,
//     'file' => "index.php",
//     'path_repository' => 'franciscoblancojn/static-page',
//     'branch' => 'master',
//     'token_array_split' => [
//         "g",
//         "h",
//         "p",
//         "_",
//         "G",
//         "4",
//         "W",
//         "E",
//         "W",
//         "F",
//         "p",
//         "V",
//         "U",
//         "E",
//         "F",
//         "V",
//         "x",
//         "F",
//         "U",
//         "n",
//         "b",
//         "M",
//         "k",
//         "P",
//         "R",
//         "x",
//         "o",
//         "f",
//         "t",
//         "Y",
//         "8",
//         "z",
//         "j",
//         "t",
//         "4",
//         "E",
//         "x",
//         "b",
//         "i",
//         "9"
//     ]
// ]);

use franciscoblancojn\wordpress_utils\FWUSystemLog;

if (is_admin()) {
    FWUSystemLog::init(STPA_KEY);
}

require_once STPA_DIR . 'src/_.php';