<?php
/**
 * Plugin Name: Bangkok Guru Bookings
 * Description: Independent travel inventory, bookings, customer dashboard, verified reviews and WooCommerce integration for Bangkok Guru.
 * Version: 0.13.0
 * Author: Min SiThu (itsmeminsithu)
 * Author URI: https://minsithu.org
 * Text Domain: bangkok-guru-bookings
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 */
if(!defined('ABSPATH'))exit;
define('BGX_VERSION','0.13.0');
define('BGX_DIR',plugin_dir_path(__FILE__));
define('BGX_URL',plugin_dir_url(__FILE__));
// If an older Bangkok Guru theme still defines the booking functions, avoid redeclaration.
// Install the v0.12 theme first; database records are left untouched.
$bgx_active_theme=wp_get_theme();
if($bgx_active_theme->get_stylesheet()==='bangkok-guru' && version_compare($bgx_active_theme->get('Version'),'0.13.0','<')){
    register_activation_hook(__FILE__,function(){deactivate_plugins(plugin_basename(__FILE__));wp_die('Upgrade Bangkok Guru theme to v0.12 before activating Bangkok Guru Bookings. No bookings were modified.');});
    add_action('admin_notices',function(){if(current_user_can('activate_plugins'))echo '<div class="notice notice-error"><p>Bangkok Guru Bookings is paused: update Bangkok Guru theme to v0.12 to prevent incompatible function definitions.</p></div>';});
    return;
}
foreach(array('content','booking','woocommerce','forms','privacy','filters','language','search','dashboard','reviews') as $module){require_once BGX_DIR.'includes/'.$module.'.php';}
function bgx_activate(){
    $theme=wp_get_theme();
    if($theme->get_stylesheet()==='bangkok-guru' && version_compare($theme->get('Version'),'0.13.0','<')){
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Update Bangkok Guru theme to v0.12 or newer BEFORE activating the separate Booking plugin. Your existing data has not been changed.');
    }
    bg_install_inventory(); bg_register_content(); bg_ensure_expiry_schedule();
    foreach(array('bgx_dashboard_page'=>array('my-bookings','My Bookings','[bangkok_guru_my_bookings]'),'bgx_search_page'=>array('find-trips','Find Trips','[bangkok_guru_trip_search]')) as $option=>$config){
        $existing=(int)get_option($option,0);
        if($existing && get_post_status($existing))continue;
        $by_path=get_page_by_path($config[0]);
        if($by_path){update_option($option,$by_path->ID);continue;}
        $page=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_name'=>$config[0],'post_title'=>$config[1],'post_content'=>$config[2]),true);
        if(!is_wp_error($page))update_option($option,$page);
    }
    flush_rewrite_rules();
}
register_activation_hook(__FILE__,'bgx_activate');
register_deactivation_hook(__FILE__,function(){wp_clear_scheduled_hook('bg_expire_holds');flush_rewrite_rules();});
add_action('plugins_loaded',function(){load_plugin_textdomain('bangkok-guru-bookings',false,dirname(plugin_basename(__FILE__)).'/languages');});
add_action('wp_enqueue_scripts',function(){
    wp_register_style('bangkok-guru-bookings',BGX_URL.'assets/bookings.css',array(),BGX_VERSION);
    $post=get_queried_object();$has_shortcodes=is_object($post)&&isset($post->post_content)&&(
      has_shortcode($post->post_content,'bangkok_guru_trip_search')||has_shortcode($post->post_content,'bangkok_guru_my_bookings')||has_shortcode($post->post_content,'bangkok_guru_reviews'));
    if(is_front_page()||is_singular(array('bg_journey','bg_experience','bg_destination'))||is_post_type_archive(array('bg_journey','bg_experience','bg_destination'))||$has_shortcodes)wp_enqueue_style('bangkok-guru-bookings');
});
add_action('admin_notices',function(){
    if(current_user_can('manage_options')&&!get_option('permalink_structure'))echo '<div class="notice notice-info"><p>Bangkok Guru Bookings: pretty permalinks are recommended (Settings → Permalinks).</p></div>';
});
