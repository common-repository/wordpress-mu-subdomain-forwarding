<?php

if( VHOST == 'no' ) {
	die( 'Sorry, subdomain forwarding only works on virtual host installs.' );
}

$wpdb->subdftable = $wpdb->base_prefix . 'subdomain_forwarding';

$wpdb->suppress_errors();
$subdf_subhost = $wpdb->escape( preg_replace( "/^www\./", "", $_SERVER[ 'HTTP_HOST' ] ) );
$subdf_subdomain_target_url = $wpdb->get_var( "SELECT target_url FROM {$wpdb->subdftable} WHERE subdomain = '{$subdf_subhost}' LIMIT 1" );
$wpdb->suppress_errors( false );

if ( $subdf_subdomain_target_url ) {
	$subdf_subdomain = $wpdb->get_row( "SELECT subdomain, blog_id, blog_dir, profile_dir, profile_status, title, keywords, description, revisit, robots, user_id FROM {$wpdb->subdftable} WHERE subdomain = '{$subdf_subhost}'" );
	$subdf_request_uri = explode( '/', $_SERVER[ 'REQUEST_URI' ]);

	if ( $subdf_subdomain->profile_status == 1 && ($subdf_request_uri[1] == $subdf_subdomain->profile_dir || ($subdf_request_uri[1] == '' && $subdf_subdomain_target_url == 'PROFILE') )) {
		require_once( ABSPATH . 'wp-includes/user.php' );
		require_once( ABSPATH . 'wp-includes/plugin.php' );
		require_once( ABSPATH . 'wp-includes/wpmu-functions.php' );

		$subdf_profile_headings_array = get_usermeta( $subdf_subdomain->user_id, 'subdf_profile_order' );

		$subdf_profile_header = get_site_option( 'subdf_profile_header' );
		$subdf_profile_footer = get_site_option( 'subdf_profile_footer' );
		$subdf_profile_heading_header = get_site_option( 'subdf_profile_heading_header' );
		$subdf_profile_heading_footer = get_site_option( 'subdf_profile_heading_footer' );
		$subdf_profile_heading_content_header = get_site_option( 'subdf_profile_heading_content_header' );
		$subdf_profile_heading_content_footer = get_site_option( 'subdf_profile_heading_content_header' );

		echo $subdf_profile_header;

		foreach ( $subdf_profile_headings_array as $subdf_profile_heading ) {
			echo $subdf_profile_heading_header;
			echo $subdf_profile_heading;
			echo $subdf_profile_heading_footer;

			$subdf_profile_heading_content = get_usermeta( $subdf_subdomain->user_id, $subdf_profile_heading );
			if ( $subdf_profile_heading_content == 'subdf_null_value' )
				$subdf_profile_heading_content = '';

			echo $subdf_profile_heading_content_header;
			echo $subdf_profile_heading_content;
			echo $subdf_profile_heading_content_footer;
		}

		echo $subdf_profile_footer;

		exit;
	} elseif ( $subdf_request_uri[1] == $subdf_subdomain->blog_dir && $subdf_subdomain->blog_id != 0 && $subdf_subdomain_target_url != 'BLOG' ) { 
		define( 'WP_CONTENT_URL', 'http://'.$subdf_subdomain->subdomain.'/'.$subdf_subdomain->blog_dir.'/wp-content' );
		define( 'WP_PLUGIN_URL', 'http://'.$subdf_subdomain->subdomain.'/'.$subdf_subdomain->blog_dir.'/wp-content/plugins' );
		define( 'WPMU_PLUGIN_URL', 'http://'.$subdf_subdomain->subdomain.'/'.$subdf_subdomain->blog_dir.'/wp-content/mu-plugins' );
		define( 'ADMIN_COOKIE_PATH', '/'.$subdf_subdomain->blog_dir.'/wp-admin' );
		define( 'PLUGINS_COOKIE_PATH', '/'.$subdf_subdomain->blog_dir.'/wp-content/plugins' );
	} elseif ( $subdf_subdomain->target_url == 'BLOG' ) {
		//do nothing
	} else {
		$_SERVER[ 'REQUEST_URI' ] = preg_replace( '|/|', '', $_SERVER[ 'REQUEST_URI' ], 1 );

		if ( substr( $subdf_subdomain_target_url, -1 ) == '/' ) {
			$subdf_target = $subdf_subdomain_target_url.$_SERVER[ 'REQUEST_URI' ];
		} else {
			if ( $_SERVER[ 'REQUEST_URI' ] == '' ) {
				$subdf_target = $subdf_subdomain_target_url;
			} else {
				$subdf_target = preg_replace( '|'. strrchr( $subdf_subdomain_target_url, '/' ) . '|', '/', $subdf_subdomain_target_url ) . $_SERVER[ 'REQUEST_URI' ];
			}
		}

		?>
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
		<html>
		<head>
		<title><?php echo $subdf_subdomain->title; ?></title>
		<meta name="keywords" content="<?php echo $subdf_subdomain->keywords; ?>">
		<meta name="description" content="<?php echo $subdf_subdomain->description; ?>">
		<meta name="revisit-after" content="<?php echo $subdf_subdomain->revisit; ?>">
		<meta name="robots" content="<?php echo $subdf_subdomain->robots; ?>">
		</head>
		<frameset rows="100%,*" frameborder="NO" border="0" framespacing="0">
		<frame name="main" src="<?php echo $subdf_target; ?>">
		</frameset>
		<noframes>
		<body bgcolor="#FFFFFF" text="#000000">
		<a href="<?php echo $subdf_target; ?>">Click here to continue to <?php echo $subdf_subdomain->title; ?></a>
		</body>
		</noframes>
		</html>
		<?php
		exit;
	}
}

?>
