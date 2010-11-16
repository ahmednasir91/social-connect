<?php
/*
Plugin Name: Social Connect
Plugin URI: http://github.com/ashwinphatak/wp_social_connect
Description: Allows users to register and login using their existing Twitter, Facebook, Google and wordpress.com accounts
Version: 1.0
Author: Ashwin Phatak, Brent Shepherd
Author URI: http://github.com/ashwinphatak
License: GPL2
*/
?>
<?php
require_once(ABSPATH . WPINC . '/registration.php');


function get_user_by_meta($meta_key, $meta_value) {
  global $wpdb;
  $sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s' AND meta_value = '%s'";
  return $wpdb->get_var($wpdb->prepare($sql, $meta_key, $meta_value));
}


function sc_add_stylesheets()
{
  if(!wp_style_is('social_connect', 'registered') ) {
    wp_register_style("social_connect", plugins_url() . "/wp_social_connect/media/css/style.css");
    wp_register_style("jquery-ui", 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.5/themes/smoothness/jquery-ui.css');
  }

  if (did_action('wp_print_styles')) {
		wp_print_styles('social_connect');
    wp_print_styles('jquery-ui');
	} else {
		wp_enqueue_style("social_connect");
    wp_enqueue_style("jquery-ui");
	}
}
add_action('login_head', 'sc_add_stylesheets');

function sc_add_javascripts()
{
  if(!wp_script_is('social_connect', 'registered') ) {
    wp_register_script("social_connect", plugins_url() . "/wp_social_connect/media/js/connect.js");
  }

  // commented out check below as then the JS files are just not emitted, not sure why
//  if (did_action('wp_print_scripts')) {
    wp_print_scripts("jquery");
    wp_print_scripts('jquery-ui-core');
    wp_print_scripts('jquery-ui-dialog');
    wp_print_scripts("social_connect");
//	} else {
//    wp_enqueue_script("jquery");
//    wp_enqueue_script('jquery-ui-core');
//    wp_enqueue_script('jquery-ui-dialog');
//    wp_enqueue_script("social_connect");
//  }
}
add_action('login_head', 'sc_add_javascripts');

function sc_social_connect_admin_menu()
{
  add_options_page('Social Connect Settings', 'Social Connect', 'manage_options', 'social-connect-id', 'sc_render_social_connect_settings');
  add_action( 'admin_init', 'sc_register_social_connect_settings' );
}
add_action('admin_menu', 'sc_social_connect_admin_menu');

function sc_register_social_connect_settings()
{
	register_setting( 'social-connect-settings-group', 'social_connect_facebook_api_key' );
	register_setting( 'social-connect-settings-group', 'social_connect_facebook_secret_key' );
  register_setting( 'social-connect-settings-group', 'social_connect_twitter_enabled');
  register_setting( 'social-connect-settings-group', 'social_connect_twitter_consumer_key');
  register_setting( 'social-connect-settings-group', 'social_connect_twitter_consumer_secret');
}


function sc_render_social_connect_settings()
{
?>
<div class="wrap">
<h2>Social Connect Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'social-connect-settings-group' ); ?>

    <h3>Facebook Settings</h3>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">API Key</th>
        <td><input type="text" name="social_connect_facebook_api_key" value="<?php echo get_option('social_connect_facebook_api_key'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Secret Key</th>
        <td><input type="text" name="social_connect_facebook_secret_key" value="<?php echo get_option('social_connect_facebook_secret_key'); ?>" /></td>
        </tr>
    </table>

    <br/><br/>

    <h3>Twitter Settings</h3>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Enable?</th>
        <td>
          <input type="checkbox" name="social_connect_twitter_enabled" value="1" <?php checked(get_option('social_connect_twitter_enabled'), 1); ?> /><br/>
          Twitter integration requires the generation of dummy email addresses for authenticating users. <br/>
          Please check with your domain administrator as this may require changes to your mail server. <br/><br/>
          When configuring your Twitter application, ensure that the callback URL is <br/>
          <?php echo plugins_url() . '/wp_social_connect/twitter/callback.php'; ?>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row">Consumer Key</th>
        <td><input type="text" name="social_connect_twitter_consumer_key" value="<?php echo get_option('social_connect_twitter_consumer_key'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Consumer Secret</th>
        <td><input type="text" name="social_connect_twitter_consumer_secret" value="<?php echo get_option('social_connect_twitter_consumer_secret'); ?>" /></td>
        </tr>
    </table>

    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div> <?php
}

