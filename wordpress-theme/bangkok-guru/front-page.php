<?php
get_header();
$home_id=get_queried_object_id();$style=get_post_meta($home_id,'_bg_home_style',true)?:'travel';
if(!in_array($style,array('travel','daylight','immersive','departures'),true))$style='travel';
$title=get_the_title($home_id);$intro=get_post_meta($home_id,'_bg_hero_subtitle',true)?:get_post_field('post_excerpt',$home_id);
if(!$intro)$intro=__('From hidden alleys to unforgettable escapes. Your next story starts here.','bangkok-guru');
?>
<main id="main" class="bg-home bg-home-<?php echo esc_attr($style); ?>">
<?php if(function_exists('elementor_theme_do_location')&&elementor_theme_do_location('single')){ /* Respect Elementor Theme Builder, if active. */ }
elseif(get_post_meta($home_id,'_elementor_edit_mode',true)==='builder'){
    echo '<div class="bg-container bg-elementor-home">';$p=get_post($home_id);echo apply_filters('the_content',$p->post_content);echo '</div>';
}else{ ?>
<section class="bg-hero bg-container <?php echo esc_attr('bg-hero-'.$style); ?>">
    <div class="bg-hero-copy">
        <span class="bg-eyebrow">✦ <?php echo esc_html(get_post_meta($home_id,'_bg_hero_eyebrow',true)?:'BANGKOK GURU · CURATED IN THAILAND'); ?></span>
        <h1><?php echo esc_html(get_post_meta($home_id,'_bg_hero_title',true)?:get_the_title($home_id)); ?></h1>
        <p><?php echo esc_html($intro); ?></p>
        <div class="bg-hero-actions"><a class="bg-button" href="<?php echo esc_url($style==='daylight'?get_post_type_archive_link('bg_experience'):($style==='immersive'?get_post_type_archive_link('bg_destination'):get_post_type_archive_link('bg_journey'))); ?>"><?php echo esc_html(get_post_meta($home_id,'_bg_hero_cta',true)?:'Explore journeys'); ?> <span>↗</span></a><a class="bg-quiet-link" href="<?php echo esc_url(home_url('/tailor-made/')); ?>">Plan my own trip ↗</a></div>
        <div class="bg-hero-stats"><span><strong>4</strong> ways to explore</span><span><strong>∞</strong> moments to remember</span></div>
    </div>
    <div class="bg-hero-art" role="img" aria-label="Illustrated travel scene">
<?php if($home_id && has_post_thumbnail($home_id)) : echo get_the_post_thumbnail($home_id,'large',array('class'=>'bg-hero-cover','fetchpriority'=>'high','decoding'=>'async')); else: ?>
<div class="bg-art-sun"></div><div class="bg-art-mountain bg-art-mountain-1"></div><div class="bg-art-mountain bg-art-mountain-2"></div>
<?php endif; ?>
<div class="bg-hero-image-shade" aria-hidden="true"></div><span class="bg-art-label">DISCOVER THAILAND · YOUR WAY</span><span class="bg-art-stamp">GO<br>EXPLORE ↗</span></div>
</section>
<?php if(function_exists('bgx_search_shortcode')){ ?><div class="bg-container"><?php echo do_shortcode('[bangkok_guru_trip_search]'); ?></div><?php } ?>
<?php if($style==='departures'){ ?>
<section class="bg-container bg-section"><div class="bg-section-head"><span class="bg-eyebrow">NEXT UP</span><h2>Departures worth looking forward to.</h2></div><?php bg_theme_departures_board(8); ?></section>
<?php }elseif($style==='daylight'){ ?>
<section class="bg-container bg-section"><div class="bg-section-head"><span class="bg-eyebrow">NEAR & DEAR</span><h2>One good day can change everything.</h2><p>Short adventures, longer memories.</p></div><?php bg_cards('bg_experience',3); ?></section>
<?php }elseif($style==='immersive'){ ?>
<section class="bg-container bg-section"><div class="bg-section-head"><span class="bg-eyebrow">PLACES WE LOVE</span><h2>Places with a little magic.</h2></div><?php bg_cards('bg_destination',3); ?></section>
<?php }else{ ?>
<section class="bg-container bg-section"><div class="bg-section-head"><span class="bg-eyebrow">A LITTLE FURTHER</span><h2>Journeys to fall in love with.</h2><p>Thoughtful itineraries, beautiful places and the people who know them best.</p></div><?php bg_cards('bg_journey',3); ?></section>
<?php } ?>
<section class="bg-values"><div class="bg-container"><div class="bg-section-head"><span class="bg-eyebrow">OUR WAY OF TRAVELLING</span><h2><?php echo esc_html(get_post_meta($home_id,'_bg_values_title',true)?:'Go curious. Come back changed.'); ?></h2></div><div class="bg-value-grid"><div><span>01</span><h3>Know the place</h3><p>Thoughtful routes, regional stories, and memorable local moments.</p></div><div><span>02</span><h3>Travel your pace</h3><p>Choose a departure or ask us to build something just for you.</p></div><div><span>03</span><h3>Keep it personal</h3><p>Small details make the difference between a trip and a story.</p></div></div></div></section>
<section class="bg-container bg-section"><div class="bg-section-head"><span class="bg-eyebrow">THE PERFECT ESCAPE</span><h2>More reasons to wander.</h2><a href="<?php echo esc_url(get_post_type_archive_link('bg_experience')); ?>">All day experiences ↗</a></div><?php bg_cards('bg_experience',3); ?></section>
<section class="bg-container bg-cta"><span class="bg-eyebrow">MADE FOR YOU</span><h2><?php echo esc_html(get_post_meta($home_id,'_bg_cta_title',true)?:'Have a place in mind?'); ?></h2><p><?php echo esc_html(get_post_meta($home_id,'_bg_cta_text',true)?:'Tell us your wish list, your dates and the feeling you\'re after. We\'ll do the rest.'); ?></p><a class="bg-button bg-button-sun" href="<?php echo esc_url(home_url('/tailor-made/')); ?>">Design my trip ↗</a></section>
<?php if($home_id && trim(get_post_field('post_content',$home_id))){?><section class="bg-container bg-section bg-prose"><?php echo apply_filters('the_content',get_post_field('post_content',$home_id)); ?></section><?php } ?>
<?php } ?></main><?php get_footer(); ?>
