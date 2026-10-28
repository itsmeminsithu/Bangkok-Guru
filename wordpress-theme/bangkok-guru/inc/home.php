<?php
if (!defined('ABSPATH')) exit;
function bg_home_box_render($post){
    wp_nonce_field('bg_home_save','bg_home_nonce'); $style=get_post_meta($post->ID,'_bg_home_style',true);
    echo '<p>Use the same page to select your homepage design; edit its title, excerpt and content normally. Elementor-edited pages can replace the default home layout.</p><label><strong>Homepage design</strong><select name="bg_home_style">';
    foreach(array('travel'=>'01 · Curated travel','daylight'=>'02 · Bright local adventures','immersive'=>'03 · Immersive editorial','departures'=>'04 · Next departures') as $v=>$name) printf('<option value="%s" %s>%s</option>',esc_attr($v),selected($style,$v,false),esc_html($name));
    echo '</select></label>';
    foreach(array('_bg_hero_eyebrow'=>'Hero eyebrow','_bg_hero_title'=>'Hero headline','_bg_hero_subtitle'=>'Hero subtitle','_bg_hero_cta'=>'Hero button label','_bg_values_title'=>'Values section headline','_bg_cta_title'=>'Final call-to-action headline','_bg_cta_text'=>'Final call-to-action paragraph') as $meta=>$label){
       $val=get_post_meta($post->ID,$meta,true);
       printf('<p><label><strong>%s</strong><br/><input type="text" name="bg_hero[%s]" value="%s" class="widefat" placeholder="%s" /></label></p>',esc_html($label),esc_attr($meta),esc_attr($val),esc_attr($label));
    }
}
function bg_save_home($post_id,$post) {
    if($post->post_type!=='page' || !isset($_POST['bg_home_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bg_home_nonce'])),'bg_home_save') || !current_user_can('edit_post',$post_id) || (defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE))return;
    $valid=array('travel','daylight','immersive','departures');
    $s=sanitize_key(wp_unslash($_POST['bg_home_style']??'travel'));
    if(in_array($s,$valid,true))update_post_meta($post_id,'_bg_home_style',$s);
    $hero=isset($_POST['bg_hero'])&&is_array($_POST['bg_hero'])?wp_unslash($_POST['bg_hero']):array();
    foreach(array('_bg_hero_eyebrow','_bg_hero_title','_bg_hero_subtitle','_bg_hero_cta','_bg_values_title','_bg_cta_title','_bg_cta_text') as $key){
       if(array_key_exists($key,$hero))update_post_meta($post_id,$key,sanitize_text_field($hero[$key]));
    }
}
add_action('save_post_page','bg_save_home',10,2);
add_action('add_meta_boxes_page', function(){ add_meta_box('bg_home_options',__('Bangkok Guru — Home Page Options','bangkok-guru'),'bg_home_box_render','page','normal','default'); });
