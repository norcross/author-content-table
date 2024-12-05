<?php
/**
 * Handle the individual queries we need to do.
 *
 * @package AuthorContentTable
 */

// Declare our namespace.
namespace Norcross\AuthorContentTable\Queries;

// Set our aliases.
use Norcross\AuthorContentTable as Core;

/**
 * Get the count of all the items for a user.
 *
 * @param  integer $user_id  The user ID we are checking against.
 *
 * @return array
 */
function query_status_row_counts( $user_id = 0 ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// Get the allowed post statuses and types.
	$get_post_stats = get_post_status_array();
	$get_post_types = get_post_type_array();

	// Call the global DB.
	global $wpdb;

	// Flush out the cache first.
	$wpdb->flush();

	// Set up our query.
	// phpcs:ignore -- this is set up exactly like core does it. ugly, but works.
	// phpcs:disable
	$query_args = $wpdb->prepare("
		SELECT   post_status, COUNT(*) as count
		FROM     $wpdb->posts
		WHERE    post_author = '%d'
		AND      post_status IN ( '" . implode( "','", $get_post_stats ) . "' )
		AND      post_type IN ( '" . implode( "','", $get_post_types ) . "' )
		GROUP BY post_status
	", absint( $user_id ) );
	// phpcs:enable

	// Process the query.
	// phpcs:ignore -- the following is a false positive; this SQL is safe, everything is escaped above.
	$query_run  = $wpdb->get_results( $query_args, ARRAY_A );

	// Return the result, or an empty array.
	return empty( $query_run ) || is_wp_error( $query_run ) ? [] : wp_list_pluck( $query_run, 'count', 'post_status' );
}

/**
 * Get the count of all the items for a user.
 *
 * @param  integer $user_id  The user ID we are checking against.
 * @param  boolean $purge    Whether to purge the cache first.
 *
 * @return array
 */
function query_total_row_count( $user_id = 0, $purge = false ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// Set the key to use in our transient.
	$ky = Core\TRANSIENT_PREFIX . 'total_rows_' . absint( $user_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the data from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Get the allowed post statuses and types.
		$get_post_stats = get_post_status_array();
		$get_post_types = get_post_type_array();

		// Call the global DB.
		global $wpdb;

		// Flush out the cache first.
		$wpdb->flush();

		// Set up our query.
		// phpcs:ignore -- this is set up exactly like core does it. ugly, but works.
		// phpcs:disable
		$query_args = $wpdb->prepare("
			SELECT   COUNT(*)
			FROM     $wpdb->posts
			WHERE    post_author = '%d'
			AND      post_status IN ( '" . implode( "','", $get_post_stats ) . "' )
			AND      post_type IN ( '" . implode( "','", $get_post_types ) . "' )
		", absint( $user_id ) );
		// phpcs:enable

		// Process the query.
		// phpcs:ignore -- the following is a false positive; this SQL is safe, everything is escaped above.
		$query_run  = $wpdb->get_var( $query_args );

		// Bail without any items.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data for a minute.
		set_transient( $ky, $query_run, MINUTE_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// And return the resulting.
	return $cached_dataset;
}

/**
 * Get the count of all the items for a user with a specific post status.
 *
 * @param  integer $user_id      The user ID we are checking against.
 * @param  string  $post_status  What post status to look for.
 * @param  boolean $purge        Whether to purge the cache first.
 *
 * @return array
 */
function query_status_row_count( $user_id = 0, $post_status = '', $purge = false ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// Return the whole count without a status to check.
	if ( empty( $post_status ) ) {
		return query_total_row_count( $user_id );
	}

	// Set the key to use in our transient.
	$ky = Core\TRANSIENT_PREFIX . 'status_rows_' . absint( $user_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the data from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Get the allowed post types.
		$get_post_types = get_post_type_array();

		// Call the global DB.
		global $wpdb;

		// Flush out the cache first.
		$wpdb->flush();

		// Set up our query.
		// phpcs:ignore -- this is set up exactly like core does it. ugly, but works.
		// phpcs:disable
		$query_args = $wpdb->prepare("
			SELECT   COUNT(*)
			FROM     $wpdb->posts
			WHERE    post_status = '%s'
			AND      post_author = '%d'
			AND      post_type IN ( '" . implode( "','", $get_post_types ) . "' )
		", esc_sql( $post_status ), absint( $user_id ) );
		// phpcs:enable

		// Process the query.
		// phpcs:ignore -- the following is a false positive; this SQL is safe, everything is escaped above.
		$query_run  = $wpdb->get_var( $query_args );

		// Bail without any items.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data for a minute.
		set_transient( $ky, $query_run, MINUTE_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// And return the resulting.
	return $cached_dataset;
}

/**
 * Get the count of all the items for a user with a specific search term.
 *
 * @param  integer $user_id      The user ID we are checking against.
 * @param  string  $search_term  What term a user searched for.
 * @param  boolean $purge        Whether to purge the cache first.
 *
 * @return array
 */
function query_search_row_count( $user_id = 0, $search_term = '', $purge = false ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// Return the whole count without a term to check.
	if ( empty( $search_term ) ) {
		return query_total_row_count( $user_id );
	}

	// Set the key to use in our transient.
	$ky = Core\TRANSIENT_PREFIX . 'search_rows_' . absint( $user_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the data from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Get the allowed post statuses and types.
		$get_post_stats = get_post_status_array();
		$get_post_types = get_post_type_array();

		// Define the args we need.
		$define_search  = [
			'fields'           => 'ids',
			'numberposts'      => -1,
			'author'           => absint( $user_id ),
			'post_type'        => $get_post_types,
			'post_status'      => $get_post_stats,
			'suppress_filters' => true, // phpcs:ignore -- we need this suppressed.
			's'                => $search_term,
		];

		// Try to get the content.
		$maybe_content  = get_posts( $define_search ); // phpcs:ignore -- this query needs to be specific.

		// Bail without anything to count.
		if ( empty( $maybe_content ) || is_wp_error( $maybe_content ) ) {
			return false;
		}

		// Set our count.
		$set_item_count = count( $maybe_content );

		// Set our transient with our data for a minute.
		set_transient( $ky, $set_item_count, MINUTE_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $set_item_count;
	}

	// And return the resulting.
	return $cached_dataset;
}

/**
 * Get the count of all the items for a user with a one or more dropdown.
 *
 * @param  integer $user_id    The user ID we are checking against.
 * @param  array   $drop_args  What dropdown args we have.
 * @param  boolean $purge      Whether to purge the cache first.
 *
 * @return array
 */
function query_dropdown_row_count( $user_id = 0, $drop_args = [], $purge = false ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// Return the whole count without args to check.
	if ( empty( $drop_args ) ) {
		return query_total_row_count( $user_id );
	}

	// Set the key to use in our transient.
	$ky = Core\TRANSIENT_PREFIX . 'drows_' . absint( $user_id ) . '_' . implode( '_', $drop_args );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the data from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Get the allowed post statuses and types.
		$get_post_stats = get_post_status_array();
		$get_post_types = get_post_type_array();

		// Define the args we need.
		$define_search  = [
			'fields'           => 'ids',
			'numberposts'      => -1,
			'author'           => absint( $user_id ),
			'post_type'        => $get_post_types,
			'post_status'      => $get_post_stats,
			'suppress_filters' => true, // phpcs:ignore -- we need this suppressed.
			'tax_query'        => [] // phpcs:ignore -- we are looking up by terms, so... yeah. gotta.
		];

		// Check for a yearmonth.
		if ( ! empty( $drop_args['yearmonth'] ) ) {
			$define_search['m'] = absint( $drop_args['yearmonth'] );
		}

		// Check for categories.
		if ( ! empty( $drop_args['category'] ) ) {

			// Set up the part for categories.
			$define_search['tax_query'][] = [
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => absint( $drop_args['category'] ),
			];
		}

		// Check for tags.
		if ( ! empty( $drop_args['post_tag'] ) ) {

			// Set up the part for categories.
			$define_search['tax_query'][] = [
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => absint( $drop_args['post_tag'] ),
			];
		}

		// If we have more than one term, add the relation argument.
		if ( ! empty( $define_search['tax_query'] ) && 1 < count( $define_search['tax_query'] ) ) {
			$define_search['tax_query']['relation'] = 'AND';
		}

		// Try to get the content.
		$maybe_content  = get_posts( $define_search ); // phpcs:ignore -- this query needs to be specific.

		// Bail without anything to count.
		if ( empty( $maybe_content ) || is_wp_error( $maybe_content ) ) {
			return false;
		}

		// Set our count.
		$set_item_count = count( $maybe_content );

		// Set our transient with our data for a minute.
		set_transient( $ky, $set_item_count, MINUTE_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $set_item_count;
	}

	// And return the resulting.
	return $cached_dataset;
}

/**
 * Query our current items.
 *
 * @param  integer $user_id     The user ID we are checking against.
 * @param  array   $setup_args  The various args we wanna use.
 *
 * @return array
 */
function query_default_items( $user_id = 0, $setup_args = [] ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// Set a default offset and per-page.
	$ppage  = ! empty( $setup_args['per_page'] ) ? $setup_args['per_page'] : 20;
	$offset = 0;

	// Calculate the offset.
	if ( ! empty( $setup_args['paged'] ) && absint( $setup_args['paged'] ) > 1 ) {
		$offset = ( absint( $setup_args['paged'] ) - 1 ) * absint( $ppage );
	}

	// Define the order and orderby, with defaults.
	$define_orderby = ! empty( $setup_args['orderby'] ) ? $setup_args['orderby'] : 'post_date';
	$define_order   = ! empty( $setup_args['order'] ) ? strtoupper( $setup_args['order'] ) : 'DESC';

	// Now set the proper order and orderby string, as per WooCommerce.
	$orderby_sql    = sanitize_sql_orderby( "{$define_orderby} {$define_order}" );

	// Get the allowed post statuses and types.
	$get_post_stats = get_post_status_array();
	$get_post_types = get_post_type_array();

	// Call the global DB.
	global $wpdb;

	// Flush out the cache first.
	$wpdb->flush();

	// Set up our query.
	// phpcs:ignore -- this is set up exactly like core does it. ugly, but works.
	// phpcs:disable
	$query_args = $wpdb->prepare("
		SELECT   ID,post_date,post_title,post_status,post_type
		FROM     $wpdb->posts
		WHERE    post_author = '%d'
		AND      post_status IN ( '" . implode( "','", $get_post_stats ) . "' )
		AND      post_type IN ( '" . implode( "','", $get_post_types ) . "' )
		ORDER BY {$orderby_sql}
		LIMIT    %d
		OFFSET   %d
	", absint( $user_id ), absint( $ppage ), absint( $offset ) );
	// phpcs:enable

	// Process the query.
	// phpcs:ignore -- the following is a false positive; this SQL is safe, everything is escaped above.
	$query_run  = $wpdb->get_results( $query_args, ARRAY_A );

	// Return the result, or an empty array.
	return empty( $query_run ) || is_wp_error( $query_run ) ? [] : $query_run;
}

/**
 * Query our current items based on a post status.
 *
 * @param  integer $user_id      The user ID we are checking against.
 * @param  array   $setup_args   The various args we wanna use.
 * @param  string  $post_status  Which post status we want.
 *
 * @return array
 */
function query_filtered_status_items( $user_id = 0, $setup_args = [], $post_status = '' ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// If no status was passed, send back the full one.
	if ( empty( $post_status ) || 'all' === $post_status ) {
		return query_default_items( $user_id, $setup_args );
	}

	// Set a default offset and per-page.
	$ppage  = ! empty( $setup_args['per_page'] ) ? $setup_args['per_page'] : 20;
	$offset = 0;

	// Calculate the offset.
	if ( ! empty( $setup_args['paged'] ) && absint( $setup_args['paged'] ) > 1 ) {
		$offset = ( absint( $setup_args['paged'] ) - 1 ) * absint( $ppage );
	}

	// Define the order and orderby, with defaults.
	$define_orderby = ! empty( $setup_args['orderby'] ) ? $setup_args['orderby'] : 'post_date';
	$define_order   = ! empty( $setup_args['order'] ) ? strtoupper( $setup_args['order'] ) : 'DESC';

	// Now set the proper order and orderby string, as per WooCommerce.
	$orderby_sql    = sanitize_sql_orderby( "{$define_orderby} {$define_order}" );

	// Get the allowed post types.
	$get_post_types = get_post_type_array();

	// Call the global DB.
	global $wpdb;

	// Flush out the cache first.
	$wpdb->flush();

	// Set up our query.
	// phpcs:ignore -- this is set up exactly like core does it. ugly, but works.
	// phpcs:disable
	$query_args = $wpdb->prepare("
		SELECT   ID,post_date,post_title,post_status,post_type
		FROM     $wpdb->posts
		WHERE    post_status = '%s'
		AND      post_author = '%d'
		AND      post_type IN ( '" . implode( "','", $get_post_types ) . "' )
		ORDER BY {$orderby_sql}
		LIMIT    %d
		OFFSET   %d
	", esc_sql( $post_status ), absint( $user_id ), absint( $ppage ), absint( $offset ) );
	// phpcs:enable

	// Process the query.
	// phpcs:ignore -- the following is a false positive; this SQL is safe, everything is escaped above.
	$query_run  = $wpdb->get_results( $query_args, ARRAY_A );

	// Return the result, or an empty array.
	return empty( $query_run ) || is_wp_error( $query_run ) ? [] : $query_run;
}

/**
 * Query our current items based on a post status.
 *
 * @param  integer $user_id      The user ID we are checking against.
 * @param  array   $setup_args   The various args we wanna use.
 * @param  string  $search_term  What term we are looking for.
 *
 * @return array
 */
function query_filtered_search_items( $user_id = 0, $setup_args = [], $search_term = '' ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// If no term was passed, send back the full one.
	if ( empty( $search_term ) ) {
		return query_default_items( $user_id, $setup_args );
	}

	// Set a default offset and per-page.
	$ppage  = ! empty( $setup_args['per_page'] ) ? $setup_args['per_page'] : 20;
	$offset = 0;

	// Calculate the offset.
	if ( ! empty( $setup_args['paged'] ) && absint( $setup_args['paged'] ) > 1 ) {
		$offset = ( absint( $setup_args['paged'] ) - 1 ) * absint( $ppage );
	}

	// Define the order and orderby, with defaults.
	$define_orderby = ! empty( $setup_args['orderby'] ) ? str_replace( 'post_', '', $setup_args['orderby'] ) : 'date';
	$define_order   = ! empty( $setup_args['order'] ) ? strtoupper( $setup_args['order'] ) : 'DESC';

	// Get the allowed post statuses and types.
	$get_post_stats = get_post_status_array();
	$get_post_types = get_post_type_array();

	// Define the args we need.
	$define_search  = [
		'author'           => absint( $user_id ),
		'numberposts'      => absint( $ppage ),
		'offset'           => $offset,
		'orderby'          => $define_orderby,
		'order'            => $define_order,
		'post_type'        => $get_post_types,
		'post_status'      => $get_post_stats,
		'suppress_filters' => true, // phpcs:ignore -- we need this suppressed.
		's'                => $search_term,
	];

	// Try to get the content.
	$maybe_content  = get_posts( $define_search ); // phpcs:ignore -- this query needs to be specific.

	// Return one or the other.
	return ! empty( $maybe_content ) && ! is_wp_error( $maybe_content ) ? $maybe_content : [];
}

/**
 * Query our current items based on a post status.
 *
 * @param  integer $user_id      The user ID we are checking against.
 * @param  array   $setup_args   The various args we wanna use.
 * @param  array   $drop_args    What possible values from the dropdowns.
 *
 * @return array
 */
function query_filtered_dropdown_items( $user_id = 0, $setup_args = [], $drop_args = [] ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// If no term was passed, send back the full one.
	if ( empty( $drop_args ) ) {
		return query_default_items( $user_id, $setup_args );
	}

	// Set a default offset and per-page.
	$ppage  = ! empty( $setup_args['per_page'] ) ? $setup_args['per_page'] : 20;
	$offset = 0;

	// Calculate the offset.
	if ( ! empty( $setup_args['paged'] ) && absint( $setup_args['paged'] ) > 1 ) {
		$offset = ( absint( $setup_args['paged'] ) - 1 ) * absint( $ppage );
	}

	// Define the order and orderby, with defaults.
	$define_orderby = ! empty( $setup_args['orderby'] ) ? str_replace( 'post_', '', $setup_args['orderby'] ) : 'date';
	$define_order   = ! empty( $setup_args['order'] ) ? strtoupper( $setup_args['order'] ) : 'DESC';

	// Get the allowed post statuses and types.
	$get_post_stats = get_post_status_array();
	$get_post_types = get_post_type_array();

	// Define the args we need.
	$define_search  = [
		'author'           => absint( $user_id ),
		'numberposts'      => absint( $ppage ),
		'offset'           => $offset,
		'orderby'          => $define_orderby,
		'order'            => $define_order,
		'post_type'        => $get_post_types,
		'post_status'      => $get_post_stats,
		'suppress_filters' => true, // phpcs:ignore -- we need this suppressed.
		'tax_query'        => [] // phpcs:ignore -- we are looking up by terms, so... yeah. gotta.
	];

	// Check for a yearmonth.
	if ( ! empty( $drop_args['yearmonth'] ) ) {
		$define_search['m'] = absint( $drop_args['yearmonth'] );
	}

	// Check for categories.
	if ( ! empty( $drop_args['category'] ) ) {

		// Set up the part for categories.
		$define_search['tax_query'][] = [
			'taxonomy' => 'category',
			'field'    => 'term_id',
			'terms'    => absint( $drop_args['category'] ),
		];
	}

	// Check for tags.
	if ( ! empty( $drop_args['post_tag'] ) ) {

		// Set up the part for categories.
		$define_search['tax_query'][] = [
			'taxonomy' => 'post_tag',
			'field'    => 'term_id',
			'terms'    => absint( $drop_args['post_tag'] ),
		];
	}

	// If we have more than one term, add the relation argument.
	if ( ! empty( $define_search['tax_query'] ) && 1 < count( $define_search['tax_query'] ) ) {
		$define_search['tax_query']['relation'] = 'AND';
	}

	// Try to get the content.
	$maybe_content  = get_posts( $define_search ); // phpcs:ignore -- this query needs to be specific.

	// Return one or the other.
	return ! empty( $maybe_content ) && ! is_wp_error( $maybe_content ) ? $maybe_content : [];
}

/**
 * Get all the months a user published something.
 *
 * @param  integer $user_id  The user ID we are on.
 * @param  boolean $purge    Whether to purge the cache first.
 *
 * @return array
 */
function query_available_months( $user_id = 0, $purge = false ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return [];
	}

	// Set the key to use in our transient.
	$ky = Core\TRANSIENT_PREFIX . 'user_mns_' . absint( $user_id );

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG || ! empty( $purge ) ) {
		delete_transient( $ky );
	}

	// Attempt to get the data from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

		// Get the allowed post statuses and types.
		$get_post_stats = get_post_status_array();
		$get_post_types = get_post_type_array();

		// Call the global DB.
		global $wpdb;

		// Flush out the cache first.
		$wpdb->flush();

		// Set up our query.
		// phpcs:ignore -- this is set up exactly like core does it. ugly, but works.
		// phpcs:disable
		$query_args = $wpdb->prepare("
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM            $wpdb->posts
			WHERE           post_author = '%d'
			AND             post_status IN ( '" . implode( "','", $get_post_stats ) . "' )
			AND             post_type IN ( '" . implode( "','", $get_post_types ) . "' )
			ORDER BY        post_date DESC
		", absint( $user_id ) );
		// phpcs:enable

		// Process the query.
		// phpcs:ignore -- the following is a false positive; this SQL is safe, everything is escaped above.
		$query_run  = $wpdb->get_results( $query_args, ARRAY_A );

		// Bail without any data.
		if ( empty( $query_run ) ) {
			return false;
		}

		// Set our transient with our data for a minute.
		set_transient( $ky, $query_run, MINUTE_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $query_run;
	}

	// And return the resulting.
	return $cached_dataset;
}

/**
 * Get all the terms from whatever taxonomy for an ID.
 *
 * @param  integer $post_id   The post ID we have.
 * @param  string  $taxonomy  The taxonomy we wanna check.
 *
 * @return array
 */
function query_single_item_term_data( $post_id = 0, $taxonomy = '' ) {

	// Return nothing if we don't have the important parts.
	if ( empty( $post_id ) || empty( $taxonomy ) ) {
		return [];
	}

	// See if we have any terms here.
	$maybe_haz_term = get_the_terms( absint( $post_id ), esc_attr( $taxonomy ) );

	// Send back the empty.
	if ( empty( $maybe_haz_term ) || is_wp_error( $maybe_haz_term ) ) {
		return [];
	}

	// Return the name / ID pair.
	return wp_list_pluck( $maybe_haz_term, 'name', 'term_id' );
}

/**
 * Return an array of all the post statuses this table gets into.
 *
 * @return array
 */
function get_post_status_array() {

	// Set the array of post statuses we want to include.
	$setup_data = [
		'publish',
		'draft',
		'pending',
		'private',
		'future',
	];

	// Return the data, with a filter.
	return apply_filters( Core\HOOK_PREFIX . 'post_statuses', $setup_data );
}

/**
 * Return an array of all the post types this table gets into.
 *
 * @return array
 */
function get_post_type_array() {

	// Get all the public post types.
	$set_types = get_post_types( [ 'public' => true ] );

	// Remove attachments.
	unset( $set_types['attachment'] );

	// Return the data, with a filter.
	return apply_filters( Core\HOOK_PREFIX . 'post_types', $set_types );
}
