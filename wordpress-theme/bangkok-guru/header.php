<?php if ( ! defined('ABSPATH') ) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?> data-bg-theme="light">
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main"><?php esc_html_e('Skip to content','bangkok-guru'); ?></a>
<div class="bg-announcement"><div class="bg-container"><span>✺ <?php echo esc_html(get_theme_mod('bg_announcement','Discover Bangkok and beyond, your way.')); ?></span><span><?php esc_html_e('Local stories. Remarkable journeys.','bangkok-guru'); ?></span></div></div>
<header class="bg-header"><div class="bg-container bg-nav">
  <a class="bg-logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Bangkok Guru home','bangkok-guru'); ?>">
    <?php if(has_custom_logo()) { $id=get_theme_mod('custom_logo'); echo wp_get_attachment_image($id,'medium',false,array('class'=>'bg-custom-logo','decoding'=>'async')); } else { ?><span class="bg-logo-original"><img class="bg-logo-light" src="<?php echo esc_url(BG_URI.'/assets/brand/bangkok-guru-logo-light.png'); ?>" alt="Bangkok Guru" width="200" height="130"><img class="bg-logo-dark" src="<?php echo esc_url(BG_URI.'/assets/brand/bangkok-guru-logo-dark.png'); ?>" alt="" width="200" height="130"></span><?php } ?>
  </a>
  <button class="bg-mobile-menu" type="button" aria-expanded="false" aria-controls="bg-primary-menu" aria-label="<?php esc_attr_e('Toggle menu','bangkok-guru'); ?>">☰</button>
  <nav id="bg-primary-menu" class="bg-menu" aria-label="<?php esc_attr_e('Primary','bangkok-guru'); ?>">
    <?php if (has_nav_menu('primary')) { wp_nav_menu(array('theme_location'=>'primary','container'=>false,'menu_class'=>'bg-menu-links')); } else { ?><ul class="bg-menu-links"><li><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(function_exists('bgx_t')?bgx_t('Home'):'Home'); ?></a></li><li><a href="<?php echo esc_url(get_post_type_archive_link('bg_journey')); ?>"><?php echo esc_html(function_exists('bgx_t')?bgx_t('Journeys'):'Journeys'); ?></a></li><li><a href="<?php echo esc_url(get_post_type_archive_link('bg_destination')); ?>"><?php echo esc_html(function_exists('bgx_t')?bgx_t('Destinations'):'Destinations'); ?></a></li><li><a href="<?php echo esc_url(get_post_type_archive_link('bg_experience')); ?>"><?php echo esc_html(function_exists('bgx_t')?bgx_t('Experiences'):'Experiences'); ?></a></li></ul><?php } ?>
  </nav>
  <div class="bg-header-actions"><?php if(function_exists('bgx_language_switcher')) echo bgx_language_switcher(); ?><?php if(function_exists('bgx_dashboard_url')){ ?><a class="bg-account-link" href="<?php echo esc_url(bgx_dashboard_url()); ?>"><?php echo esc_html(function_exists('bgx_t')?bgx_t('My bookings'):'My bookings'); ?></a><?php } ?><button type="button" class="bg-theme-toggle" aria-label="<?php esc_attr_e('Switch dark or light theme','bangkok-guru'); ?>" title="Dark / Light">◐</button><a class="bg-button bg-button-sm" href="<?php echo esc_url(get_post_type_archive_link('bg_journey')); ?>"><?php echo esc_html(get_theme_mod('bg_header_cta','Explore trips')); ?> ↗</a></div>
</div></header>
