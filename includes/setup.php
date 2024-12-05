<?php
/**
 * Handle the admin setup parts.
 *
 * @package AuthorContentTable
 */

// Declare our namespace.
namespace Norcross\AuthorContentTable\Setup;

// Set our aliases.
use Norcross\AuthorContentTable as Core;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_table_admin_assets' );
add_filter( 'list_table_primary_column', __NAMESPACE__ . '\set_primary_table_column', 10, 2 );
add_filter( 'set-screen-option', __NAMESPACE__ . '\set_user_table_count', 20, 3 );

/**
 * Load any admin CSS or JS as needed.
 *
 * @return void
 */
function load_table_admin_assets( $hook ) {

	// Bail if this isn't our custom page.
	if ( empty( $hook ) || 'dashboard_page_author-content-table' !== $hook ) {
		return;
	}

	// Set my handle.
	$handle = 'author-content-table';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $handle : $handle . '.min';

	// Set a version for whether or not we're debugging.
	$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : Core\VERS;

	// Load our primary CSS file.
	wp_enqueue_style( $handle, Core\ASSETS_URL . '/css/' . $file . '.css', false, $vers, 'all' );
}

/**
 * Set the title field as our primary column.
 *
 * @param  string $default  Column name default for the specific list table, e.g. 'name'.
 * @param  string $context  Screen ID for specific list table, e.g. 'plugins'.
 *
 * @return string
 */
function set_primary_table_column( $default, $context ) {
	return ! empty( $context ) && Core\MENU_ID === $context ? 'title' : $default;
}

/**
 * Set and define our value.
 *
 * @param  mixed  $screen_option  The value to save instead of the option value. Default false (to skip saving the current option).
 * @param  string $option         The option name.
 * @param  value  $value          The option value.
 *
 * @return integer
 */
function set_user_table_count( $screen_option, $option, $value ) {

	// Return whatever the value is for other options.
	if ( empty( $option ) || $option !== 'ac_table_per_page' ) {
		return $value;
	}

	// If it's less than 1, return 1.
	if ( 1 > intval( $value ) ) {
		return 1;
	}

	// If it's more than 100, return 100.
	if ( 100 < intval( $value ) ) {
		return 100;
	}

	// This one is OK.
	return intval( $value );
}
