<?php
/**
 * Handle the admin bar parts.
 *
 * @package AuthorContentTable
 */

// Declare our namespace.
namespace Norcross\AuthorContentTable\AdminBar;

// Set our aliases.
use Norcross\AuthorContentTable as Core;

/**
 * Start our engines.
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\load_table_admin_bar_css' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_table_admin_bar_css' );
add_action( 'admin_bar_menu', __NAMESPACE__ . '\add_author_table_admin_bar', 999 );

/**
 * Load some basic CSS for the admin bar.
 *
 * @return void
 */
function load_table_admin_bar_css() {

	// Don't try to load the CSS without an admin bar.
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	// Set my CSS up.
	$admin_bar_css  = '
		#wpadminbar #wp-admin-bar-ab-bar-author-content > .ab-item::before {
			content: "\f163";
			font-size: 16px;
			padding: 8px 0;
		}';

	// And add the CSS.
	wp_add_inline_style( 'admin-bar', $admin_bar_css );
}

/**
 * Add our link to the Author Content Table in the admin bar.
 *
 * @param  WP_Admin_Bar $wp_admin_bar  The global WP_Admin_Bar object.
 *
 * @return void.
 */
function add_author_table_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {

	// Confirm the user has this permission.
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	// Manually create the link.
	$set_admin_link = add_query_arg( [ 'page' => Core\MENU_ID ], admin_url( 'index.php' ) );

	// Add the top-level menu.
	$wp_admin_bar->add_node(
		[
			'id'       => 'ab-bar-author-content',
			'title'    => __( 'My Content', 'author-content-table' ),
			'href'     => esc_url( $set_admin_link ),
			'parent'   => '',
			'meta'     => [
				'title'    => __( 'Manage My Content', 'author-content-table' ),
			],
		]
	);
}
