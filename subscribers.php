<?php
/*
Plugin Name: Subscribers.com
Plugin URI: https://subscribers.com/
Description: Subscribers.com lets you send push notifications from your desktop or mobile website to your users.
Simply enable the plugin and start collecting subscribers for your Subscribers account.
Visit <a href="https://subscribers.com/">Subscribers</a> for more details.
Author: Subscribers.com
Version: 1.5.4
screenshot.png
subscribers.php
Author URI: https://subscribers.com

This relies on the actions being present in the themes header.php and footer.php
* header.php code before the closing </head> tag
*   wp_head();
*
*/

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//

require_once("config.php");

$subscribers_embed_script = <<<HTML
<!-- Start Subscriber Embed Code -->
<script type="text/javascript">
var subscribersSiteId = 'SUBSCRIBER_ID';
var subscribersServiceWorkerPath = '/?firebase-messaging-sw';
</script>
<script type="text/javascript" src="https://$subscribers_cdn_host/assets/subscribers.js"></script>
<!-- End Subscriber Embed Code -->
HTML;

//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//
add_action( 'wp_footer', 'subscribers_headercode', 1 );
add_action( 'admin_menu', 'subscribers_plugin_menu' );
add_action( 'admin_init', 'subscribers_register_mysettings' );
add_action( 'admin_notices', 'subscribers_warn_nosettings' );

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//
// options page link
function subscribers_plugin_menu() {
  add_options_page('Subscribers', 'Subscribers', 'create_users', 'subscribers_options', 'subscribers_plugin_options');
}

// whitelist settings
function subscribers_register_mysettings(){
  register_setting('subscribers_options','subscribers_hash');
  register_setting('subscribers_options','subscribers_lang');
}

//------------------------------------------------------------------------//
//---Add "Settings" link to plugin Page-----------------------------------//
//------------------------------------------------------------------------//
function subscribers_plugin_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=subscribers_options">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'subscribers_plugin_settings_link' );

//------------------------------------------------------------------------//
//---Redirect to Settings page after activation---------------------------//
//------------------------------------------------------------------------//
register_activation_hook(__FILE__, 'subscribers_plugin_activate');
add_action('admin_init', 'subscribers_plugin_redirect');

function subscribers_plugin_activate() {
    add_option('subscribers_plugin_do_activation_redirect', true);
}

function subscribers_plugin_redirect() {
    if (get_option('subscribers_plugin_do_activation_redirect', false)) {
        delete_option('subscribers_plugin_do_activation_redirect');
        // Do not redirect if activated as part of a bulk activate
        if(!isset($_GET['activate-multi'])) {
          wp_redirect("options-general.php?page=subscribers_options");
          // wp_redirect() does not exit automatically and should almost always be followed by exit.
          exit;
        }
    }
}

//------------------------------------------------------------------------//
//---Serving service worker-----------------------------------------------//
//------------------------------------------------------------------------//
add_action( 'parse_request', 'subscribers_service_worker' );
add_filter( 'query_vars', 'subscribers_query_vars' );

function subscribers_query_vars($vars) {
  $vars[] = 'firebase-messaging-sw';
  return $vars;
}

// Served at `/?firebase-messaging-sw`. Needs to be at the top level in order
// to be registered at the correct scope.
function subscribers_service_worker($query) {
  if ( isset( $query->query_vars['firebase-messaging-sw'] ) ) {
    header( 'Content-Type: application/javascript' );
    include( 'firebase-messaging-sw.js.php' );
    exit;
  }
}

