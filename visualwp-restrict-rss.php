<?php
/*
Plugin Name: VisualWP Restrict RSS Feeds
Plugin URI: https://sightfactory.com/wordpress-plugins/restrict-rss-feeds-for-wordpress
Description:  A quick and easy solution to automatically disable all RSS feeds in your website or password protect your RSS feed to protect gated content.
Version: 1.0.2
Author: Sightfactory
Author URI: https://sightfactory.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

function vwprss_rss_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=visualwp-restrict-rss">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'vwprss_rss_add_settings_link' );

function visualwp_rss_disable_feed() {
	$vwprss_rss_status = get_option('vwprss_rss_status');
	
	if($vwprss_rss_status != 1) {	
			wp_die( __( 'No feed is available, please visit the <a href="'. esc_url( home_url( '/' ) ) .'">website homepage</a>.' ) );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
			remove_action( 'wp_head', 'feed_links', 2 );
		
	}
	$vwprss_rss_setpassword = get_option('vwprss_rss_setpassword');
	if($vwprss_rss_setpassword == 1) {	
		if(!isset($_GET['accesskey'])) {		
			wp_die( __( 'No feed is available, please visit the <a href="'. esc_url( home_url( '/' ) ) .'">website homepage</a>.' ) );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
			remove_action( 'wp_head', 'feed_links', 2 );
		}
	}
	
	if(isset($_GET['accesskey'])) {
		$vwprss_rss_pass_value = get_option('vwprss_rss_password');
		if($_GET['accesskey'] != $vwprss_rss_pass_value) {
			wp_die( __( 'This RSS feed is restricted, please visit the <a href="'. esc_url( home_url( '/' ) ) .'">website homepage</a>.' ) );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
			remove_action( 'wp_head', 'feed_links', 2 );
		}
	}

	
   }
   
/*
delete_option('vwprss_rss_password');
delete_option('vwprss_rss_status');
delete_option('vwprss_rss_setpassword');
delete_option('vwprss_rss_titleonly');
delete_option('vwprss_rss_removeauthornames');
*/   
   
add_action('do_feed', 'visualwp_rss_disable_feed', 1);
add_action('do_feed_rdf', 'visualwp_rss_disable_feed', 1);
add_action('do_feed_rss', 'visualwp_rss_disable_feed', 1);
add_action('do_feed_rss2', 'visualwp_rss_disable_feed', 1);
add_action('do_feed_atom', 'visualwp_rss_disable_feed', 1);
add_action('do_feed_rss2_comments', 'visualwp_rss_disable_feed', 1);
add_action('do_feed_atom_comments', 'visualwp_rss_disable_feed', 1);

$vwprss_rss_removeauthor = get_option('vwprss_rss_removeauthornames');

$vwprss_rss_titleonly = get_option('vwprss_rss_titleonly');

if($vwprss_rss_titleonly == 1) {
	add_filter( 'the_content_feed', '__return_empty_string' );
	add_filter( 'the_excerpt_rss', '__return_empty_string' );
}
if($vwprss_rss_removeauthor == 1) { 
	add_filter( 'the_author', 'vwprss_feed_author' );
}
function vwprss_feed_author($name) {
  if( is_feed() ) {
    global $post;
	$author = NULL;//get_bloginfo( 'name' );
    return esc_html($author);  
  }
}

/*Admin RSS*/
add_action( 'init', 'vwprss_init_rss_scripts' );



function vwprss_init_rss_scripts() {
	
	if(is_admin()) {
		wp_enqueue_style('vwprss-admin-css', plugins_url('/admin/css/vwp.css' , __FILE__), array(),'1.0');
		//wp_enqueue_style('vwprss-css', plugins_url('/public/css/vwp-rss.css' , __FILE__), array(),'1.0.2');
	}

}
// validation to check if current user has apropriate permissions
if( ! function_exists( 'vwprss_current_user_has_role' ) ){
    function vwprss_current_user_has_role( $role ) {

        $user = get_userdata( get_current_user_id() );
        if( ! $user || ! $user->roles ){
            return false;
        }

        if( is_array( $role ) ){
            return array_intersect( $role, (array) $user->roles ) ? true : false;
        }

        return in_array( $role, (array) $user->roles );
    }
}

