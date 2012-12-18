<?php

add_action( 'admin_menu', 'wpbitly_add_pages' );
add_action( 'admin_head', 'wpbitly_add_metaboxes' );


function wpbitly_add_pages()
{
	$hook = add_options_page( 'WP Bit.ly Options', 'WP Bit.ly', 'edit_posts', 'wpbitly', 'wpbitly_display' );
		add_action( 'admin_print_styles-'.$hook, 'wpbitly_print_styles' ); 
		add_action( 'admin_print_scripts-'.$hook, 'wpbitly_print_scripts' ); 
}


function wpbitly_print_styles()
{
	wp_enqueue_style( 'dashboard' );
	wp_enqueue_style( 'wpbitly', plugins_url( '', __FILE__ ).'/assets/wpbitly.css', false, WPBITLY_VERSION, 'screen' );
}


function wpbitly_print_scripts()
{
	wp_enqueue_script( 'dashboard' );
//	wp_enqueue_script( 'jquery-validate', 'http://dev.jquery.com/view/trunk/plugins/validate/jquery.validate.js', 'jQuery', false, true );
}


function wpbitly_add_metaboxes()
{
	global $post;

	if ( is_object( $post ) )
	{

		/** We can't use this until 3.1 and we're sure the majority of users have at least 3.0
		$shortlink = wp_get_shortlink( $post->ID );
		*/
		$shortlink = get_post_meta( $post->ID, '_wpbitly', true );

		if ( empty( $shortlink ) )
			return;

		add_meta_box( 'wpbitly-meta', 'WP Bit.ly', 'wpbitly_build_metabox', $post->post_type, 'side', 'default', array( $shortlink ) );

	}

}


function wpbitly_build_metabox( $post, $args )
{
	global $wpbitly;

	$shortlink = $args['args'][0];

	echo '<label class="screen-reader-text" for="new-tag-post_tag">WP Bit.ly</label>';
	echo '<p style="margin-top: 8px;"><input type="text" id="wpbitly-shortlink" name="_wpbitly" size="32" autocomplete="off" value="'.$shortlink.'" style="margin-right: 4px; color: #aaa;" /></p>';

	$url = sprintf( $wpbitly->url['clicks'], $shortlink, $wpbitly->options['bitly_username'], $wpbitly->options['bitly_api_key'] );
	$bitly_response = wpbitly_curl( $url );

	echo '<h4 style="margin-left: 4px; margin-right: 4px; padding-bottom: 3px; border-bottom: 4px solid #eee;">Shortlink Stats</h4>';

	if ( is_array( $bitly_response ) && $bitly_response['status_code'] == 200 )
	{
		echo "<p>Global Clicks: <strong>{$bitly_response['data']['clicks'][0]['global_clicks']}</strong><br/>";
		echo "<p>User Clicks: <strong>{$bitly_response['data']['clicks'][0]['user_clicks']}</strong></p>";
	}
	else
	{
		echo '<p class="error" style="padding: 4px;">There was a problem retrieving stats!</p>';
	}

}


function wpbitly_display()
{
	global $wpbitly;

	echo '<div class="wrap">';
	screen_icon();
	echo '<h2 style="margin-bottom: 1em;">' . __( 'WP Bit.ly Options', 'wpbitly' ) . '</h2>';

?>

	<div class="postbox-container" style="width: 70%;">
	<div class="metabox-holder">	
	<div class="meta-box-sortables">
		<form action="options.php" id="wpbitly" method="post">
		<?php
        	settings_fields( 'wpbitly_admin_options' );
			wpbitly_postbox_options();
		?>
		</form>
	</div></div>
	</div> <!-- .postbox-container -->

	<div class="postbox-container" style="width: 24%;">
	<div class="metabox-holder">	
	<div class="meta-box-sortables">
	<?php
		wpbitly_postbox_support();
		if ( ! empty( $wpbitly->options['bitly_username'] ) && ! empty( $wpbitly->options['bitly_api_key'] ) && ! get_option( 'wpbitly_invalid' ) )
		{
			wpbitly_postbox_generate();
		}
	?>
	</div></div>
	</div> <!-- .postbox-container -->

	</div> <!-- .wrap -->
<?php

}


function wpbitly_postbox_options()
{
	global $wpbitly;

	$exclude_types = array(
		'revision',
		'nav_menu_item',
	);

	$post_types = get_post_types();

	$checkboxes = array();

	foreach ( $post_types as $pt )
	{
		if ( ! in_array( $pt, $exclude_types ) )
		{
			$checked = false;

			if ( in_array( $pt, $wpbitly->options['post_types'] ) )
			{
				$checked = $pt;
			}

			$checkboxes[] = '<input name="wpbitly_options[post_types][]" type="checkbox" value="'.$pt.'" '.checked( $pt, $checked, false ).' /><span>'.ucwords( str_replace( '_', ' ', $pt ) ).'</span><br />';

		}
	}


	$options = array();

	$options[] = array(
		'id'    => 'bitly_username',
		'name'  => __( 'Bit.ly Username:', 'wpbitly' ),
		'desc'  => __( 'The username you use to log in to your Bit.ly account.', 'wpbitly' ),
		'input' => '<input name="wpbitly_options[bitly_username]" type="text" value="'.$wpbitly->options['bitly_username'].'" />'
	);

	$options[] = array(
		'id'    => 'bitly_api_key',
		'name'  => __( 'Bit.ly API Key:', 'wpbitly' ),
		'desc'  => sprintf( __( 'Your API key can be found on your %1$s', 'wpbitly' ), '<a href="http://bit.ly/account/" target="_blank">'.__( 'Bit.ly account page', 'wpbitly' ).'</a>' ),
		'input' => '<input name="wpbitly_options[bitly_api_key]" type="text" value="'.$wpbitly->options['bitly_api_key'] . '" />'
	);

	$options[] = array(
		'id'    => 'post_types',
		'name'  => __( 'Post Types:', 'wpbitly' ),
		'desc'  => __( 'What kind of posts should short links be generated for?', 'wpbitly' ),
		'input' => implode( "\n", $checkboxes ),
  	);

	$output  = '<div class="intro">';
	$output .= '<p>' . __( 'Use the following options to configure your Bit.ly API access and determine the general operation of the WP Bit.ly plugin.', 'wpbitly' ).'</p>';
	$output .= '</div>';

	$output .= wpbitly_build_form( $options );

	wpbitly_build_postbox( 'wp_bitly_options', __( 'General Settings', 'wpbitly' ), $output );

}


