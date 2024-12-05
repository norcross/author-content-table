<?php
/**
 * Our table setup for the handling all the content.
 *
 * @package AuthorContentTable
 */

// Set our aliases.
use Norcross\AuthorContentTable as Core;
use Norcross\AuthorContentTable\Queries as AuthorQueries;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create a new table class that will extend the WP_List_Table.
 */
class Author_Content_Table_List extends WP_List_Table {

	/**
	 * Author_Content_Table_List constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct( array(
			'singular' => __( 'Author Content', 'author-content-table' ),
			'plural'   => __( 'Author Content', 'author-content-table' ),
			'ajax'     => false,
			'screen'   => Core\MENU_ID,
		) );
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @return void
	 */
	public function prepare_items() {

		// We need a user ID.
		$define_user_id = get_current_user_id();

		// Roll out each part.
		$columns    = $this->get_columns();
		$sortable   = $this->get_sortable_columns();

		// Load up the pagination settings.
		$paginate   = $this->get_items_per_page( 'ac_table_per_page', 20 );
		$item_count = $this->table_count( $define_user_id );
		$current    = $this->get_pagenum();

		// Now grab the dataset.
		$dataset    = $this->table_data( $define_user_id, $paginate );

		// Set my pagination args.
		$this->set_pagination_args( [
			'total_items' => $item_count,
			'per_page'    => $paginate,
			'total_pages' => ceil( $item_count / $paginate ),
		] );

		// Do the column headers.
		$this->_column_headers = [ $columns, [], $sortable ];

		// And the result.
		$this->items = $dataset;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @return array
	 */
	public function get_columns() {

		// Build and return our array of column setups.
		$setup_data = [
			'blank'          => '',
			'title'          => __( 'Title', 'author-content-table' ),
			'categories'     => __( 'Categories', 'author-content-table' ),
			'post_tags'      => __( 'Post Tags', 'author-content-table' ),
			'post_type'      => __( 'Content Type', 'author-content-table' ),
			'date'           => __( 'Publish Date', 'author-content-table' ),
			'featured_image' => __( 'Featured Image', 'author-content-table' ),
		];

		// Return the array.
		return apply_filters( Core\HOOK_PREFIX . 'table_columns', $setup_data );
	}

	/**
	 * Define the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		// Build our array of sortable columns.
		$setup_data = [
			'title'     => [ 'post_title', false ],
			'post_type' => [ 'post_type', false ],
			'date'      => [ 'post_date', true ],
		];

		// Return the array.
		return apply_filters( Core\HOOK_PREFIX . 'sortable_columns', $setup_data );
	}

	/**
	 * Display all the things.
	 *
	 * @return void
	 */
	public function display() {

		// Wrap the basic div on it.
		echo '<div class="wrap ac-table-admin-wrap">';

			// Include our page display.
			$this->table_page_title_display();

			// Include our views row.
			$this->views();

			// Throw a wrap around the table.
			echo '<div class="ac-table-admin-section-wrap ac-table-admin-table-wrap">';

				// Wrap the display in a form.
				echo '<form action="" class="ac-admin-form" id="ac-admin-table-form" method="get">';

					// Include the search box.
					$this->search_box( __( 'Search Content', 'author-content-table' ), 'ac-table-search-input' );

					// And the parent display (which is most of it).
					parent::display();

				// Close up the form.
				echo '</form>';

			// And close the table div.
			echo '</div>';

		// And close the final div.
		echo '</div>';
	}

	/**
	 * Generates the table navigation above or below the table.
	 *
	 * @param  string $which  Which nav it is.
	 *
	 * @return HTML
	 */
	protected function display_tablenav( $which ) {

		// Open the table nav.
		echo '<div class="tablenav ' . esc_attr( $which ) . '">';

			// Include the blank div where the bulk action dropdown would be.
			echo '<div class="alignleft actions bulkactions"></div>';

			// Now show the other parts.
			$this->extra_tablenav( $which );
			$this->pagination( $which );

			// Clear everything out.
			echo '<br class="clear" />';

		// Close it up.
		echo '</div>';
	}

	/**
	 * Set up our custom table classes.
	 *
	 * @return array
	 */
	protected function get_table_classes() {
		return [ 'widefat', 'fixed', 'striped', 'table-view-list', 'posts', 'author-content-table' ];
	}

	/**
	 * Handle our admin page title setup.
	 *
	 * @return HTML
	 */
	public function table_page_title_display() {

		// Get the current user.
		$get_current_user   = wp_get_current_user();

		// Check to see if there is a search value provided.
		$set_search_value   = $this->get_search_value();

		// Wrap a div on it.
		echo '<div class="ac-table-admin-section-wrap ac-table-admin-title-wrap">';

			// Handle the title.
			echo '<h1 class="wp-heading-inline ac-table-admin-title">' . esc_html( get_admin_page_title() ) . '</h1>';

			// Output the search subtitle.
			if ( ! empty( $set_search_value ) ) {
				echo sprintf( '<span class="subtitle">' . esc_html__( 'Search results for: %s', 'author-content-table' ) . '</span>', '<strong>' . esc_html( $set_search_value ) . '</strong>' );
			}

			// Cut off the header.
			echo '<hr class="wp-header-end">';

		// Close the div.
		echo '</div>';
	}

	/**
	 * Handle displaying the unordered list of views.
	 *
	 * @return HTML
	 */
	public function views() {

		// Get the current user.
		$get_current_user   = wp_get_current_user();

		// Get our views to display.
		$get_status_views   = $this->get_filter_views( $get_current_user->ID );

		// Bail without any views to render.
		if ( empty( $get_status_views ) ) {
			return;
		}

		// Include the screen reader items.
		$this->screen->render_screen_reader_content( 'heading_views' );

		// Wrap it in an unordered list.
		echo '<ul class="subsubsub ac-table-subsubsub">' . "\n";

		// Loop the views we creatred and output them.
		foreach ( $get_status_views as $type => $view ) {
			$get_status_views[ $type ] = "\t" . '<li class="' . esc_attr( $type ) . '">' . wp_kses_post( $view );
		}

		// Blow out and implode my list.
		echo implode( ' |</li>' . "\n", $get_status_views ) . '</li>' . "\n"; // phpcs:ignore -- this is already escaped above.

		// And our string about who we are.
		echo '<li class="ac-table-admin-title-inline-author">' . wp_kses_post( sprintf( __( 'Currently viewing content for: %s (%s)', 'author-content-table' ), '<strong>' . $get_current_user->display_name . '</strong>', $get_current_user->user_email ) ) . '</li>';

		// Close the list.
		echo '</ul>';
	}

	/**
	 * Build out our custom search box to make sure it goes to the right place.
	 *
	 * @param  string $text      The 'submit' button label.
	 * @param  string $input_id  ID attribute value for the search input field.
	 *
	 * @return HTML
	 */
	public function search_box( $text, $input_id ) {

		// Check to see if there is a search value provided.
		$set_search_var = $this->get_search_value();

		// Do the check for order and orderby.
		$check_order    = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS );
		$check_orderby  = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_SPECIAL_CHARS );

