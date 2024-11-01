<?php
/*
Plugin Name: WordPress MU Subdomain Forwarding
Plugin URI: http://joejacobs.org/software/wordpress-mu-subdomain-forwarding/
Description: Turns a vanilla WordPress MU install into a subdomain forwarding site with options for hosted blogs and/or profiles.
Version: 0.1.2
Author: Joe Jacobs
Author URI: http://joejacobs.org/
*/
/*  Copyright 2009 Joe Jacobs (email : joe@hazardcell.com)

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
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


$wpdb->subdftable = $wpdb->base_prefix . 'subdomain_forwarding';

function subdf_add_pages () {
	global $current_user, $wpdb;
	if ( is_site_admin() ) {
		add_submenu_page('wpmu-admin.php', 'Subdomain Forwarding Settings', 'Subdomain Forwarding', '0', 'subdf_admin', 'subdf_admin_page');
	} else {
		if ( $wpdb->get_var("SELECT profile_status FROM {$wpdb->subdftable} WHERE user_id = '$current_user->ID'") == 1 )
			add_submenu_page('profile.php', 'Public Profile Page', 'Public Profile', '0', 'subdf_profile', 'subdf_profile_page');

		add_submenu_page('index.php', 'Subdomain Settings', 'Subdomain', '0', 'subdf', 'subdf_manage_page');
	}
}

function subdf_header () {
	if ( $_GET[ 'page' ] == 'subdf' ) {?>
	<script type="text/javascript">
	function subdf_switch(id, state) {
		if (state == 1)
		{
			document.getElementById(id).style.display = "block";
			return true;
		}
		else
		{
			document.getElementById(id).style.display = "none";
			return true;
		}
	}

	function subdf_disable(id, state) {
		if (state == 1)
		{
			document.getElementById(id).disabled = true;
			return true;
		}
		else
		{
			document.getElementById(id).disabled = false;
			return true;
		}
	}

	function subdf_value(id, newValue) {
		document.getElementById(id).value = newValue;
		return true;
	}	
	</script>

	<style type="text/css">
		#subdf_spiffy_div{
			width:200px;
			text-align:center;
			float:right;
		}

		.subdf_spiffy{
			display:block;			
		}

		.subdf_spiffy *{
			display:block;
			height:1px;
			overflow:hidden;
			font-size:.01em;
			background:#b20000;
		}

		.subdf_spiffy1{
			margin-left:3px;
			margin-right:3px;
			padding-left:1px;
			padding-right:1px;
			border-left:1px solid #dd9191;
			border-right:1px solid #dd9191;
			background:#c53f3f
		}

		.subdf_spiffy2{
			margin-left:1px;
			margin-right:1px;
			padding-right:1px;
			padding-left:1px;
			border-left:1px solid #f7e5e5;
			border-right:1px solid #f7e5e5;
			background:#c03030
		}

		.subdf_spiffy3{
			margin-left:1px;
			margin-right:1px;
			border-left:1px solid #c03030;
			border-right:1px solid #c03030;
		}

		.subdf_spiffy4{
			border-left:1px solid #dd9191;
			border-right:1px solid #dd9191
		}

		.subdf_spiffy5{
			border-left:1px solid #c53f3f;
			border-right:1px solid #c53f3f
		}

		.subdf_spiffyfg{
			background:#b20000;
			font-size:14px;
		}

		.subdf_spiffyfg a, .subdf_spiffyfg a:hover, .subdf_spiffyfg a:visited{
			text-decoration:none;
			font-weight:bold;
			color:#FFFFFF;
		}
	</style><?php
	} elseif ( $_GET[ 'page' ] == 'subdf_profile' ) {
		wp_admin_css('thickbox');
		wp_print_scripts('jquery-ui-core');
		wp_print_scripts('jquery-ui-tabs');
		wp_print_scripts('post');
		wp_print_scripts('editor');
		add_thickbox();
		wp_print_scripts('media-upload');
		if (function_exists('wp_tiny_mce')) wp_tiny_mce();
	}
}
add_action('admin_menu', 'subdf_add_pages');
add_action('admin_print_scripts', 'subdf_header');

function subdf_admin_page () {
	if( VHOST == 'no' )
		die( 'Sorry, subdomain forwarding only works on virtual host installs.' );

	global $wpdb;

	$subdf_table_check = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->subdftable}'");
	$subdf_option_check = get_site_option( 'subdf_robots_values' );

	if( $subdf_table_check != $wpdb->subdftable || !$subdf_option_check || empty( $subdf_option_check ) ) {
		if ( $subdf_table_check != $wpdb->subdftable ) {
			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->subdftable} (
				`user_id` bigint(20) NOT NULL,
				`subdomain` varchar(255) NOT NULL,
				`target_url` varchar(255) NOT NULL,
				`blog_id` bigint(20) default '0',
				`blog_dir` varchar(255) default 'blog',
				`profile_dir` varchar(255) default 'profile',
				`profile_status` tinyint(4) default '0',
				`title` varchar(255) NOT NULL,
				`keywords` varchar(255),
				`description` varchar(255),
				`revisit` bigint(20) default '14',
				`robots` varchar(255),
				PRIMARY KEY  (`subdomain`)
			);" );

			?> <div id="message" class="updated fade"><p><strong><?php _e('Subdomain forwarding database table created.') ?></strong></p></div> <?php
		}

		if ( !$subdf_option_check || empty( $subdf_option_check ) ) {
			add_site_option('subdf_robots_values', "'INDEX, FOLLOW';'NOINDEX, FOLLOW';'NOINDEX';'INDEX';'FOLLOW';'ALL'");

			?> <div id="message" class="updated fade"><p><strong><?php _e('Subdomain forwarding database options added.') ?></strong></p></div> <?php
		}
	}

	if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'subdf_admin' ) {
		check_admin_referer( 'subdomain_forwarding_admin' );
		update_site_option( 'subdf_profile_header', $_POST[ 'subdf_profile_header' ] );
		update_site_option( 'subdf_profile_footer', $_POST[ 'subdf_profile_footer' ] );
		update_site_option( 'subdf_profile_heading_header', $_POST[ 'subdf_profile_heading_header' ] );
		update_site_option( 'subdf_profile_heading_footer', $_POST[ 'subdf_profile_heading_footer' ] );
		update_site_option( 'subdf_profile_heading_content_header', $_POST[ 'subdf_profile_heading_content_header' ] );
		update_site_option( 'subdf_profile_heading_content_footer', $_POST[ 'subdf_profile_heading_content_footer' ] );
		?> <div id="message" class="updated fade"><p><strong><?php _e('Subdomain forwarding options saved.') ?></strong></p></div> <?php
	}

	if( !defined( 'SUNRISE' ) ) {
		echo "Please uncomment the line <em>//define( 'SUNRISE', 'on' );</em> in " . ABSPATH . "/wp-config.php";
		echo "</div>";
		die();
	}

	if ( !isset( $_POST[ 'subdf_profile_header' ] ) )
		$_POST[ 'subdf_profile_header' ] = get_site_option( 'subdf_profile_header' );
	
	if ( !isset( $_POST[ 'subdf_profile_footer' ] ) )
		$_POST[ 'subdf_profile_footer' ] = get_site_option( 'subdf_profile_footer' );

	if ( !isset( $_POST[ 'subdf_profile_heading_header' ] ) )
		$_POST[ 'subdf_profile_heading_header' ] = get_site_option( 'subdf_profile_heading_header' );

	if ( !isset( $_POST[ 'subdf_profile_heading_footer' ] ) )
		$_POST[ 'subdf_profile_heading_footer' ] = get_site_option( 'subdf_profile_heading_footer' );

	if ( !isset( $_POST[ 'subdf_profile_heading_content_header' ] ) )
		$_POST[ 'subdf_profile_heading_content_header' ] = get_site_option( 'subdf_profile_heading_content_header' );

	if ( !isset( $_POST[ 'subdf_profile_heading_content_footer' ] ) )
		$_POST[ 'subdf_profile_heading_content_footer' ] = get_site_option( 'subdf_profile_heading_content_footer' );

	echo "<div class='wrap'><h2>Subdomain Forwarding Settings</h2>";
	
	echo "<form method='POST' name='subdf_settings_form'>";
	wp_nonce_field( 'subdomain_forwarding_admin' );
	echo "<input type='hidden' name='action' value='subdf_admin'>";
	echo "<table class='form-table'>";

	?><tr valign="top">
	<td colspan='2'>
		<h3>Public Profile Page</h3>
	</td>
	</tr>
	<tr valign="top">
	</tr>
	<tr valign="top">
	<th scope='row'><label for='subdf_profile_header'>Page Header</label></th>
	<td>
		<textarea name='subdf_profile_header' id='subdf_profile_header' rows='5' cols='45' style="width: 95%"><?php echo $_POST[ 'subdf_profile_header' ]; ?></textarea>
	</td>
	</tr>
	<tr valign="top">
	<th scope='row'><label for='subdf_profile_footer'>Page Footer</label></th>
	<td>
		<textarea name='subdf_profile_footer' id='subdf_profile_footer' rows='5' cols='45' style="width: 95%"><?php echo $_POST[ 'subdf_profile_footer' ]; ?></textarea>
	</td>
	</tr><tr valign="top">
	<th scope='row'><label for='subdf_profile_heading_header'>Before Section Title</label></th>
	<td>
		<textarea name='subdf_profile_heading_header' id='subdf_profile_heading_header' rows='5' cols='45' style="width: 95%"><?php echo $_POST[ 'subdf_profile_heading_header' ]; ?></textarea>
	</td>
	</tr><tr valign="top">
	<th scope='row'><label for='subdf_profile_heading_footer'>After Section Title</label></th>
	<td>
		<textarea name='subdf_profile_heading_footer' id='subdf_profile_heading_footer' rows='5' cols='45' style="width: 95%"><?php echo $_POST[ 'subdf_profile_heading_footer' ]; ?></textarea>
	</td>
	</tr><tr valign="top">
	<th scope='row'><label for='subdf_profile_heading_content_header'>Before Section Content</label></th>
	<td>
		<textarea name='subdf_profile_heading_content_header' id='subdf_profile_heading_content_header' rows='5' cols='45' style="width: 95%"><?php echo $_POST[ 'subdf_profile_heading_content_header' ]; ?></textarea>
	</td>
	</tr><tr valign="top">
	<th scope='row'><label for='subdf_profile_heading_content_footer'>After Section Content</label></th>
	<td>
		<textarea name='subdf_profile_heading_content_footer' id='subdf_profile_heading_content_footer' rows='5' cols='45' style="width: 95%"><?php echo $_POST[ 'subdf_profile_heading_content_footer' ]; ?></textarea>
	</td>
	</tr><?php
	

	echo "</table>";
	echo "<input type='submit' value='Save Changes' />";
	echo "</form><br />";
	echo "</div>";
}

function subdf_manage_page () {
	global $wpdb, $current_user, $current_site;	

	if( $_POST[ 'action' ] == 'subdf_process' ) {
		check_admin_referer( 'subdomain_forwarding' );
		
		if ( $_POST[ 'subdf_target_url_radio' ] == 'BLOG' ) {
			$subdf_target_url = BLOG;
			$subdf_blog_dir = '/';

			if ( $_POST[ 'subdf_profile_status' ] == 1 ) {
				$subdf_profile_dir = $wpdb->escape( $_POST[ 'subdf_profile_dir' ] );
				$subdf_profile_status = 1;
			} elseif ( $_POST[ 'subdf_profile_status' ] == 0 ) {
				$subdf_profile_dir = 'profile';
				$subdf_profile_status = 0;
			}
		} elseif ( $_POST[ 'subdf_target_url_radio' ] == 'PROFILE' && $_POST[ 'subdf_profile_status' ] == 1 ) {
			$subdf_target_url = PROFILE;
			$subdf_profile_dir = '/';
			$subdf_profile_status = 1;
			
			if ( $_POST[ 'subdf_blog_dir' ] != '' )
				$subdf_blog_dir = $wpdb->escape( $_POST[ 'subdf_blog_dir' ] );
			else
				$subdf_blog_dir = 'blog';
		} elseif ( $_POST[ 'subdf_target_url_radio' ] == 'EXTERNAL' ) {
			$subdf_target_url = $wpdb->escape( $_POST[ 'subdf_target_url_text' ] );

			if ( !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $subdf_target_url) )
				$errors[] = "The URL you entered is invalid. Please enter a valid URL including http:// at the beginning (eg. http://$current_site->domain/)";

			if ( $_POST[ 'subdf_profile_status' ] == 1 ) {
				$subdf_profile_dir = $wpdb->escape( $_POST[ 'subdf_profile_dir' ] );
				$subdf_profile_status = 1;
			} elseif ( $_POST[ 'subdf_profile_status' ] == 0 ) {
				$subdf_profile_dir = 'profile';
				$subdf_profile_status = 0;
			}

			if ( $_POST[ 'subdf_blog_dir' ] != '' )
				$subdf_blog_dir = $wpdb->escape( $_POST[ 'subdf_blog_dir' ] );
			else
				$subdf_blog_dir = 'blog';
		}

		if ( !preg_match( '|^[a-z0-9-_]*$|', $subdf_blog_dir ) && $subdf_target_url != 'BLOG' )
			$errors[] = 'The blog path you entered is invalid. Please enter a valid blog path. The path can only contain the characters a-z, 0-9, hyphen (-) and underscore (_).';
		if ( !preg_match( '|^[-a-z0-9_]*$|', $subdf_profile_dir ) && $subdf_target_url != 'PROFILE' )
			$errors[] = 'The profile path you entered is invalid. Please enter a valid profile path. The path can only contain the characters a-z, 0-9, hyphen (-) and underscore (_).';

		if ( empty( $subdf_target_url ) || empty( $subdf_profile_dir ) || empty( $subdf_blog_dir ) || empty( $_POST[ 'subdf_title' ] ) ) {
			?> <div id="message" class="updated fade"><p><strong><?php _e('Please ensure fields marked * are not empty.') ?></strong></p></div> <?php
		} elseif ( !empty( $errors ) ) {
			foreach($errors as $error) {
				?> <div id="message" class="updated fade"><p><strong><?php echo $error ?></strong></p></div> <?php
			}
		} else {
			$subdf_current_details = $wpdb->get_row("SELECT blog_id, blog_dir FROM {$wpdb->subdftable} WHERE user_id = '$current_user->ID'");
			
			if ( $subdf_current_details->blog_id != 0 && $subdf_current_details->blog_dir != $subdf_blog_dir )
				$subdf_blogs_query = $wpdb->query("UPDATE {$wpdb->blogs} SET path = '/$subdf_blog_dir/' WHERE blog_id = '$subdf_current_details->blog_id'");
			else
				$subdf_blogs_query = 'no change';

			$subdf_table_query = $wpdb->query("UPDATE {$wpdb->subdftable} SET target_url = '$subdf_target_url', blog_dir = '$subdf_blog_dir', profile_dir = '$subdf_profile_dir', profile_status = '$subdf_profile_status', title='".$wpdb->escape( $_POST[ 'subdf_title' ] )."', keywords='".$wpdb->escape( $_POST[ 'subdf_keywords' ] )."', description='".$wpdb->escape( $_POST[ 'subdf_description' ] )."', revisit='".$wpdb->escape( $_POST[ 'subdf_revisit' ] )."', robots='".$wpdb->escape( $_POST[ 'subdf_robots' ] )."' WHERE user_id = '$current_user->ID'");

			if ($subdf_table_query === FALSE || $subdf_table_query === 0 || $subdf_blogs_query === FALSE || $subdf_blogs_query === 0) {
				?> <div id="message" class="updated fade"><p><strong><?php _e('There has been an error. Please contact the site administrator immediately to report the issue.') ?></strong></p></div> <?php
			} else {
				if ($subdf_current_details->blog_dir != $subdf_blog_dir)
					echo '<script>window.location="http://' . $_SERVER[ 'HTTP_HOST' ] . preg_replace( "|^/$subdf_current_details->blog_dir/|", "/$subdf_blog_dir/", $_SERVER[ 'REQUEST_URI' ] ) . '&subdf_msg=' . urlencode('Your changes have been saved') . '";</script>';	
				else
					?> <div id="message" class="updated fade"><p><strong><?php _e('Your changes have been saved.') ?></strong></p></div> <?php
			}
		}
	}

	if ( isset( $_GET[ 'subdf_msg' ] ) )
		?> <div id="message" class="updated fade"><p><strong><?php _e( urldecode( $_GET[ 'subdf_msg' ] ) ) ?></strong></p></div> <?php

	if ( isset( $_GET[ 'do' ] )  && $_GET[ 'do' ] == 'addblog' ) {
		$subdf_subdomain = $wpdb->get_row( "SELECT blog_id, subdomain, title FROM {$wpdb->subdftable} WHERE user_id = '{$current_user->ID}'" );
		if ( $subdf_subdomain->blog_id == 0 ) {
			$subdf_new_blog_id = wpmu_create_blog( $subdf_subdomain->subdomain, '/', $subdf_subdomain->title, $current_user->ID );
			$wpdb->query( "UPDATE {$wpdb->subdftable} SET blog_id = '$subdf_new_blog_id' WHERE user_id = '$current_user->ID'" );
			$wpdb->query( "UPDATE {$wpdb->blogs} SET path = '/blog/', public = '1', lang_id = '1' WHERE blog_id = '$subdf_new_blog_id'" );
			switch_to_blog( $subdf_new_blog_id );
			$wpdb->query( "UPDATE {$wpdb->options} SET option_value = '1' WHERE option_name = 'blog_public'" );
			restore_current_blog();
			echo "<script>window.location='http://$subdf_subdomain->subdomain/blog" . preg_replace( "|&do=addblog|", "", $_SERVER[ 'REQUEST_URI' ] ) . "&subdf_msg=" . urlencode('Your blog has been created!') . "';</script>";	
		}
	}

	echo "<div class='wrap'><h2>Subdomain Settings</h2>";
	echo "<span class='setting-description'>* denotes a required field</span>";
	
	$subdf_subdomain = $wpdb->get_row( "SELECT blog_id, target_url, blog_dir, profile_dir, profile_status, title, keywords, description, revisit, robots FROM {$wpdb->subdftable} WHERE user_id = '{$current_user->ID}'", 'ARRAY_A' );
	
	echo "<form method='POST' name='subdf_config_form'>";
	wp_nonce_field( 'subdomain_forwarding' );
	echo "<input type='hidden' name='action' value='subdf_process'>";
	echo "<table class='form-table'>";

	if( !empty( $subdf_subdomain ) ) {
		?><tr valign="top">
		<td colspan='2'>
			<h3>Main Settings</h3>
		</td>
		<td rowspan='11'>
			<?php if ( $subdf_subdomain[ 'blog_id' ] == 0 ) { ?><div id="subdf_spiffy_div">
				<b class="subdf_spiffy">
				<b class="subdf_spiffy1"><b></b></b>
				<b class="subdf_spiffy2"><b></b></b>
				<b class="subdf_spiffy3"></b>
				<b class="subdf_spiffy4"></b>
				<b class="subdf_spiffy5"></b></b>

				<div class="subdf_spiffyfg">
					<a href="?page=subdf&amp;do=addblog">Gimme a blog!</a>
				</div>
				<b class="subdf_spiffy">
				<b class="subdf_spiffy5"></b>
				<b class="subdf_spiffy4"></b>
				<b class="subdf_spiffy3"></b>
				<b class="subdf_spiffy2"><b></b></b>
				<b class="subdf_spiffy1"><b></b></b></b>
				<span class="setting-description">Note: You will have to re-login once blog setup is complete.</span>
			</div><?php } ?>
		</td>
		</tr>
		<tr valign="top">
		<th scope='row'><label for='subdf_target_url_text'>Redirect subdomain to*</label></th>
		<td>
			<label <?php if ( $subdf_subdomain['blog_id'] == 0 ) {echo "style='display:none' ";} ?>name='Redirect subdomain to blog'><input name='subdf_target_url_radio' type='radio' value='BLOG' <?php if ( $subdf_subdomain['target_url'] == 'BLOG' ) {echo "checked='checked'";} ?> onclick="subdf_disable('subdf_blog_dir', 1);subdf_disable('subdf_profile_dir', 0);subdf_disable('subdf_target_url_text', 1);subdf_value('subdf_target_url_text', 'BLOG');subdf_value('subdf_blog_dir', '/');subdf_value('subdf_profile_dir', 'profile');" /> Blog</label><div>
			<label id='subdf_target_url_profile' <?php if ( $subdf_subdomain['profile_status'] == 0 ) {echo "style='display:none' ";} ?>name='Redirect subdomain to profile'><input name='subdf_target_url_radio' type='radio' value='PROFILE' <?php if ( $subdf_subdomain['target_url'] == 'PROFILE' ) {echo "checked='checked'";} ?> onclick="subdf_disable('subdf_profile_dir', 1);subdf_disable('subdf_blog_dir', 0);subdf_disable('subdf_target_url_text', 1);subdf_value('subdf_target_url_text', 'PROFILE');subdf_value('subdf_profile_dir', '/');subdf_value('subdf_blog_dir', 'blog');" /> Profile</label><div>
			<label name='Redirect subdomain to external site'><input name='subdf_target_url_radio' type='radio' id='subdf_target_url_external_radio' value='EXTERNAL' <?php if ( $subdf_subdomain['target_url'] != 'PROFILE' && $subdf_subdomain['target_url'] != 'BLOG' ) {echo "checked='checked'";} ?> onclick="subdf_disable('subdf_blog_dir', 0);subdf_disable('subdf_profile_dir', 0);subdf_disable('subdf_target_url_text', 0);subdf_value('subdf_target_url_text', '<?php echo $subdf_subdomain['target_url']; ?>');subdf_value('subdf_blog_dir', 'blog');subdf_value('subdf_profile_dir', 'profile');" /> External Site</label></div>
			<input id='subdf_target_url_text' name='subdf_target_url_text' type='text' value='<?php echo $subdf_subdomain['target_url']; ?>' <?php if ( $subdf_subdomain['target_url'] == 'BLOG' || $subdf_subdomain['target_url'] == 'PROFILE' ) { echo "disabled='true'"; } ?> class='regular-text' /><br />
			<span class="setting-description">This is where we will redirect your subdomain to. If you choose to redirect to an external site,<br />you can still choose to redirect certain paths to your blog and/or profile. (eg. http://<?php echo $current_site->domain; ?>/)</span>
		</td>
		</tr>
		<tr valign="top">
		<th scope='row'><label for='subdf_blog_dir' <?php if ( $subdf_subdomain['blog_id'] == 0 ) {echo "style='display:none'";} ?>>Blog Path*</label></th>
		<td>
			<div <?php if ( $subdf_subdomain['blog_id'] == 0 ) {echo "style='display:none'";} ?>>
				<input id='subdf_blog_dir' name='subdf_blog_dir' type='text' value='<?php echo $subdf_subdomain['blog_dir']; ?>' /><br />
				<span class="setting-description">This is the path that we will redirect to your blog. Can only contain the characters<br /> a-z, 0-9, hyphen (-) and underscore (_). Please note, you will have to login to the system<br /> again once you change this.</span>
			</div></td>
		</tr>
		<tr valign="top">
		<th scope='row'>Profile Page*</th>
		<td>
			<label name='Activate profile page'><input type='radio' name='subdf_profile_status' value='1' <?php if ( $subdf_subdomain['profile_status'] == 1 ) echo "checked='checked'"; ?> onclick="subdf_switch('subdf_profile_dir_text', 1);subdf_switch('subdf_profile_dir_div', 1);subdf_switch('subdf_target_url_profile', 1)" /> On</label>
			<label name='Deactivate profile page'><input type='radio' name='subdf_profile_status' value='0' <?php if ( $subdf_subdomain['profile_status'] == 0 ) echo "checked='checked'"; ?> onclick="subdf_switch('subdf_profile_dir_text', 0);subdf_switch('subdf_profile_dir_div', 0);subdf_switch('subdf_target_url_profile', 0);if(document.getElementById('subdf_target_url_text').value == 'PROFILE'){subdf_value('subdf_target_url_text', '<?php echo $subdf_subdomain['target_url']; ?>');subdf_disable('subdf_target_url_text', 0);document.subdf_config_form.subdf_target_url_external_radio.checked = 'checked';}" /> Off</label><br />
			<span class="setting-description">Switch your profile page on/off</span>
		</td>
		</tr>
		<tr valign="top">
		<th scope='row'><label for='subdf_profile_dir' id='subdf_profile_dir_div' <?php if ( $subdf_subdomain['profile_status'] == 0 ) {echo "style='display:none'";} ?>>Profile Path*</label></th>
		<td>
			<div id='subdf_profile_dir_text' <?php if ( $subdf_subdomain['profile_status'] == 0 ) {echo "style='display:none'";} ?>>
				<input id='subdf_profile_dir' name='subdf_profile_dir' type='text' value='<?php echo $subdf_subdomain['profile_dir']; ?>' <?php if ( $subdf_subdomain['target_url'] == 'PROFILE' ) { ?>disabled='true'<?php } ?> /><br />
				<span class="setting-description">This is the path that we will redirect to your profile. Can only contain the characters<br /> a-z, 0-9, hyphen (-) and underscore (_)</span>
			</div>
		</td>
		</tr>
		<tr valign="top">
		<td colspan='2'><h3>Meta Details</h3>These meta tags will be displayed for redirection to external sites.</td>
		</tr>
		<tr valign="top">
		<th scope='row'><label for='subdf_title'>Title*</label></th>
		<td>
			<input name='subdf_title' type='text' value='<?php echo $subdf_subdomain['title']; ?>' class='regular-text' /><br />
			<span class="setting-description">Normally the name of the site/page. It will be displayed as the page title in the web browser.</span>
		</td>
		</tr>
		<tr valign="top">
		<th scope='row'><label for='subdf_keywords'>Keywords</label></th>
		<td>
			<input name='subdf_keywords' type='text' value='<?php echo $subdf_subdomain['keywords']; ?>' class='regular-text' /><br />
			<span class="setting-description">A collection of words, separated by commas, that captures the essence of a page/site.</span>
		</td>
		</tr>
		<tr valign="top">
		<th scope='row'><label for='subdf_description'>Description</label></th>
		<td>
			<input name='subdf_description' type='text' value='<?php echo $subdf_subdomain['description']; ?>' class='regular-text' /><br />
			<span class="setting-description">A short text describing the page/site.</span>
		</td>
		</tr>
		<tr valign="top">
		<th scope='row'><label for='subdf_revisit'>Revisit</label></th>
		<td>
			<input name='subdf_revisit' type='text' value='<?php echo $subdf_subdomain['revisit']; ?>' /> days<br />
			<span class="setting-description">This is how often you want search engine spiders to revisit the site to record new content.</span>
		</td>
		</tr>
		<tr valign="top">
		<th scope='row'><label for='subdf_robots'>Robots</label></th>
		<td>
			<select name="subdf_robots">
				<?php
			
				$robots = get_site_option('subdf_robots_values');
				$robots_array = explode(';', $robots);

				foreach ($robots_array as $robot) {
					$robot = preg_replace( "|'|", "", $robot );
					?><option value='<?php echo $robot; ?>' <?php if ( $robot == $subdf_subdomain['robots'] ) { ?>selected='selected'<?php } ?>><?php echo $robot; ?></option><?php
				}

				?>					
			</select><br />
			<span class="setting-description">Controls whether search engine spiders are allowed to index a page, or not, and whether<br /> they should follow links from a page, or not.</span>
		</td>
		</tr> <?php
	}

	echo "</table>";
	echo "<input type='submit' value='Save Changes' />";
	echo "</form><br />";
	echo "</div>";
}

function subdf_profile_page () {
	global $current_user;

	if ( !empty( $_POST[ 'action' ] ) ) {
		switch( $_POST[ 'action' ] ) {
			case 'subdf_add_heading':
				check_admin_referer( 'subdf_add_heading' );
				if ( empty( $_POST[ 'subdf_heading' ] ) ) {
					?> <div id="message" class="updated fade"><p><strong><?php _e('The section name cannot be blank.') ?></strong></p></div> <?php
				} elseif ( preg_match( '|[_]|', $_POST[ 'subdf_heading' ] ) ) {
					?> <div id="message" class="updated fade"><p><strong><?php _e('You cannot use the underscore (_) in the section name.') ?></strong></p></div> <?php
				} else {
					$subdf_heading = preg_replace( '|\s|', '_', trim( $_POST[ 'subdf_heading' ] ) );
					$subdf_heading_meta = get_usermeta( $current_user->ID, $subdf_heading );
					if ( empty( $subdf_heading_meta ) ) {
						if ( update_usermeta( $current_user->ID, $subdf_heading, 'subdf_null_value' ) ) {
							$subdf_profile_order = get_usermeta( $current_user->ID, 'subdf_profile_order' );
							$subdf_profile_order[] = $subdf_heading;

							if ( update_usermeta( $current_user->ID, 'subdf_profile_order', $subdf_profile_order ) )
								?> <div id="message" class="updated fade"><p><strong><?php _e('Section successfully added.') ?></strong></p></div> <?php
							else
								?> <div id="message" class="updated fade"><p><strong><?php _e('Error updating order of sections.') ?></strong></p></div> <?php
						} else {
							?> <div id="message" class="updated fade"><p><strong><?php _e('Error adding section.') ?></strong></p></div> <?php
						}
					} else {
						?> <div id="message" class="updated fade"><p><strong><?php _e('A section with that name already exists. Please enter a unique section name.') ?></strong></p></div> <?php
					}
				}
				break;
			case 'subdf_save_content':
				check_admin_referer( 'subdf_save_content' );
				if ( empty( $_POST[ 'subdf_heading_name' ] ) )
					$subdf_heading_name = $_POST[ 'subdf_current_heading' ];
				else
					$subdf_heading_name = $_POST[ 'subdf_heading_name' ];

				if ( empty( $_POST[ 'subdf_heading_content' ] ) )
					$subdf_heading_content = 'subdf_null_value';
				else
					$subdf_heading_content = $_POST[ 'subdf_heading_content' ];

				if ( empty( $_POST[ 'subdf_current_content' ] ) )
					$_POST[ 'subdf_current_content' ] = 'subdf_null_value';

				if ( preg_match( '|[_]|', $subdf_heading_name ) ) {
					?> <div id="message" class="updated fade"><p><strong><?php _e('You cannot use the underscore (_) in the section name.') ?></strong></p></div> <?php
					$_GET[ 'subdf_heading' ] = preg_replace( '|\s|', '_', $_POST[ 'subdf_current_heading' ] );
				} else {
					$subdf_heading_name = preg_replace( '|\s|', '_', $subdf_heading_name );
					$_POST[ 'subdf_current_heading' ] = preg_replace( '|\s|', '_', $_POST[ 'subdf_current_heading' ] );

					if ( $subdf_heading_content != $_POST[ 'subdf_current_content' ] ) {
						if ( $subdf_heading_name != $_POST[ 'subdf_current_heading' ] ) {
							delete_usermeta( $current_user->ID, $_POST[ 'subdf_current_heading' ] );

							$subdf_order = get_usermeta( $current_user->ID, 'subdf_profile_order' );
							$subdf_current_key = array_search( $_POST[ 'subdf_current_heading' ], $subdf_order );
							$subdf_order[ $subdf_current_key ] = $subdf_heading_name;
							update_usermeta( $current_user->ID, 'subdf_profile_order', $subdf_order );
						}

						if ( update_usermeta( $current_user->ID, $subdf_heading_name, $subdf_heading_content ) )
							?> <div id="message" class="updated fade"><p><strong><?php _e('Section successfully updated.') ?></strong></p></div> <?php
						else
							?> <div id="message" class="updated fade"><p><strong><?php _e('Section could not be updated.') ?></strong></p></div> <?php
					} else {
						if ( $subdf_heading_name != $_POST[ 'subdf_current_heading' ] ) {
							delete_usermeta( $current_user->ID, $_POST[ 'subdf_current_heading' ] );

							$subdf_order = get_usermeta( $current_user->ID, 'subdf_profile_order' );
							$subdf_current_key = array_search( $_POST[ 'subdf_current_heading' ], $subdf_order );
							$subdf_order[ $subdf_current_key ] = $subdf_heading_name;
							update_usermeta( $current_user->ID, 'subdf_profile_order', $subdf_order );

							update_usermeta( $current_user->ID, $subdf_heading_name, $subdf_heading_content );
						}

						?> <div id="message" class="updated fade"><p><strong><?php _e('Section successfully updated.') ?></strong></p></div> <?php
					}
				}
				break;
		}
	}

	if ( !empty( $_GET[ 'subdf_heading' ] ) ) {
		$subdf_current_content = get_usermeta( $current_user->ID, urldecode( $_GET[ 'subdf_heading' ] ) );
		if ( $subdf_current_content == "subdf_null_value" )
			$subdf_current_content = "";

		$subdf_current_heading = preg_replace( '|_|', ' ', urldecode( $_GET[ 'subdf_heading' ] ) );

		echo '<form method="POST" name="subdf_profile_edit" action="?page=subdf_profile">';
		wp_nonce_field( 'subdf_save_content' );
		echo '<input type="text" name="subdf_heading_name" id="subdf_heading_name" value="' . $subdf_current_heading . '" /><br />';
		the_editor(  $subdf_current_content, 'subdf_heading_content', 'subdf_heading_name' );
		echo '<input type="hidden" name="subdf_current_heading" id="subdf_current_heading" value="' . $subdf_current_heading . '" />';
		echo '<input type="hidden" name="subdf_current_content" id="subdf_current_content" value="' . $subdf_current_content . '" />';
		echo '<input type="hidden" name="action" value="subdf_save_content" /><br />';
		echo '<input type="submit" value="Save">';
		echo '</form>';
		echo '</div>';
	} else {
		echo '<div class="wrap"><h2>Public Profile Page</h2>';
		echo '<h3>Add Section</h3>';
		echo '<form method="POST" name="subdf_add_profile_heading">';
		echo 'Section Name: <input type="text" name="subdf_heading" />';
		echo '<input type="hidden" name="action" value="subdf_add_heading" />';
		echo '<input type="submit" value="Add Section" />';
		wp_nonce_field( 'subdf_add_heading' );
		echo '</form>';
		echo '<h3>Edit Sections</h3>';

		$subdf_profile_headings = get_usermeta( $current_user->ID, 'subdf_profile_order' );

		if ( !empty( $subdf_profile_headings[0] ) ) {
			foreach ( $subdf_profile_headings as $heading ) {
				echo '<a href="?page=subdf_profile&amp;subdf_heading=' . urlencode( $heading ) . '">' . preg_replace( '|_|', ' ', $heading ) . '</a><br />';
			}
		} else {
			echo 'No page sections yet';
		}
		echo '</div>';
	}
}

function subdf_add_signup ( $subdf_user_id ) {
	global $wpdb, $current_site;

	$subdf_user_login = $wpdb->get_var("SELECT user_login FROM $wpdb->users WHERE ID = '$subdf_user_id'");
	$subdf_subdomain = $subdf_user_login . '.' . $current_site->domain;
	
	$wpdb->query("INSERT INTO $wpdb->subdftable (user_id, subdomain, target_url, profile_status, profile_dir, title) VALUES ('$subdf_user_id', '$subdf_subdomain', 'PROFILE', '1', '/', '$subdf_user_login')");
}

add_action( 'wpmu_activate_user', 'subdf_add_signup', 10, 1 );

function subdf_dashboard_widget () {
	$subdf_widget_contents = "<ul><li><strong>Domain:</strong> %s</li><li><strong>Redirects To:</strong> %s</li><li><strong>Total Hits:</strong> %d</li><li><strong>Last Visit:</strong> %s</li></ul>";

	global $wpdb, $current_user;

	list($subdf_subdomain, $subdf_subdomain_target_url) = $wpdb->get_row( "SELECT subdomain, target_url FROM {$wpdb->subdftable} WHERE user_id = '{$current_user->ID}'", 'ARRAY_N' );
	
	if ( $subdf_subdomain_target_url == 'BLOG' )
		$subdf_subdomain_target_url = get_site_option( 'blogname' );
	elseif ( $subdf_subdomain_target_url == 'PROFILE' )
		$subdf_subdomain_target_url = 'Your Profile';

	$subdf_subdomain_total_hits = 'coming soon';
	$subdf_subdomain_last_visit = 'coming soon';

	printf( $subdf_widget_contents, $subdf_subdomain, $subdf_subdomain_target_url, $subdf_subdomain_total_hits, $subdf_subdomain_last_visit );
}

function subdf_admin_dashboard_widget () {
	$subdf_widget_contents = "<ul><li><strong>Total Users:</strong> %d</li><li><strong>Latest User:</strong> %s</li><li><strong>Unactivated Users:</strong> %d</li></ul>";

	global $wpdb;

	$subdf_users = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->users}");
	$subdf_latest_user = $wpdb->get_var("SELECT subdomain FROM {$wpdb->subdftable} ORDER BY user_id DESC LIMIT 1");
	$subdf_unactivated_users = $wpdb->get_var("SELECT COUNT(user_login) FROM {$wpdb->signups} WHERE active = 0");

	printf( $subdf_widget_contents, $subdf_users, $subdf_latest_user, $subdf_unactivated_users );
}

function subdf_add_dashboard_widget () {
	if ( is_site_admin() ) {
		wp_add_dashboard_widget('subdomain_forwarding_widget', 'Subdomain Forwarding', 'subdf_admin_dashboard_widget');
	} else {
		wp_add_dashboard_widget('subdomain_forwarding_widget', 'Subdomain', 'subdf_dashboard_widget');
	}

	global $wp_meta_boxes;

	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

	$subdf_dashboard_widget_backup = array('subdomain_forwarding_widget' => $normal_dashboard['subdomain_forwarding_widget']);
	unset($normal_dashboard['subdomain_forwarding_widget']);

	$sorted_dashboard = array_merge($subdf_dashboard_widget_backup, $normal_dashboard);

	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
}

add_action('wp_dashboard_setup', 'subdf_add_dashboard_widget' );

/*Borrowed from Donncha's WordPress MU Domain Mapping Plugin
Generates the siteurl, home and fileupload_url settings so we
don't have to make these changes in the database*/
function blog_mapping_siteurl( $setting ) {
	global $wpdb, $current_blog;
	$s = $wpdb->suppress_errors();
	$domain = $wpdb->get_var( "SELECT subdomain FROM {$wpdb->subdftable} WHERE blog_id = '{$wpdb->blogid}'" );
	$wpdb->suppress_errors( $s );
	$protocol = ( 'on' == strtolower($_SERVER['HTTPS']) ) ? 'https://' : 'http://';
	if( $domain )
		return untrailingslashit( $protocol . $domain . $current_blog->path );

	return $setting;
}
add_action( 'pre_option_siteurl', 'blog_mapping_siteurl' );
add_action( 'pre_option_home', 'blog_mapping_siteurl' );
add_action( 'pre_option_fileupload_url', 'blog_mapping_siteurl' );

/*Also borrowed from Donncha's WordPress MU Domain Mapping Plugin
Redirects hits to the mapped blog if any hits are received from
non-mapped URLs.*/
function redirect_to_mapped_blog() {
	global $current_blog;
	$protocol = ( 'on' == strtolower($_SERVER['HTTPS']) ) ? 'https://' : 'http://';
	$url = blog_mapping_siteurl( false );
	if( $url && $url != untrailingslashit( $protocol . $current_blog->domain . $current_blog->path ) ) {
		wp_redirect( $url );
		exit;
	}
}
add_action( 'template_redirect', 'redirect_to_mapped_blog' );

?>