function wpbitly_postbox_support()
{

	$output  = '<p>' . __( 'If you require support, or would like to contribute to the further development of this plugin, please choose one of the following;', 'wpbitly' ) . '</p>';
	$output .= '<ul class="links">';
	$output .= '<li><a href="http://mark.watero.us/">' . __( 'Author Homepage (Mark)', 'wpbitly' ) . '</a></li>';
	$output .= '<li><a href="http://www.chipbennett.net/">' . __( 'Author Homepage (Chip)', 'wpbitly' ) . '</a></li>';
	$output .= '<li><a href="http://mark.watero.us/wordpress-plugins/wp-bitly/">' . __( 'Plugin Homepage', 'wpbitly' ) . '</a></li>';
	$output .= '<li><a href="http://wordpress.org/extend/plugins/wp-bitly/">' . __( 'Rate This Plugin', 'wpbitly' ) . '</a></li>';
//	$output .= '<li><a href="http://mark.watero.us/wordpress-plugins/oops/">' . __( 'Bug Reports', 'wpbitly' ) . '</a></li>';
//	$output .= '<li><a href="http://mark.watero.us/">' . __( 'Feature Requests', 'wpbitly' ) . '</a></li>';
	$output .= '<li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9847234">' . __( 'Donate To The Cause', 'wpbitly' ) . '</a></li>';
	$output .= '</ul>';

//	$output .= '<div class="logo"><a href="http://mark.watero.us/" target="_blank" title="Visit the Author">http://mark.watero.us/</a></div>';

	wpbitly_build_postbox( 'support', 'WP Bit.ly', $output );

}


function wpbitly_postbox_generate()
{
	global $wpbitly;

	$output = '';

	if ( isset( $_POST['wpbitly_generate'] ) )
	{

		$generate = $wpbitly->options['post_types'];

		if ( empty( $wpbitly->options['bitly_username'] ) || empty( $wpbitly->options['bitly_api_key'] ) || get_option( 'wpbitly_invalid' ) )
		{
			$output .= '<div class="error"><p>' . $status . __( 'You must configure your username and API key first!', 'wpbitly' ) . '</p></div>';
		}
		else
		{

			$posts = get_posts( array(
				'numberposts' => '-1',
				'post_type'   => $generate,
			) );

			foreach ( $posts as $the )
			{
				if ( ! get_post_meta( $the->ID, '_wpbitly', true ) )
				{
					wpbitly_generate_shortlink( $the->ID );
				}
			}

			$output .= '<div class="updated fade"><p style="font-weight: 700;">'.__( 'Short links have been generated for the selected post type(s)!', 'wpbitly' ).'</p></div>';

		} // if ( empty )

	} // if ( isset )

	$output .= '<form action="" method="post">';
	$output .= '<p class="wpbitly utility"><input type="submit" name="wpbitly_generate" class="button-primary" value="' . __( 'Generate Links', 'wpbitly' ) . '" /></p>';
	$output .= '</form>';

	wpbitly_build_postbox( 'wpbitly_generate', __( 'Generate Short Links', 'wpbitly' ), $output );

}


function wpbitly_build_postbox( $id, $title, $content, $echo = true )
{

	$output  = '<div id="wpbitly_' . $id . '" class="postbox">';
	$output .= '<div class="handlediv" title="Click to toggle"><br /></div>';
	$output .= '<h3 class="hndle"><span>' . $title . '</span></h3>';
	$output .= '<div class="inside">';
	$output .= $content;
	$output .= '</div></div>';

	if ( $echo === true )
	{
		echo $output;
	}

	return $output;

}


function wpbitly_build_form( $options, $button = 'secondary' )
{

	$output = '<fieldset>';

	foreach ( $options as $option )
	{

		$output .= '<dl' . ( isset( $option['class'] ) ? ' class="' . $option['class'] . '"' : '' ) . '>';
		$output .= '<dt><label for="wpbitly_options[' . $option['id'] . '">' . $option['name'] . '</label>';

		if ( isset( $option['desc'] ) )
		{
			$output .= '<p>' . $option['desc'] . '</p>';
		}

		$output .= '</dt>';
		$output .= '<dd>' . $option['input'] . '</dd>';
		$output .= '</dl>';

	}

	$output .= '<div style="clear: both;"></div>';
	$output .= '<p class="wpbitly_submit"><input type="submit" class="button-' . $button . '" value="' . __( 'Save Changes', 'wpbitly' ) . '" /></p>';
	$output .= '</fieldset>';

	return $output;

}
