<?php

/**
 * Plugin Name: Live Chat for WordPress plugin - Live Chat, Sales & Marketing by Atlasmic
 * Description: Live chat that helps you increase sales. Everything you need to run a modern business on Wordpress. Boost your sales - live chat with your visitors.
 * Version: 1.1.2
 * Author: Atlasmic
 * Author URI: https://atlasmic.com
 * License: GPLv3
 * Text Domain: atlasmic
*/

include(plugin_dir_path(__FILE__) . '/config.php');

add_action('admin_menu', 'atlasmic_create_menu');

function atlasmic_create_menu() {
  add_menu_page(
    'Atlasmic - Settings',
    'Atlasmic',
    'manage_options',
    'atlasmic',
    'atlasmic_settings',
    plugin_dir_url(__FILE__) . "/assets/atlasmic.svg",
    3
  );
};

function atlasmic_installation_script(
  $atlasmic_args_installation_script_src,
  $atlasmic_write_key) {

  return <<<SRC
<script type="text/javascript">
  (function() {
    window.atlasmic = window.atlasmic || function() { window.atlasmic.actions.push(arguments); };
    window.atlasmic.actions = window.atlasmic.actions || [];
    window.atlasmic.WRITE_KEY = "$atlasmic_write_key";
    window.atlasmic.VERSION = "1.0.0";

    const d = document;
    const e = d.createElement("script");
    e.src = "$atlasmic_args_installation_script_src";
    e.async = 1;
    d.getElementsByTagName("head")[0].appendChild(e);
  })();
</script>
SRC;
}

function atlasmic_settings() {
  ?><div id='atlasmic-settings'></div><?php

  global $atlasmic_help_installation_script_src;
  global $atlasmic_help_write_key;

  echo atlasmic_installation_script($atlasmic_help_installation_script_src, $atlasmic_help_write_key);
}

function atlasmic_get_settings($request) {
  $atlasmic_selected_workspace_write_key = get_option('atlasmic_selected_workspace_write_key');

  return array(
    'selectedWorkspaceWriteKey' => $atlasmic_selected_workspace_write_key ? $atlasmic_selected_workspace_write_key : null,
    'adminEmail' => get_bloginfo('admin_email'),
    'homeUrl' => get_home_url(),
    'blogname' => get_bloginfo('name'),
  );
}

function atlasmic_update_settings($request) {
  $json = $request->get_json_params();

  $atlasmic_selected_workspace_write_key = $json['selectedWorkspaceWriteKey'];
  update_option('atlasmic_selected_workspace_write_key', $atlasmic_selected_workspace_write_key);

  return array(
    'selectedWorkspaceWriteKey' => $atlasmic_selected_workspace_write_key,
  );
}

function atlasmic_settings_permissions_check() {
  if (current_user_can('manage_options')) {
    return true;
  }

  return new WP_Error('rest_forbidden', esc_html__('You do not have permissions to view this data.', 'atlasmic'), array('status' => 401));;
}

add_action('rest_api_init', function () {
  register_rest_route('atlasmic', '/settings', array(
    'methods'  => WP_REST_Server::READABLE,
    'callback' => 'atlasmic_get_settings',
    'permission_callback' => 'atlasmic_settings_permissions_check'
  ));
  register_rest_route('atlasmic', '/settings', array(
    'methods'  => WP_REST_Server::CREATABLE,
    'callback' => 'atlasmic_update_settings',
    'permission_callback' => 'atlasmic_settings_permissions_check'
  ));
});

add_action('admin_enqueue_scripts', function ($hook) {
  global $atlasmic_wordpress_script_src;

  wp_enqueue_script('atlasmic-translations', plugins_url('/translations.js', __FILE__), array('wp-i18n'), mt_rand(10,1000), true);
  wp_set_script_translations('atlasmic-translations', 'atlasmic');
  wp_enqueue_script('atlasmic', $atlasmic_wordpress_script_src, '', mt_rand(10,1000), true);
  wp_localize_script('atlasmic', 'atlasmicConfig', array(
    'settingsUrl' => rest_url('atlasmic/settings'),
    'nonce' => wp_create_nonce('wp_rest'),
  ));
});

add_action('wp_footer', function() {
  $atlasmic_selected_workspace_write_key = get_option('atlasmic_selected_workspace_write_key');
  if (!$atlasmic_selected_workspace_write_key) return;

  global $atlasmic_installation_script_src;

  echo atlasmic_installation_script($atlasmic_installation_script_src, $atlasmic_selected_workspace_write_key);
});

function atlasmic_uninstall() {
  delete_option('atlasmic_selected_workspace_write_key');
}

register_uninstall_hook(__FILE__, 'atlasmic_uninstall');