function sc_render_login_form_social_connect()
{
  $twitter_enabled = get_option('social_connect_twitter_enabled');

?>

<p>
  <a href="javascript://" class="social_connect_login">Social Connect</a>
</p>
<br/>
<div class="social_connect_form" title="Social Connect">
  <a href="#" class="social_connect_login_facebook">Facebook</a> <br/>
  <?php if($twitter_enabled) echo '<a href="#" class="social_connect_login_twitter">Twitter</a> <br/>'; ?>
  <a href="#" class="social_connect_login_google">Google</a> <br/>
  <a href="#" class="social_connect_login_wordpress">WordPress</a> <br/>
</div>

<?php 
$social_connect_provider = isset($_COOKIE['social_connect_current_provider']) ? $_COOKIE['social_connect_current_provider'] : '';
$social_connect_user_name = isset($_COOKIE['social_connect_current_name']) ? $_COOKIE['social_connect_current_name'] : '';
$social_connect_wordpress_blog_url = isset($_COOKIE['social_connect_wordpress_blog_url']) ? $_COOKIE['social_connect_wordpress_blog_url'] : '';

if($social_connect_provider) {
?>
<div class="social_connect_already_connected_form" title="Social Connect">
  Welcome back <?php echo $social_connect_user_name ?>, <a href="#" class="social_connect_login_<?php echo $social_connect_provider ?>">continue?</a> <br/><br/>
  <a href="#" class="social_connect_already_connected_form_not_you">Not you?</a> <br/><br/>
  <a href="#" class="social_connect_already_connected_user_another">Use another account</a> <br/>
</div>
<?php
}
?>

<div class="social_connect_facebook_auth" client_id="<?php echo get_option('social_connect_facebook_api_key'); ?>" redirect_uri="<?php 
  echo urlencode(plugins_url() . '/wp_social_connect/facebook/callback.php'); ?>">
</div>

<div class="social_connect_twitter_auth" redirect_uri="<?php echo(plugins_url() . '/wp_social_connect/twitter/connect.php'); ?>">
</div>

<div class="social_connect_google_auth" redirect_uri="<?php echo(plugins_url() . '/wp_social_connect/google/connect.php'); ?>">
</div>

<div class="social_connect_wordpress_auth" redirect_uri="<?php echo(plugins_url() . '/wp_social_connect/wordpress/connect.php'); ?>">
</div>

<div class="social_connect_wordpress_form" title="WordPress">
  <p>Enter your WordPress.com blog URL</p><br/>
  <p><input class="wordpress_blog_url" value="<?php echo $social_connect_wordpress_blog_url ?>"/> &nbsp; <a href="#" class="social_connect_wordpress_proceed">Proceed</a></p>
</div>

<?php
}
add_filter('login_form', 'sc_render_login_form_social_connect');



