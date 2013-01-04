<?php /*
Plugin Name: WP Bit.ly
Plugin URI: http://wordpress.org/extend/wp-bitly/
Description: WP Bit.ly uses the Bit.ly API to generate short links for all your articles and pages. Visitors can use the link to email, share, or bookmark your pages quickly and easily.
Version: 1.0.1
Author: <a href="http://mark.watero.us/">Mark Waterous</a> & <a href="http://www.chipbennett.net/">Chip Bennett</a>

Copyright 2010 Mark Waterous (mark@watero.us)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

register_activation_hook( __FILE__, 'wpbitly_activate' );
register_uninstall_hook( __FILE__, 'wpbitly_uninstall' );

require( 'wp-bitly-options.php' );
require( 'wp-bitly-views.php' );

global $wpbitly_options;
$wpbitly_options = wpbitly_get_options();

/**
 * Load Plugin textdomain
 */
function wpbitly_load_textdomain() {
	load_plugin_textdomain( 'wpbitly', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'); 
}
// Load Plugin textdomain
add_action( 'plugins_loaded', 'wpbitly_load_textdomain' );

// Load our controller class... it's helpful!
$wpbitly = new wpbitly_options( $wpbitly_options );


// If we're competing with WordPress.com stats... chances are people are already using wp.me
// but we'll remove the competitive headers just in case.
if ( function_exists( 'wpme_shortlink_header' ) )
{
	remove_action( 'wp',      'wpme_shortlink_header' );
	remove_action( 'wp_head', 'wpme_shortlink_wp_head' );
}

/**
 * Add "Get Shortlink" link to menu header
 */
if ( $wpbitly_options['enable_admin_toolbar_shortlink'] ) {
	add_action( 'admin_bar_menu', 'wp_admin_bar_shortlink_menu', 90 );
}


// Automatic generation is disabled if the API information is invalid
if ( ! $wpbitly_options['wpbitly_invalid'] )
{
	add_action( 'save_post', 'wpbitly_generate_shortlink', 10, 1 );
}


// Settings menu on plugins page.
add_filter( 'plugin_action_links', 'wpbitly_filter_plugin_actions', 10, 2 );

// One guess?
add_shortcode( 'wpbitly', 'wpbitly_shortcode' );

// WordPress 3.0!
add_filter( 'get_shortlink', 'wpbitly_get_shortlink', 10, 3 );

/**
 * The activation routine deletes unused, old options
 */
function wpbitly_activate() {

	delete_option( 'wpbitly_invalid' );
	delete_option( 'wpbitly_version' );
	
}

/**
 * The uninstall routine deletes all options related to WP Bit.ly.
 */
function wpbitly_uninstall()
{

	// Delete associated options
	delete_option( 'wpbitly_options' );

	// Grab all posts
	$posts = get_posts( 'numberposts=-1&post_type=any' );

	// And remove our meta information from them
	foreach ( $posts as $post )
	{
		delete_post_meta( $post->ID, '_wpbitly' );
	}

}


/**
 * Borrowed from the Sociable plugin, this adds a 'Settings' option to the
 * entry on the WP Plugins page.
 *
 * @param $links array  The array of links displayed by the plugins page
 * @param $file  string The current plugin being filtered.
 */

function wpbitly_filter_plugin_actions( $links, $file )
{
	static $wpbitly_plugin;

	if ( ! isset( $wpbitly_plugin ) )
	{
		$wpbitly_plugin = plugin_basename( __FILE__ );
	}
	
	if ( $file == $wpbitly_plugin )
	{
		$settings_link = '<a href="' . admin_url( 'options-writing.php' ) . '">' . __( 'Settings', 'wpbitly' ) . '</a>';
		array_unshift( $links, $settings_link );
	}

	return $links;

}


/**
 * Generates the shortlink for the post specified by $post_id.
 */

function wpbitly_generate_shortlink( $post_id )
{
	global $wpbitly;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return false;

	// If this information hasn't been filled out, there's no need to go any further.
	if ( empty( $wpbitly->options['bitly_username'] ) || empty( $wpbitly->options['bitly_api_key'] ) || get_option( 'wpbitly_invalid' ) )
		return false;


	// Do we need to generate a shortlink for this post? (save_post is fired when revisions, auto-drafts, et al are saved)
	if ( $parent = wp_is_post_revision( $post_id ) )
	{
		$post_id = $parent;
	}

	$post = get_post( $post_id );

	if ( 'publish' != $post->post_status && 'future' != $post->post_status )
		return false;


	// Link to be generated
	$permalink = get_permalink( $post_id );
	$wpbitly_link = get_post_meta( $post_id, '_wpbitly', true );


	if ( $wpbitly_link != false )
	{
		$url = sprintf( $wpbitly->url['expand'], $wpbitly_link, $wpbitly->options['bitly_username'], $wpbitly->options['bitly_api_key'] );
		$bitly_response = wpbitly_curl( $url );

		// If we have a shortlink for this post already, we've sent it to the Bit.ly expand API to verify that it will actually forward to this posts permalink
		if ( is_array( $bitly_response ) && $bitly_response['status_code'] == 200 && $bitly_response['data']['expand'][0]['long_url'] == $permalink )
			return false;

		// The expanded URLs don't match, so we can delete and regenerate
		delete_post_meta( $post_id, '_wpbitly' );
	}

	// Submit to Bit.ly API and look for a response
	$url = sprintf( $wpbitly->url['shorten'], $wpbitly->options['bitly_username'], $wpbitly->options['bitly_api_key'], urlencode( $permalink ) );
	$bitly_response = wpbitly_curl( $url );

	// Success?
	if ( is_array( $bitly_response ) && $bitly_response['status_code'] == 200 )
	{
		update_post_meta( $post_id, '_wpbitly', $bitly_response['data']['url'] );
	}

}


/**
 * Return the wpbitly_get_shortlink method to the built in WordPress pre_get_shortlink
 * filter for internal use.
 */

function wpbitly_get_shortlink( $shortlink, $id, $context )
{

	// Look for the post ID passed by wp_get_shortlink() first
	if ( empty( $id ) )
	{
		global $post;
		$id = ( isset( $post ) ? $post->ID : null );
	}

	// Fall back in case we still don't have a post ID
	if ( empty( $id ) )
	{
		// Maybe we got passed a shortlink already? Better to return something than nothing.
		// Some wacky test cases might help us polish this up.
		if ( ! empty( $shortlink ) )
			return $shortlink;

		return false;

	}

	$shortlink = get_post_meta( $id, '_wpbitly', true );

	if ( $shortlink == false )
	{
		wpbitly_generate_shortlink( $id );
		$shortlink = get_post_meta( $id, '_wpbitly', true );
	}

	return $shortlink;

}


/**
 * This is merely a wrapper for the shortlink API method the_shortlink()
 */

function wpbitly_shortcode( $atts )
{
	global $post;

	$defaults = array(
		'text' => '',
		'title' => '',
		'before' => '',
		'after'  => '',
	);

	extract( shortcode_atts( $defaults, $atts ) );

	return the_shortlink( $text, $title, $before, $after );

}


/**
 * WP Bit.ly wrapper for cURL - this method relies on the ability to use cURL
 * or file_get_contents. If cURL is not available and allow_url_fopen is set
 * to false this method will fail and the plugin will not be able to generate
 * shortlinks.
 */

function wpbitly_curl( $url )
{
	global $wpbitly;

	if ( ! isset( $url ) )
		return false;

	if ( function_exists( 'curl_init' ) )
	{

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url );
		$result = curl_exec($ch);
		curl_close($ch);

	}
	else
	{
		$result = file_get_contents( $url );
	}

	if ( ! empty( $result ) )
		return json_decode( $result, true );

	return false;

}

?>