<?php
/** Bangkok Guru: original WordPress travel theme. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'BG_VERSION', '0.13.0' );
define( 'BG_DIR', get_template_directory() );
define( 'BG_URI', get_template_directory_uri() );
foreach ( array( 'setup', 'home', 'seo', 'customizer', 'demo', 'elementor' ) as $module ) {
    require_once BG_DIR . '/inc/' . $module . '.php';
}

add_action('admin_notices',function(){if(current_user_can('activate_plugins')&&!function_exists('bg_inventory_available'))echo '<div class="notice notice-warning"><p><strong>Bangkok Guru:</strong> Install and activate the Bangkok Guru Bookings plugin for tours, booking, reviews and customer dashboards.</p></div>';});

/* Theme-side wrappers avoid redeclaring any Booking Plugin functions during plugin activation. */
function bg_theme_booking_form($id){return function_exists('bg_booking_form')?bg_booking_form($id):'<p class="bg-muted">Bookings temporarily unavailable. Please contact us.</p>';}
function bg_theme_enquiry_form($type='message'){return function_exists('bg_enquiry_form')?bg_enquiry_form($type):'<p class="bg-muted">Enquiries temporarily unavailable.</p>';}
function bg_theme_departures_board($n=8){if(function_exists('bg_render_departures_board'))bg_render_departures_board($n);else echo '<p>Departure listings require Bangkok Guru Bookings.</p>';}
function bg_theme_lines($str){return function_exists('bg_lines')?bg_lines($str):array_filter(array_map('trim',preg_split('/\R/u',(string)$str)));}
function bg_theme_archive_filters(){return function_exists('bg_archive_filter_params')?bg_archive_filter_params():array('keyword'=>'','place'=>'','max_price'=>0,'min_date'=>'','max_date'=>'','seats'=>1,'available'=>false);}