// quick validation check before saving settings
function is_one_or_zero( $value ) {
	// Check if the value is numeric.
	if ( ! is_numeric( $value ) ) {
		return false;
	}

	// Convert the value to an integer.
	$value = intval( $value );

	// Check if the value is 1 or 0.
	if ( $value === 1 || $value === 0 ) {
		return true;
	} else {
		return false;
	}
}


/*Create Settings Page*/

add_action( 'admin_menu', 'vwprss_menu' );

function vwprss_menu() {
	add_options_page( 'Restrict RSS Feed', 'Restrict RSS Feed', 'manage_options', 'visualwp-restrict-rss', 'vwprss_menu_options' );
		
}

function vwprss_menu_options() {
	$update_notice = '';
	$plugin_title = get_admin_page_title();
    $plugin_page = "RSS Options";
	$user = get_userdata( get_current_user_id() );
    if( ! $user || ! $user->roles ){
        return false;
    }
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	else {
		//print_r($_POST);
		if (isset($_POST['vwprss_rss_password']) && vwprss_current_user_has_role( 'administrator' ) )  {	
			check_admin_referer( 'vwprss_option_page_action' );
			
			update_option('vwprss_rss_password', sanitize_text_field($_POST['vwprss_rss_password']));
			if(!isset($_POST['vwprss_rss_setpassword'])){
				$_POST['vwprss_rss_setpassword'] = 0;
			}		
			if(isset($_POST['vwprss_rss_setpassword']) && is_one_or_zero($_POST['vwprss_rss_setpassword'])) {
				update_option('vwprss_rss_setpassword', sanitize_text_field($_POST['vwprss_rss_setpassword']));
			}
			if(!isset($_POST['vwprss_rss_status'])){
				$_POST['vwprss_rss_status'] = 0;
			}			
			if(isset($_POST['vwprss_rss_status']) && is_one_or_zero($_POST['vwprss_rss_status']) ) {				
				update_option('vwprss_rss_status', sanitize_text_field($_POST['vwprss_rss_status']));
			}
			if(!isset($_POST['vwprss_rss_titleonly'])){
				$_POST['vwprss_rss_titleonly'] = 0;
			}
			if(isset($_POST['vwprss_rss_titleonly']) && is_one_or_zero($_POST['vwprss_rss_titleonly'])) {
				update_option('vwprss_rss_titleonly', sanitize_text_field($_POST['vwprss_rss_titleonly']));
			}
			if(!isset($_POST['vwprss_rss_removeauthornames'])){
				$_POST['vwprss_rss_removeauthornames'] = 0;
			}
			if(isset($_POST['vwprss_rss_removeauthornames']) && is_one_or_zero($_POST['vwprss_rss_removeauthornames'])) {				
				update_option('vwprss_rss_removeauthornames', sanitize_text_field($_POST['vwprss_rss_removeauthornames']));
			}
			
			$update_notice = 'Settings Updated';
			
			
		} 
		
	
		@$vwprss_rss_pass_value = get_option('vwprss_rss_password');
		@$vwprss_rss_status = get_option('vwprss_rss_status');
		@$vwprss_rss_setpassword = get_option('vwprss_rss_setpassword');
		@$vwprss_rss_titleonly = get_option('vwprss_rss_titleonly');
		@$vwprss_rss_removeauthornames = get_option('vwprss_rss_removeauthornames');
		
		
		
		
		
		
	?>
	<div class="wrap">
	
	<div class="vwprss-plugin-header">
	<a href="https://www.sightfactory.com/wordpress-plugins/visual-wp" target="_blank">
	<img align="center" style="width:25px;margin-top:-5px" src="<?php echo plugins_url('/admin/images/vwp-logo.png' , __FILE__)?>"/> <span class="vwp-plugin-creator">VisualWP</span></a><?php esc_html_e($plugin_title) ?>
	
	<a href="https://www.buymeacoffee.com/XCiNWATgR" target="_blank"><img src="<?php echo plugins_url('/admin/images/donate.png' , __FILE__)?>" alt="Buy Me A Coffee" style="float:right;margin-top:-8px;height: 40px !important;" ></a>

	<img align="center" style="float:right;width:55px;margin-top:-15px" src="<?php echo plugins_url('/admin/images/vwp-restrict-rss.png' , __FILE__)?>"/>
	</div>
	<div class="vwprss-plugin-body">
		
		<?php 
		echo sprintf("<span class='vwprss-notice'>%s</span>",esc_html($update_notice));
		$update_notice = '';
		?>
		
		<form method="POST">
		<div>
		<label for="vwprss_rss_password"><p>RSS Password</p></label>
		
		<input type="password" name="vwprss_rss_password" id="rss_site_key" size="40" maxlength="40" value="<?php echo esc_html(@$vwprss_rss_pass_value); ?>" autocomplete="off"/>
		<p>The RSS Feed is disabled by default,however you can restrict access to your feed by entering a secret key above and turning on the <b>Enable RSS Feed</b> & <b>Require Passowrd</b> options below.</p>
		<p>Caching may occur with the RSS feed, so some changes may take some time to show up. Try updating or creating a new post or do a hard refresh to pull the latest version of your feed</p>
		<?php 
		if(isset($vwprss_rss_setpassword) && $vwprss_rss_setpassword == 1) {
			echo sprintf("The RSS secret key is now <b>active</b>. Your RSS feed can be accessed at the following url.<br/><a target='_blank' href='%s/feed/?accesskey=%s'>%s/feed/?accesskey=%s</a>"
				,esc_html(get_site_url()),esc_html($vwprss_rss_pass_value),esc_html(get_site_url()),esc_html($vwprss_rss_pass_value));
		}
		
		?>
		</div>
		
		<?php wp_nonce_field( 'vwprss_option_page_action' ); ?>
		<p><input type="checkbox" value="1" name="vwprss_rss_status" class="vwppd-ui-toggle"
		<?php 
		if(isset($vwprss_rss_status) && $vwprss_rss_status == 1) { 
			esc_html_e('checked');
		}
		?>/>
		Enable RSS Feed</p>
		
		<p><input type="checkbox" value="1" name="vwprss_rss_setpassword" class="vwppd-ui-toggle"
		<?php 
		if(isset($vwprss_rss_setpassword) && $vwprss_rss_setpassword == 1) { 
			esc_html_e('checked');
		}
		?>/>
		Require Password to View Feed</p>
		
		<p><input type="checkbox" value="1" name="vwprss_rss_titleonly" class="vwppd-ui-toggle"
		<?php 
		if(isset($vwprss_rss_titleonly) && $vwprss_rss_titleonly == 1) { 
			esc_html_e('checked');
		}
		?>/>
		Show Titles Only In RSS Feed</p>
		<p><input type="checkbox" value="1" name="vwprss_rss_removeauthornames" class="vwppd-ui-toggle"
		<?php 
		if(isset($vwprss_rss_removeauthornames) && $vwprss_rss_removeauthornames == 1) { 
			esc_html_e('checked');
		}
		?>/>
		Remove Author Names From Feed</p>
		<br>
		<input type="submit" value="Save Changes" class="button button-primary button-large">
		
		</form>
		
		<p><a href="https://www.sightfactory.com/wordpress-plugins/visualwp-restrict-rss" target="_blank">Need help? View Documentation</a></p>
	
		
		
	</div>
	</div>
	<?php
	}
	
	
	
}

?>
