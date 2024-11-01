<?php
/** 
Plugin Name: Social Footprint
Description: Displays social sharing stats by platform for any set of URLs.
Version: 1.0
Author: Matthew Barby
Author URI: http://matthewbarby.com/social-footprint-plugin/
License: GPL2
*/

include dirname(__FILE__) . '/includes/PagesSocialStatsPlugin.php';

$instance = PagesSocialStatsPlugin::get_instance();

// $plugin_data = get_plugin_data(__FILE__);
// $plugin_data['Name'];

$instance->init();

add_action('admin_menu', array($instance, 'hook_admin_menu'));

register_activation_hook(__FILE__, array($instance, 'hook_activate'));
register_deactivation_hook(__FILE__, array($instance, 'hook_deactivate'));
