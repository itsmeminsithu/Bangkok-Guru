<?php
if(!defined('ABSPATH'))exit;
get_header();
while(have_posts()){the_post();$id=get_the_ID();$type=get_post_type();$layout=get_post_meta($id,'_bg_layout',true)?:'column';
$location=get_post_meta($id,'_bg_location',true);$duration=get_post_meta($id,'_bg_duration',true);$guide=get_post_meta($id,'_bg_guide',true);$price=get_post_meta($id,'_bg_price',true);
$blocks=array('Highlights'=>'_bg_highlights','Day by day'=>'_bg_itinerary','Included'=>'_bg_included','Not included'=>'_bg_excluded');
?>
<main id="main" class="bg-entry bg-entry-<?php echo esc_attr($layout); ?>">
<section class="bg-container bg-entry-hero"><div class="bg-entry-intro"><a class="bg-back" href="<?php echo esc_url(get_post_type_archive_link($type)); ?>">← <?php esc_html_e('Explore all','bangkok-guru'); ?></a><p class="bg-eyebrow"><?php echo esc_html(strtoupper(str_replace('bg_','',$type))); ?> <?php if($location)echo ' · '.esc_html($location); ?></p><h1><?php echo esc_html(function_exists('bgx_local_title')?bgx_local_title($id):get_the_title($id)); ?></h1><p class="bg-lead"><?php echo esc_html((function_exists('bgx_local_meta')?bgx_local_meta($id,'_bg_subtitle'):get_post_meta($id,'_bg_subtitle',true))?:get_the_excerpt()); ?></p><div class="bg-entry-facts"><?php if($duration)echo '<span>◷ '.esc_html($duration).'</span>';if($guide)echo '<span>✧ Hosted by '.esc_html($guide).'</span>';if($price!=='')echo '<span>From '.wp_kses_post(bg_money($price)).'</span>'; ?></div></div>
<div class="bg-entry-visual" <?php echo bg_hero_background($id); ?>><span class="bg-visual-word">EXPLORE<br><em>MORE.</em></span></div></section>
<div class="bg-container bg-entry-layout"><article class="bg-entry-content bg-prose">
<?php if($layout==='story')echo '<div class="bg-eyebrow">THE STORY</div>'; elseif($layout==='route')echo '<div class="bg-eyebrow">YOUR ROUTE</div>'; ?>
<?php $local_body=function_exists('bgx_local_meta')?bgx_local_meta($id,'_bg_content'):''; if(!empty($local_body))echo wpautop(esc_html($local_body));else the_content(); ?>
<?php foreach($blocks as $heading=>$key){$lines=bg_theme_lines(function_exists('bgx_local_meta')?bgx_local_meta($id,$key):get_post_meta($id,$key,true));if(!$lines)continue;
$kind=($layout==='route'&&$heading==='Day by day')?'bg-route-list':'bg-detail-list';
echo '<section class="bg-entry-section"><span class="bg-eyebrow">THE DETAILS</span><h2>'.esc_html($heading).'</h2><ol class="'.esc_attr($kind).'">';foreach($lines as $line){echo '<li>'.esc_html($line).'</li>';}echo '</ol></section>';
} ?>
<?php $map=get_post_meta($id,'_bg_map_url',true);if($map)echo '<p><a class="bg-quiet-link" href="'.esc_url($map).'" target="_blank" rel="noopener noreferrer">Explore this place on the map ↗</a></p>'; ?>
</article><aside class="bg-entry-aside"><?php if($type==='bg_destination'): ?>
<section class="bg-booking-panel"><span class="bg-eyebrow">DISCOVER MORE</span><h2>Make this place part of your story.</h2><p>Explore inspiring trips or share your ideas for a tailor-made itinerary.</p><a class="bg-button" href="<?php echo esc_url(get_post_type_archive_link('bg_journey')); ?>">View journeys ↗</a><p><a class="bg-quiet-link" href="<?php echo esc_url(home_url('/tailor-made/')); ?>">Plan your own trip ↗</a></p></section>
<?php else: echo bg_theme_booking_form($id); endif; ?></aside></div>
<?php if(function_exists('bgx_reviews_shortcode'))echo do_shortcode('[bangkok_guru_reviews id="'.absint($id).'"]'); ?>
<section class="bg-container bg-section"><div class="bg-section-head"><span class="bg-eyebrow">KEEP EXPLORING</span><h2>There is always more to discover.</h2></div><?php bg_cards($type,3); ?></section>
</main>
<?php }get_footer(); ?>