function sc_social_connect_process_login()
{
  $social_connect_provider = $_REQUEST['social_connect_provider'];
  $sc_provider_identity_key = 'social_connect_' . $social_connect_provider . '_id';
  
  switch($social_connect_provider) {
    case 'facebook':
      $fb_json = json_decode(file_get_contents("https://graph.facebook.com/me?access_token=" . $_REQUEST['social_connect_access_token']));
      $sc_provider_identity = $fb_json->{'id'};
      $sc_email = $fb_json->{'email'};
      $sc_first_name = $fb_json->{'first_name'};
      $sc_last_name = $fb_json->{'last_name'};
      $sc_profile_url = $fb_json->{'link'};
      $sc_name = $sc_first_name . ' ' . $sc_last_name;
    break;

    case 'twitter':
      $sc_provider_identity = $_REQUEST['social_connect_twitter_identity'];
      $sc_name = $_REQUEST['social_connect_name'];
      $names = explode(" ", $sc_name);
      $sc_first_name = $names[0];
      $sc_last_name = $names[1];

      $sc_screen_name = $_REQUEST['social_connect_screen_name'];
      $sc_profile_url = '';

      // get host name from URL http://in.php.net/preg_match
      preg_match("/^(http:\/\/)?([^\/]+)/i", site_url(), $matches);
      $host = $matches[2];
      preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
      $domain_name = $matches[0];

      $sc_email = 'tw_' . md5($sc_provider_identity) . '@' . $domain_name;

    break;
    
    case 'google':
      $sc_provider_identity = $_REQUEST['social_connect_openid_identity'];
      $sc_email = $_REQUEST['social_connect_email'];
      $sc_first_name = $_REQUEST['social_connect_first_name'];
      $sc_last_name = $_REQUEST['social_connect_last_name'];
      $sc_profile_url = '';
      $sc_name = $sc_first_name . ' ' . $sc_last_name;
    break;

    case 'wordpress':
      $sc_provider_identity = $_REQUEST['social_connect_openid_identity'];
      $sc_email = $_REQUEST['social_connect_email'];
      $sc_name = $_REQUEST['social_connect_name'];
      $sc_profile_url = '';
      if(trim($sc_name) == '') {
        $names = explode("@", $sc_email);
        $sc_name = $names[0];
        $sc_first_name = $sc_name;
        $sc_last_name = '';
      } else {
        $names = explode(" ", $sc_name);
        $sc_first_name = $names[0];
        $sc_last_name = $names[1];
      }

      setcookie("social_connect_wordpress_blog_url", $sc_provider_identity, time()+3600, SITECOOKIEPATH, COOKIE_DOMAIN, false, true);

    break;
  }


  // cookies used to display welcome message if already signed in recently using some provider
  setcookie("social_connect_current_provider", $social_connect_provider, time()+3600, SITECOOKIEPATH, COOKIE_DOMAIN, false, true);
  setcookie("social_connect_current_name", $sc_name, time()+3600, SITECOOKIEPATH, COOKIE_DOMAIN, false, true);
  
	if ( isset( $_REQUEST['redirect_to'] ) ) {
		$redirect_to = $_REQUEST['redirect_to'];
		// Redirect to https if user wants ssl
		if ( $secure_cookie && false !== strpos($redirect_to, 'wp-admin') )
			$redirect_to = preg_replace('|^http://|', 'https://', $redirect_to);
	} else {
		$redirect_to = admin_url();
	}
  
  // get user by meta
  $user_id = get_user_by_meta($sc_provider_identity_key, $sc_provider_identity);
  if($user_id) {
    // user already exists, just log him in
    wp_set_auth_cookie($user_id);
    wp_safe_redirect($redirect_to);
    exit();
  }
  
  // user not found by provider identity, check by email
  if(($user_id = email_exists($sc_email))) {
    // user already exists, associate with provider identity
    update_user_meta($user_id, $sc_provider_identity_key, $sc_provider_identity);
    
    // user signed in with provider identity after normal WP signup. Since email is verified, sign him in
    wp_set_auth_cookie($user_id);
    wp_safe_redirect($redirect_to);
    exit();
    
  } else {
    // create new user and associate provider identity
    $user_login = strtolower($sc_first_name.$sc_last_name);
    if(username_exists($user_login)) {
      $user_login = strtolower("sc_". md5($sc_provider_identity));
    }
    
    $userdata = array('user_login' => $user_login, 'user_email' => $sc_email, 'first_name' => $sc_first_name, 'last_name' => $sc_last_name,
      'user_url' => $sc_profile_url, 'user_pass' => wp_generate_password());
    
    // create a new user
    $user_id = wp_insert_user($userdata);
    
    if($user_id && is_integer($user_id)) {
      update_user_meta($user_id, $sc_provider_identity_key, $sc_provider_identity);
    
      wp_set_auth_cookie($user_id);
      wp_safe_redirect($redirect_to);
      exit();
    }
  }
}

add_action('login_form_social_connect', 'sc_social_connect_process_login');

?>