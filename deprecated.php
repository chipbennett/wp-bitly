<?php

add_action( 'wp',      'wpbitly_shortlink_header' );
add_action( 'wp_head', 'wpbitly_shortlink_wp_head' );

function wpbitly_shortlink_header() {
	global $wp_query;

	if ( headers_sent() )
		return;

	if ( ! $wpbitly_link = wpbitly_get_shortlink( $wp_query->get_queried_object_id() ) )
		return;

	header( 'Link: <' . $wpbitly_link . '>; rel=shortlink' );

}

function wpbitly_shortlink_wp_head() {
	global $wp_query;

	if ( ! $wpbitly_link = wpbitly_get_shortlink( $wp_query->get_queried_object_id() ) )
		return;

	echo "\n\t<link rel=\"shortlink\" href=\"{$wpbitly_link}\"/>\n";

}


/**
 * This function is used to return the Bit.ly shortlink for a specific post.
 * If $post_id is not supplied, attempt to retrieve it from the global namespace.
 *
 * @param $post_id int The WordPress post ID to be used.
 */

function wpbitly_get_shortlink( $post_id )
{
	global $post;

	if ( empty( $post_id ) && ! $post_id = $post->ID )
		return false;

	return get_post_meta( $post_id, '_wpbitly', true );

}




/**
 * Used internally by the shortcode function, this can be used directly by
 * a template to display the short link.
 *
 * @param $text string The text to display as the content of the link. Defaults to the link itself.
 * @param $echo bool   Whether to echo the result or return it. Defaults to true (echo).
 * @param $post_id  int    The WordPress post ID to be used. Defaults to $post->ID if it can.
 */
 
function wpbitly_print( $text = '', $echo = true, $post_id = '' )
{
	global $post;

	// Attempt to get the post ID
	if ( empty( $post_id ) && ! $post_id = $post->ID )
		return;

	$wpbitly_link = wpbitly_get_shortlink( $post_id );

	if ( empty( $text ) )
	{
		$text = $wpbitly_link;
	}

	$wpbitly_print = '<a href="' . $wpbitly_link . '" rel="shortlink" class="wpbitly shortlink">' . $text . '</a>';

	if ( $echo !== true )
		return $wpbitly_print;

	echo $wpbitly_print;
	
}



/**
 * Shortcode for WP Bit.ly uses wpbitly_print() and accepts the same
 * arguments with the exception of echo which it has to do by default.
 */

function wpbitly_shortcode( $atts )
{
	global $post;

	extract( shortcode_atts( array( 'text' => '', 'pid' => $post->ID ), $atts ) );

	return wpbitly_print( $text, false, $post_id );

}