		// Pulled these from the parent function.
		if ( ! empty( $check_order ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $check_order ) . '" />';
		}
		if ( ! empty( $check_orderby ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $check_orderby ) . '" />';
		}

		// Open the search box.
		echo '<p class="search-box">';

			// Set our label for screen readers.
			echo '<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . ':</label>';

			// Also include the page so the URL gets built correctly.
			echo '<input type="hidden" name="page" value="' . esc_attr( Core\MENU_ID ) . '">';

			// Now render the actual search field.
			echo '<input type="search" id="' . esc_attr( $input_id ) . '" class="ac-table-input-element" name="search-value" value="' . esc_attr( $set_search_var ) . '" />';

			// And do the submit button.
			echo '<button type="submit" name="filter-search" id="ac-table-search-submit" class="button" value="yes">' . esc_html( $text ) . '</button>';

		// And close the box.
		echo '</p>';
	}

	/**
	 *
	 * Get the data for outputing the views list of links.
	 *
	 * @return array
	 */
	protected function get_filter_views( $user_id = 0 ) {

		// Get all our stat numbers.
		$get_stats_nums = AuthorQueries\query_status_row_counts( get_current_user_id() );

		// Set our base link.
		$set_base_link  = add_query_arg( [ 'page' => Core\MENU_ID ], admin_url( 'index.php' ) );

		// Set the basic array of views.
		$set_all_views  = [
			'publish' => __( 'Published', 'author-content-table' ),
			'draft'   => __( 'Draft', 'author-content-table' ),
			'future'  => __( 'Scheduled', 'author-content-table' ),
			'pending' => __( 'Pending Review', 'author-content-table' ),
			'private' => __( 'Private', 'author-content-table' ),
		];

		// Check and see if there is a filtered arg already passed.
		$check_filtered = filter_input( INPUT_GET, 'filter-status', FILTER_SANITIZE_SPECIAL_CHARS );
		$filtered_value = filter_input( INPUT_GET, 'post-status', FILTER_SANITIZE_SPECIAL_CHARS );

		// Build the HTML for the "all" link.
		$all_inner_html = sprintf(
			/* translators: %s: Number of posts. */
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				array_sum( $get_stats_nums ),
				'posts',
				'author-content-table'
			),
			number_format_i18n( array_sum( $get_stats_nums ) )
		);

		// Set our empty.
		$set_views_args = [];

		// Manually build the "all" tab.
		$set_views_args['all'] = [
			'url'     => $set_base_link,
			'label'   => $all_inner_html,
			'current' => empty( $check_filtered ) || 'yes' !== $check_filtered ? true : false,
		];

		// Now loop and set the links.
		foreach ( $set_all_views as $key => $label ) {

			// Don't show any that don't have any.
			if ( ! array_key_exists( $key, $get_stats_nums ) ) {
				continue;
			}

			// Set our custom filter link args.
			$set_link_args  = [
				'filter-status' => 'yes',
				'post-status'   => $key,
			];

			// Define the status count.
			$status_count   = absint( $get_stats_nums[ $key ] );

			// Build the HTML for the individual status label.
			$status_label   = sprintf(
				/* translators: %s: Number of posts. */
				_nx(
					'%s <span class="count">(%s)</span>',
					'%s <span class="count">(%s)</span>',
					$status_count,
					'posts',
					'author-content-table'
				),
				esc_html( $label ), number_format_i18n( $status_count )
			);

			// And add this to the array.
			$set_views_args[ $key ] = [
				'url'     => add_query_arg( $set_link_args, $set_base_link ),
				'label'   => $status_label,
				'current' => ! empty( $check_filtered ) && $key === $filtered_value ? true : false,
			];
		}

		// Return our array linked up.
		return parent::get_views_links( $set_views_args );
	}

	/**
	 * Add extra markup in the toolbars before or after the list.
	 *
	 * @param  string $which  Which markup area after (bottom) or before (top) the list.
	 *
	 * @return HTML
	 */
	protected function extra_tablenav( $which ) {

		// We only want this on the top.
		if ( empty( $which ) || 'top' !== $which ) {
			return;
		}

		// Wrap the whole thing in a div.
		echo '<div class="alignleft actions">';

			// Do the month dropdown.
			$this->get_months_dropdown( get_current_user_id() );

			// Do the various term dropdown.
			$this->get_taxonomy_dropdown( 'category' );
			$this->get_taxonomy_dropdown( 'post_tags' );

			// Allow extra items to be added.
			do_action( Core\HOOK_PREFIX . 'extra_tablenav', $which );

			// And a filter button.
			echo '<button type="submit" name="filter-drops" id="ac-table-drop-submit" class="button" value="yes">' . esc_html__( 'Filter', 'author-content-table' ) . '</button>';

		// Close the div.
		echo '</div>';
	}

	/**
	 * The blank column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_blank( $item ) {
	}

	/**
	 * The title column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_title( $item ) {

		// Confirm the user can edit this.
		$confirm_edit   = current_user_can( 'edit_post', $item['id'] );

		// And include the row actions.
		$row_actions    = $this->get_row_actions( $item, $confirm_edit );

		// Show the possible locked info.
		$this->show_locked_state( $item['id'] );

		// The whole thing gets wrapped in a strong tag.
		echo '<strong>';

		// Show the edit link if they can edit.
		if ( $confirm_edit ) {

			// Get my edit link.
			$get_edit_link  = get_edit_post_link( $item['id'] );

			// And echo it out.
			echo sprintf(
				'<a class="row-title" href="%s" aria-label="%s">%s</a>',
				esc_url( $get_edit_link ),
				esc_attr( sprintf( __( '&#8220;%s&#8221; (Edit)', 'author-content-table' ), $item['title'] ) ),
				esc_html( $item['title'] )
			);

		// Otherwise just show the title.
		} else {
			echo sprintf(
				'<span>%s</span>',
				esc_html( $item['title'] )
			);
		}

		// Pulled this directly from core.
		_post_states( $item['post_object'] );

		// And my closing tag.
		echo '</strong>' . "\n";

		// If we have row actions, add them here.
		if ( ! empty( $row_actions ) ) {
			echo '<div class="row-actions">';
				echo implode( ' | ', $row_actions ); // phpcs:ignore -- the array of row actions are escaped.
			echo '</div>';
		}

		// And be done.
		return;
	}

	/**
	 * The categories column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_categories( $item ) {

		// Show our empty line if none exist.
		if ( empty( $item['categories'] ) ) {

			// Show the dash and screen reader text.
			echo '<span aria-hidden="true">&#8212;</span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'No categories', 'author-content-table' ) . '</span>';

			// And bail.
			return;
		}

		// Just display them in text for now.
		return implode( wp_get_list_item_separator(), $item['categories'] );
	}

	/**
	 * The Post Tags column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_post_tags( $item ) {

		// Show our empty line if none exist.
		if ( empty( $item['post_tags'] ) ) {

			// Show the dash and screen reader text.
			echo '<span aria-hidden="true">&#8212;</span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'No post tags applied', 'author-content-table' ) . '</span>';

			// And bail.
			return;
		}

		// Just display them in text for now.
		return implode( wp_get_list_item_separator(), $item['post_tags'] );
	}

	/**
	 * The post type column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_post_type( $item ) {

		// Get all the args for my post type.
		$post_type_args = get_post_type_object( $item['post_type'] );

		// Return my escaped value.
		return $post_type_args->labels->singular_name;
	}

	/**
	 * The date column. We took this from the core Posts table.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_date( $item ) {

		// If it has no date, it isn't published.
		if ( '0000-00-00 00:00:00' === $item['date'] ) {
			$t_time    = __( 'Unpublished' );
			$time_diff = 0;
		} else {
			$t_time = sprintf(
				/* translators: 1: Post date, 2: Post time. */
				__( '%1$s at %2$s' ),
				/* translators: Post date format. See https://www.php.net/manual/datetime.format.php */
				gmdate( __( 'Y/m/d' ), $item['stamp'] ),
				/* translators: Post time format. See https://www.php.net/manual/datetime.format.php */
				gmdate( __( 'g:i a' ), $item['stamp'] )
			);

			$time      = get_post_timestamp( $item['id'] );
			$time_diff = time() - $time;
		}

		if ( 'publish' === $item['status'] ) {
			$status = __( 'Published' );
		} elseif ( 'future' === $item['status'] ) {
			if ( $time_diff > 0 ) {
				$status = '<strong class="error-message">' . __( 'Missed schedule', 'author-content-table' ) . '</strong>';
			} else {
				$status = __( 'Scheduled', 'author-content-table' );
			}
		} else {
			$status = __( 'Last Modified', 'author-content-table' );
		}

		// And return the status and time.
		return $status . '<br />' . $t_time;
	}

	/**
	 * The featured image column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_featured_image( $item ) {

		// Attempt to get the featured image.
		$get_featured_thumb_url = get_the_post_thumbnail_url( $item['id'], 'thumbnail' );

		// Bail without an image.
		if ( empty( $get_featured_thumb_url ) ) {
			return;
		}

		// If we have one, show it.
		return '<img class="admin-column-thumb" src="' . esc_url( $get_featured_thumb_url ) . '">';
	}

	/**
	 * Get the table data.
	 *
	 * @param  integer $user_id   Which user ID we're pulling for.
	 * @param  integer $per_page  How may we want per-page.
	 *
	 * @return Array
	 */
	private function table_data( $user_id = 0, $per_page = 20 ) {

		// Set the possible args for the query.
		$setup_args = [
			'paged'    => filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ),
			'order'    => filter_input( INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS ),
			'orderby'  => filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_SPECIAL_CHARS ),
			'per_page' => $per_page,
		];

		// First check for a post status filter.
		$filter_status  = filter_input( INPUT_GET, 'filter-status', FILTER_SANITIZE_SPECIAL_CHARS );
		$status_request = filter_input( INPUT_GET, 'post-status', FILTER_SANITIZE_SPECIAL_CHARS );

		// If we have one, handle it here.
		if ( ! empty( $status_request ) && ! empty( $filter_status ) && 'yes' === sanitize_text_field( $filter_status ) ) {
			return $this->setup_filtered_data_by_status( $user_id, $setup_args, $status_request );
		}

		// Now check for a search request.
		$filter_search  = filter_input( INPUT_GET, 'filter-search', FILTER_SANITIZE_SPECIAL_CHARS );
		$search_request = filter_input( INPUT_GET, 'search-value', FILTER_SANITIZE_SPECIAL_CHARS );

		// If we have one, handle it here.
		if ( ! empty( $search_request ) && ! empty( $filter_search ) && 'yes' === sanitize_text_field( $filter_search ) ) {
			return $this->setup_filtered_data_by_search( $user_id, $setup_args, $search_request );
		}

		// Check for one of the dropdowns.
		$filtered_drop  = filter_input( INPUT_GET, 'filter-drops', FILTER_SANITIZE_SPECIAL_CHARS );

		// If we did a dropdown, figure out which one.
		if ( ! empty( $filtered_drop ) && 'yes' === sanitize_text_field( $filtered_drop ) ) {

			// Now check for any of the possible dropdown values.
			$dropdown_check = [
				'yearmonth' => filter_input( INPUT_GET, 'filter-months', FILTER_SANITIZE_NUMBER_INT ),
				'category'  => filter_input( INPUT_GET, 'filter-category', FILTER_SANITIZE_NUMBER_INT ),
				'post_tag'  => filter_input( INPUT_GET, 'filter-post-tags', FILTER_SANITIZE_NUMBER_INT ),
			];

			// Filter for any empties.
			$dropdown_args  = array_filter( $dropdown_check );

			// And try to build a query with it.
			return $this->setup_filtered_data_by_dropdowns( $user_id, $setup_args, $dropdown_args );
		}

		// Just handle the default return.
		return $this->setup_default_data( $user_id, $setup_args );
	}

	/**
	 * Handle passing the args to do a post status filtered look.
	 *
	 * @param  integer $user_id      Which user ID we're pulling for.
	 * @param  array   $setup_args   The standard args for the table.
	 * @param  string  $post_status  The status we wanna filter by.
	 *
	 * @return array
	 */
	private function setup_filtered_data_by_status( $user_id = 0, $setup_args = [], $post_status = '' ) {

		// Without a status, return the full thing.
		if ( empty( $post_status ) || 'all' === $post_status ) {
			return $this->setup_default_data( $user_id, $setup_args );
		}

		// Get the filtered data data.
		$filtered_table = AuthorQueries\query_filtered_status_items( $user_id, $setup_args, $post_status );

		// Return our data.
		return ! empty( $filtered_table ) ? $this->format_table_data( $filtered_table ) : [];
	}

	/**
	 * Handle passing the args to do a search filtered look.
	 *
	 * @param  integer $user_id      Which user ID we're pulling for.
	 * @param  array   $setup_args   The standard args for the table.
	 * @param  string  $search_term  What the search term was.
	 *
	 * @return array
	 */
	private function setup_filtered_data_by_search( $user_id = 0, $setup_args = [], $search_term = '' ) {

		// Without a term, return the full thing.
		if ( empty( $search_term ) ) {
			return $this->setup_default_data( $user_id, $setup_args );
		}

		// Get the filtered data data.
		$filtered_table = AuthorQueries\query_filtered_search_items( $user_id, $setup_args, $search_term );

		// Return our data.
		return ! empty( $filtered_table ) ? $this->format_table_data( $filtered_table ) : [];
	}

	/**
	 * Handle passing the args to do a one or multi dropdown filtered look.
	 *
	 * @param  integer $user_id     Which user ID we're pulling for.
	 * @param  array   $setup_args  The standard args for the table.
	 * @param  string  $drop_args   The possible dropdown args.
	 *
	 * @return array
	 */
	private function setup_filtered_data_by_dropdowns( $user_id = 0, $setup_args = [], $drop_args = [] ) {

		// Without anything, return the full thing.
		if ( empty( $drop_args ) ) {
			return $this->setup_default_data( $user_id, $setup_args );
		}

		// Get the filtered data data.
		$filtered_table = AuthorQueries\query_filtered_dropdown_items( $user_id, $setup_args, $drop_args );

		// Return our data.
		return ! empty( $filtered_table ) ? $this->format_table_data( $filtered_table ) : [];
	}

	/**
	 * Handle passing the args to do a post status filtered look.
	 *
	 * @param  integer $user_id     Which user ID we're pulling for.
	 * @param  array   $setup_args  The standard args for the table.
	 *
	 * @return array
	 */
	private function setup_default_data( $user_id = 0, $setup_args = [] ) {

		// Get the default data.
		$default_table  = AuthorQueries\query_default_items( $user_id, $setup_args );

		// Return our data.
		return ! empty( $default_table ) ? $this->format_table_data( $default_table ) : [];
	}

	/**
	 * Get our table data in a format we can use.
	 *
	 * @param  array  $table_data  The data we got from our query.
	 *
	 * @return array
	 */
	private function format_table_data( $table_data = [] ) {

		// Bail without data to look at.
		if ( empty( $table_data ) ) {
			return false;
		}

		// Set my empty.
		$list_data  = [];

		// Now loop each customer info.
		foreach ( $table_data as $index => $item ) {

			// Make sure this is an array.
			$set_object = is_array( $item ) ? get_post( $item['ID'] ) : $item;
			$set_data   = is_array( $item ) ? $item : (array) $item;

			// Confirm a title.
			$set_title  = ! empty( $set_data['post_title'] ) ? $set_data['post_title'] : '(' . __( 'no title', 'author-content-table' ) . ')';

			// Set up the basic return array.
			$list_data[] = [
				'id'             => $set_data['ID'],
				'title'          => $set_title,
				'categories'     => AuthorQueries\query_single_item_term_data( $set_data['ID'], 'category' ),
				'post_tags'      => AuthorQueries\query_single_item_term_data( $set_data['ID'], 'post_tags' ),
				'post_type'      => $set_data['post_type'],
				'date'           => $set_data['post_date'],
				'stamp'          => strtotime( $set_data['post_date'] ),
				'status'         => $set_data['post_status'],
				'post_object'    => $set_object,
			];
		}

		// Return our data.
		return apply_filters( Core\HOOK_PREFIX . 'table_data', $list_data, $table_data );
	}

	/**
	 * Get the table count.
	 *
	 * @param  integer $user_id  Which user ID we're pulling for.
	 *
	 * @return integer
	 */
	private function table_count( $user_id = 0 ) {

		// Bail without a user ID.
		if ( empty( $user_id ) ) {
			return 0;
		}

		// First check for a post status filter.
		$filter_status  = filter_input( INPUT_GET, 'filter-status', FILTER_SANITIZE_SPECIAL_CHARS );
		$status_request = filter_input( INPUT_GET, 'post-status', FILTER_SANITIZE_SPECIAL_CHARS );

		// If we have one, handle it here.
		if ( ! empty( $status_request ) && ! empty( $filter_status ) && 'yes' === sanitize_text_field( $filter_status ) ) {
			return AuthorQueries\query_status_row_count( $user_id, $status_request );
		}

		// Now check for a search request.
		$filter_search  = filter_input( INPUT_GET, 'filter-search', FILTER_SANITIZE_SPECIAL_CHARS );
		$search_request = filter_input( INPUT_GET, 'search-value', FILTER_SANITIZE_SPECIAL_CHARS );

		// If we have one, handle it here.
		if ( ! empty( $search_request ) && ! empty( $filter_search ) && 'yes' === sanitize_text_field( $filter_search ) ) {
			return AuthorQueries\query_search_row_count( $user_id, $search_request );
		}

		// Check for one of the dropdowns.
		$filtered_drop  = filter_input( INPUT_GET, 'filter-drops', FILTER_SANITIZE_SPECIAL_CHARS );

		// If we did a dropdown, figure out which one.
		if ( ! empty( $filtered_drop ) && 'yes' === sanitize_text_field( $filtered_drop ) ) {

			// Now check for any of the possible dropdown values.
			$dropdown_check = [
				'yearmonth' => filter_input( INPUT_GET, 'filter-months', FILTER_SANITIZE_NUMBER_INT ),
				'category'  => filter_input( INPUT_GET, 'filter-category', FILTER_SANITIZE_NUMBER_INT ),
				'post_tag'  => filter_input( INPUT_GET, 'filter-post-tags', FILTER_SANITIZE_NUMBER_INT ),
			];

			// Filter for any empties.
			$dropdown_args  = array_filter( $dropdown_check );

			// And try to build a query with it.
			return AuthorQueries\query_dropdown_row_count( $user_id, $dropdown_args );
		}

		// Return the defaul count.
		return AuthorQueries\query_total_row_count( $user_id );
	}

	/**
	 * Get the search value if we have it.
	 *
	 * @return string
	 */
	private function get_search_value() {

		// Figure out what type of filter and the value.
		$filter_search  = filter_input( INPUT_GET, 'filter-search', FILTER_SANITIZE_SPECIAL_CHARS );

		// If this isn't a search, return nothing.
		if ( empty( $filter_search ) || 'yes' !== sanitize_text_field( $filter_search ) ) {
			return '';
		}

		// See if we have a search request.
		$search_request = filter_input( INPUT_GET, 'search-value', FILTER_SANITIZE_SPECIAL_CHARS );

		// Now return the value if it was there, or an empty string.
		return ! empty( $search_request ) ? wp_unslash( $search_request ) : '';
	}

	/**
	 * Get the possible dropdown of months.
	 *
	 * @param  integer $user_id  Which user ID we're pulling for.
	 *
	 * @return HTML
	 */
	private function get_months_dropdown( $user_id = 0 ) {

		// Include the global locale.
		global $wp_locale;

		// Get all my author months.
		$get_author_months  = AuthorQueries\query_available_months( $user_id );

		// Bail without months.
		if ( empty( $get_author_months ) || empty( $get_author_months[1] ) ) {
			return;
		}

		// Include a default selected value.
		$selected_month = 0;

		// See if a monthyear string was passed.
		$check_filtered = filter_input( INPUT_GET, 'filter-months', FILTER_SANITIZE_SPECIAL_CHARS );

		// If we passed a "months" filter, do that.
		if ( ! empty( $check_filtered ) ) {
			$selected_month = absint( $check_filtered );
		}

		// Put a label on it first.
		echo '<label for="ac-table-date-filter" class="screen-reader-text">' . esc_html__( 'Filter by date', 'author-content-table' ) . '</label>';

		// Begin wrapping the select.
		echo '<select name="filter-months" id="ac-table-date-filter" class="ac-table-input-element">';

			// Include the "all".
			echo '<option value="0">' . esc_html__( 'All Dates', 'author-content-table' ) . '</option>';

			// Now loop each month and make it readable.
			foreach ( $get_author_months as $arc_row ) {

				// Skip any weird blank years.
				if ( 0 === $arc_row['year'] ) {
					continue;
				}

				// Pull out my month and year.
				$month = zeroise( $arc_row['month'], 2 );
				$year  = $arc_row['year'];

				// Show the individual option.
				echo sprintf(
					"<option %s value='%s'>%s</option>\n",
					selected( $selected_month, $year . $month, false ),
					esc_attr( $year . $month ),
					/* translators: 1: Month name, 2: 4-digit year. */
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year ) // phpcs:ignore -- this is core WP.
				);
			}

		// Close the select.
		echo '</select>';
	}

	/**
	 * Display a dropdown for one of our taxonomies.
	 *
	 * @param  string $taxonomy  What taxonomy we want.
	 *
	 * @return HTML
	 */
	private function get_taxonomy_dropdown( $taxonomy = '' ) {

		// Set my field ID and name.
		$field_id   = 'ac-table-' . $taxonomy . '-filter';
		$field_name = 'filter-' . $taxonomy;

		// Set an empty for the selected.
		$selected_term  = 0;

		// See if a dropdown string was passed.
		$check_filtered = filter_input( INPUT_GET, 'filter-drops', FILTER_SANITIZE_SPECIAL_CHARS );

		// If we passed a "months" filter, do that.
		if ( ! empty( $check_filtered ) ) {

			// Get the term ID we passed.
			$term_query_id = filter_input( INPUT_GET, $field_name, FILTER_SANITIZE_NUMBER_INT );

			// Set the term ID.
			$selected_term = absint( $term_query_id );
		}

		// Make sure we have a valid taxonomy.
		$tax_object = get_taxonomy( $taxonomy );

		// Bail if that isn't there.
		if ( empty( $tax_object ) ) {
			return;
		}

		// Get my labels.
		$tax_labels = $tax_object->labels;

		// Set the dropdown args.
		$setup_args = [
			'taxonomy'        => $taxonomy,
			'show_option_all' => $tax_labels->all_items,
			'hide_empty'      => 0,
			'hierarchical'    => 1,
			'show_count'      => 0,
			'echo'            => 0,
			'orderby'         => 'name',
			'name'            => esc_attr( $field_name ),
			'id'              => esc_attr( $field_id ),
			'selected'        => $selected_term,
		];

		// Get the screen reader label.
		$screen_txt = $tax_object->labels->filter_by_item;

		// Echo the screen reader part.
		echo '<label class="screen-reader-text" for="' . esc_attr( $field_id ) . '">' . esc_html( $tax_labels->filter_by_item ) . '</label>';

		// And actually echo the dropdown.
		echo wp_dropdown_categories( $setup_args );
	}

	/**
	 * Check if the content is locked and if so, render that part.
	 *
	 * @param  integer $item_id  The item ID in question.
	 * @param  boolean $echo     Whether to echo or just return it.
	 *
	 * @return HTML
	 */
	private function show_locked_state( $item_id = 0, $echo = true ) {

		// These are empty.
		$editing_avatar = '';
		$editing_text   = '';

		// Bail without an item ID.
		if ( empty( $item_id ) ) {
			return '';
		}

		// Now check if someone is editing it.
		$user_edit_id   = wp_check_post_lock( $item_id );

		// No ID? Then we finish up.
		if ( empty( $user_edit_id ) ) {
			return '';
		}

		// Get the userdata.
		$user_editing   = get_userdata( $user_edit_id );

		// Pull the avatar in.
		$editing_avatar = get_avatar( $user_editing->ID, 18 );

		// And show their name.
		$editing_text   = esc_html( sprintf( __( '%s is currently editing', 'author-content-table' ), $user_editing->display_name ) );

		// Echo it out if requested.
		if ( false !== $echo ) {
			echo '<div class="locked-info"><span class="locked-avatar">' . $editing_avatar . '</span> <span class="locked-text">' . $editing_text . "</span></div>\n"; // phpcs:ignore -- this is native core WP
		}

		// Return the markup.
		return '<div class="locked-info"><span class="locked-avatar">' . $editing_avatar . '</span> <span class="locked-text">' . $editing_text . "</span></div>\n"; // phpcs:ignore -- this is native core WP
	}

	/**
	 * Define what data to show on each column of the table.
	 *
	 * @param  array  $dataset      Our entire dataset.
	 * @param  string $column_name  Current column name.
	 *
	 * @return mixed
	 */
	public function column_default( $dataset, $column_name ) {

		// Run our column switch.
		switch ( $column_name ) {

			case 'blank' :
			case 'title' :
			case 'categories' :
			case 'post_tags' :
			case 'post_type' :
			case 'date' :
			case 'featured_image' :
				return ! empty( $dataset[ $column_name ] ) ? $dataset[ $column_name ] : '';

			default :
				return apply_filters( Core\HOOK_PREFIX . 'list_table_column_default', '', $dataset, $column_name );
		}
	}

	/**
	 * Handle our row actions that display under the title.
	 *
	 * @param  array   $item      The item we had in the previous function.
	 * @param  boolean $can_edit  Whether this user can edit the content.
	 *
	 * @return array
	 */
	protected function get_row_actions( $item = [], $can_edit = true ) {

		// Bail without ID to use.
		if ( empty( $item ) || empty( $item['id'] ) ) {
			return [];
		}

		// Set an empty.
		$actions    = [];

		// Add the edit link if the user is allowed.
		if ( false !== $can_edit ) {
			$actions['edit'] = sprintf(
				'<a target="_blank" href="%s" aria-label="%s">%s</a>',
				get_edit_post_link( $item['id'] ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'author-content-table' ), $item['title'] ) ),
				__( 'Edit' )
			);
		}

		// If this isn't a viewable post type, we are done.
		if ( ! is_post_type_viewable( $item['post_type'] ) ) {
			return $actions;
		}

		// Set either the view or preview link based on status.
		if ( false !== $can_edit && in_array( $item['status'], ['pending', 'draft', 'future'], true ) ) {

			// Grab the preview link.
			$preview_link    = get_preview_post_link( $item['id'] );

			// And set up the view.
			$actions['view'] = sprintf(
				'<a target="_blank" href="%s" rel="bookmark" aria-label="%s">%s</a>',
				esc_url( $preview_link ),
				// translators: %s: Post title.
				esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'author-content-table' ), $item['title'] ) ),
				__( 'Preview' )
			);
		} else {
			$actions['view'] = sprintf(
				'<a target="_blank" href="%s" rel="bookmark" aria-label="%s">%s</a>',
				get_permalink( $item['id'] ),
				// translators: %s: Post title.
				esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'author-content-table' ), $item['title'] ) ),
				__( 'View' )
			);
		}

		// And be finished.
		return $actions;
	}

	/**
	 * This is a legacy piece from the WP_List_Table that only renders a hidden button.
	 *
	 * @param  object|array $item         The item being acted upon.
	 * @param  string       $column_name  Current column name.
	 * @param  string       $primary      Primary column name.
	 *
	 * @return string                     An empty string.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return '';
	}

	/**
	 * Handle the display text for when no items exist.
	 *
	 * @return string
	 */
	public function no_items() {
		esc_html_e( 'No content avaliable.', 'author-content-table' );
	}

	// Add any additional functions here.
}
