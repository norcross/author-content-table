<?php
/**
 * Load our menu items.
 *
 * @package AuthorContentTable
 */

// Declare our namespace.
namespace Norcross\AuthorContentTable\MenuItems;

// Set our aliases.
use Norcross\AuthorContentTable as Core;

/**
 * Start our engines.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\load_author_table_menu', 10 );

/**
 * Add a top-level item for getting to the user table.
 *
 * @return void
 */
function load_author_table_menu() {

	// Handle loading the initial menu.
	$setup_page = add_dashboard_page(
		__( 'Single Author Content', 'author-content-table' ),
		__( 'My Content', 'author-content-table' ),
		'edit_posts',
		Core\MENU_ID,
		__NAMESPACE__ . '\render_author_content_page',
		2
	);

	// Now handle some screen options.
	add_action( "load-$setup_page", __NAMESPACE__ . '\add_screen_options' );

	// Nothing left inside this.
}

/**
 * Add our per_page option for the table.
 *
 * @return void
 */
function add_screen_options() {

	// Define the args we want.
	$setup_args = [
		'label'   => __( 'Per Page', 'author-content-table' ),
		'default' => 20,
		'option'  => 'ac_table_per_page'
	];

	// And add it to the setup.
	add_screen_option( 'per_page', $setup_args );
}

/**
 * Handle loading our custom table for the author content.
 *
 * @return HTML
 */
function render_author_content_page() {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You are not permitted to view this page.', 'author-content-table' ) );
	}

	// Call our table class.
	$table  = new \Author_Content_Table_List();

	// And output the table.
	$table->prepare_items();

	// The actual table itself.
	$table->display();
}
