<?php

add_action( 'admin_init', 'wpbitly_options_init' );


function wpbitly_options_init()
{
	register_setting( 'wpbitly_admin_options', 'wpbitly_options', 'wpbitly_options_validate' );
}


function wpbitly_options_validate( $options )
{
	global $wpbitly;

	function clean( $options )
	{

		foreach ( $options as $k => $v )
		{

			if ( is_array( $v ) )
			{
				$options[$k] = clean( $v );
			}
			else
			{
				$options[$k] = trim( esc_attr( urlencode( $v ) ) );
			}

		}

		return $options;

	}

	$valid = false;

	$options = clean( $options );

	if ( ! empty( $options['bitly_username'] ) && ! empty( $options['bitly_api_key'] ) ) {

		$url = sprintf( $wpbitly->url['validate'], $options['bitly_username'], $options['bitly_api_key'] );

		$wpbitly_validate = wpbitly_curl( $url );

		if ( is_array( $wpbitly_validate ) && $wpbitly_validate['data']['valid'] == 1 )
			$valid = true;

	}

	if ( ! isset( $options['post_types'] ) )
	{
		$options['post_types'] = array();
	}

	if ( $valid === true )
		delete_option( 'wpbitly_invalid' );
	else
		update_option( 'wpbitly_invalid', 1 );

	return $options;

}


class wpbitly_options
{

	public $version;

	public $options;

	public $url = array(
		'shorten'  => 'http://api.bit.ly/v3/shorten?login=%s&apiKey=%s&uri=%s&format=json',
		'expand'   => 'http://api.bit.ly/v3/expand?shortUrl=%s&login=%s&apiKey=%s&format=json',
		'validate' => 'http://api.bit.ly/v3/validate?x_login=%s&x_apiKey=%s&login=wpbitly&apiKey=R_bfef36d10128e7a2de09637a852c06c3&format=json',
		'clicks'   => 'http://api.bit.ly/v3/clicks?shortUrl=%s&login=%s&apiKey=%s&format=json',
	);


	public function __construct( array $defaults )
	{

		$this->_get_version();
		$this->_refresh_options( $defaults );

		add_action( 'init', array( $this, 'check_options' ) );

	}


	private function _get_version()
	{
		$version = get_option( 'wpbitly_version' );

		if ( $version == false || $version != WPBITLY_VERSION )
		{
			update_option( 'wpbitly_version', WPBITLY_VERSION );
			$this->version = WPBITLY_VERSION;
		}

		$this->version = $version;
	}


	private function _refresh_options( $defaults )
	{

		$options = get_option( 'wpbitly_options', false );

		if ( $options === false )
		{
			update_option( 'wpbitly_options', $defaults );
		}
		else if ( is_array( $options ) )
		{
			$diff = array_diff_key( $defaults, $options );

			if ( ! empty( $diff ) )
			{
				$options = array_merge( $options, $diff );
				update_option( 'wpbitly_options', $options );
			}
		}

		$this->options = $options;

	}


	public function check_options()
	{

		// Display any necessary administrative notices
		if ( current_user_can( 'edit_posts' ) )
		{
			if ( empty( $this->options['bitly_username'] ) || empty( $this->options['bitly_api_key'] ) )
			{
				if ( ! isset( $_GET['page'] ) || $_GET['page'] != 'wpbitly' )
				{
				add_action( 'admin_notices', array( $this, 'notice_setup' ) );
				}
			}

			if ( get_option( 'wpbitly_invalid' ) !== false && isset( $_GET['page'] ) && $_GET['page'] == 'wpbitly' )
			{
				add_action( 'admin_notices', array( $this, 'notice_invalid' ) );
			}
		}

	}


	public function notice_setup()
	{

		$title = __( 'WP Bit.Ly is almost ready!', 'wpbitly' );
		$settings_link = '<a href="options-general.php?page=wpbitly">'.__( 'settings page', 'wpbitly' ).'</a>';
		$message = sprintf( __( 'Please visit the %s to configure WP Bit.ly', 'wpbitly' ), $settings_link );

		return $this->display_notice( "<strong>{$title}</strong> {$message}", 'error' );

	}


	public function notice_invalid()
	{

		$title = __( 'Invalid API Key!', 'wpbitly' );
		$message = __( "Your username and API key for bit.ly can't be validated. All features are temporarily disabled.", 'wpbitly' );

		return $this->display_notice( "<strong>{$title}</strong> {$message}", 'error' );

	}


	public function display_notice( $string, $type = 'updated', $echo = true )
	{

		if ( $type != 'updated' )
			$type == 'error';

		$string = '<div id="message" class="' . $type . ' fade"><p>' . $string . '</p></div>';

		if ( $echo != true )
			return $string;

		echo $string;

	}

}

abstract class wpbitly_post
{

	private static $post_id;

	private static $permalink = array();

	private static $shortlink;


	public static function id()
	{

		if ( ! self::$post_id )
		{
			self::_get_post_id();
		}

		return self::$post_id;

	}


	public static function permalink( $key = 'raw' )
	{

		if ( empty( self::$permalink ) )
		{
			self::_get_permalink();
		}

		switch ( $key )
		{
			case 'raw':     return self::$permalink['raw'];
			case 'encoded': return self::$permalink['encoded'];
			default:        return self::$permalink;
		}

	}


	public static function shortlink()
	{

		if ( ! self::$shortlink )
		{
			self::_get_shortlink();
		}

		return self::$shortlink;

	}


	private static function _get_post_id()
	{
		global $post;

		if ( is_null( $post ) )
		{
			trigger_error( 'wpbitly::id() cannot be called before $post is set in the global namespace.', E_USER_ERROR );
		}

		self::$post_id = $post->ID;

		if ( $parent = wp_is_post_revision( self::$post_id ) )
		{
			self::$post_id = $parent;
		}

	}


	private static function _get_permalink()
	{

		if ( ! is_array( self::$permalink ) )
		{
			self::$permalink = array();
		}

		self::$permalink['raw']     = get_permalink( self::$post_id );
		self::$permalink['encoded'] = urlencode( self::$permalink['raw'] );

	}


	private static function _get_shortlink()
	{
		self::$shortlink = get_post_meta( self::$post_id, '_wpbitly', true );
	}

}