//------------------------------------------------------------------------//
//---Output Functions-----------------------------------------------------//
//------------------------------------------------------------------------//
function subscribers_headercode(){
  // runs in the header
  global $subscribers_embed_script;
  $subscribers_hash = swpush_sanitize(get_option('subscribers_hash'));

  if($subscribers_hash){
      echo str_replace('SUBSCRIBER_ID', $subscribers_hash, $subscribers_embed_script); // only output if options were saved
  }
}
//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//
// options page
function subscribers_plugin_options() {
  ?>
<div class="wrap">
  <h2>Subscribers - Free Web Push Notifications</h2>
  <p>
    The Subscribers plugin will insert the necessary code into your Wordpress site just before the closing
    <code>&lt;/body&gt;</code> tag. This will allow the subscribe popup and chicklet to be placed on your page so your
    visitors can subscribe.
  </p>
  <p>
    In order to use this plugin, you need to have a Subscribers account. Please
    <a target="_blank" href="https://app.subscribers.com/users/signup">sign up here</a> if you do not already have one.
  </p>
  <p>
    Once you sign in to your Subscribers account please visit <a target="_blank" href="https://app.subscribersdemo.com/settings/edit">this page</a> and copy that <b>Site ID</b> and paste it into the field
    below and click Save changes.
  </p>

  <img src="<?php echo plugins_url('/images/site-id-screenshot.png', __FILE__); ?>" alt="Site ID screenshot" style="max-width: 50%;" />

  <form method="post" action="options.php">
    <?php settings_fields( 'subscribers_options' ); ?>
    <?php do_settings_sections( 'subscribers_options' ); ?>

    <label for="subscribers_hash" style="font-size: 120%; display: block; margin-top: 2em;">Your Subscriber Site ID: *Mandatory</label>
    <input type="text" id="subscribers_hash" name="subscribers_hash" value="<?php echo swpush_sanitize(get_option('subscribers_hash')); ?>" style="margin-top: 0.5em;" size="50"/>

    <label for="subscribers_lang" style="font-size: 120%; display: block; margin-top: 2em;">Your Site's primary Language code (Refer - https://datahub.io/core/language-codes/r/0.html) *Optional:</label>
    <input type="text" id="subscribers_lang" name="subscribers_lang" value="<?php echo swpush_sanitize(get_option('subscribers_lang')); ?>" style="margin-top: 0.5em;" size="50"/>

    <?php submit_button(); ?>
  </form>

  <p>
    After you save your Site ID above, go to the homepage of your website, right click somewhere on the page and click
    on "View page source." A new tab will open with the code for your site.
  </p>

  <p>
    Next press CTRL+F (or Command+F on macOS) on your keyboard and type in "Subscriber" and if you see something like
    this, then you know the code has been added properly. This can take a few minutes to show up. If you have a caching
    plugin installed, you might need to clear your cache.
  </p>

  <img src="<?php echo plugins_url('/images/source-code-success-1x.png', __FILE__); ?>" alt="Example code" style="max-width: 100%;" />

  <h3>What are web push notifications?</h3>

  <img src="<?php echo plugins_url('/images/pc_with_notifications-1x.png', __FILE__); ?>" alt="PC with notifications" style="max-width: 100%;" />

  <p>
    Web push notifications are the digital marketing world&rsquo;s newest best friend. They allow you to send instant
    notifications, similar to those found on your smartphone to your subscribers&rsquo; devices.
  </p>
  <p>
    This is an ideal solution for marketers looking for a new and very effective channel to reach your audience about
    news, sales, order status, special offers, empty shopping cart links, new content, events or anything else you can
    think of.
  </p>
  <p>
    Conversion rates, opt-in rates, and click-through rates are all returning much higher results than traditional email
    marketing. Don&rsquo;t wait and get started today, it&rsquo;s super easy to get it installed and running on your site.
  </p>
  <p>
    To enable it for your Wordpress site, <a target="_blank" href="https://app.subscribers.com/users/signup">sign up for free here.</a> And
    don&rsquo;t hesitate to reach out directly to us at <a href="mailto:support@subscribers.com">support@subscribers.com</a>
  </p>
</div>

<?php
}

function swpush_sanitize($input, $encoding='UTF-8'){
  return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, $encoding);
}

function subscribers_warn_nosettings(){
    if (!is_admin())
        return;

  $subscribers_option = get_option("subscribers_hash");
  if (!$subscribers_option){
    echo '<div class="updated fade"><p><strong>Subscribers is almost ready.</strong> You must <a href="options-general.php?page=subscribers_options">enter your Site ID</a> for it to work.</p></div>';
  }
}
