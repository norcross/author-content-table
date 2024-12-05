<?php
/**
 * Plugin Name: Author Content Table
 * Plugin URI:  https://github.com/norcross/author-content-table
 * Description: Provide a single post table for all content based on user.
 * Version:     0.0.1
 * Author:      Andrew Norcross
 * Author URI:  https://andrewnorcross.com
 * Text Domain: author-content-table
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package     AuthorContentTable
 */

// Declare our namespace.
namespace Norcross\AuthorContentTable;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our plugin version.
define( __NAMESPACE__ . '\VERS', '0.0.1' );

// Plugin root file.
define( __NAMESPACE__ . '\FILE', __FILE__ );

// Define our file base.
define( __NAMESPACE__ . '\BASE', plugin_basename( __FILE__ ) );

// Plugin Folder URL.
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

// Set our assets path constants.
define( __NAMESPACE__ . '\ASSETS_URL', URL . 'assets' );
define( __NAMESPACE__ . '\ASSETS_PATH', __DIR__ . '/assets' );

// Set our includes path constants.
define( __NAMESPACE__ . '\INCLUDES_PATH', __DIR__ . '/includes' );

// Set the various prefixes for our actions and filters.
define( __NAMESPACE__ . '\HOOK_PREFIX', 'actb_manager_' );
define( __NAMESPACE__ . '\TRANSIENT_PREFIX', 'actb_tr_' );

// Set the menu ID, which becomes the admin page slug and screen ID.
define( __NAMESPACE__ . '\MENU_ID', 'author-content-table' );

// Now we handle all the actual file loading.
author_content_table_file_load();

/**
 * Actually load our files.
 *
 * @return void
 */
function author_content_table_file_load() {

    // And load our parts.
    require_once __DIR__ . '/includes/setup.php';
    require_once __DIR__ . '/includes/menu-items.php';
    require_once __DIR__ . '/includes/admin-bar.php';
    require_once __DIR__ . '/includes/queries.php';
    require_once __DIR__ . '/includes/list-table.php';
}
