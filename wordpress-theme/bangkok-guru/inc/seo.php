<?php
/** Minimal SEO metadata; gracefully leaves rich metadata to dedicated SEO plugins. */
if(!defined('ABSPATH'))exit;
function bg_has_seo_plugin(){
  return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION');
}
function bg_seo_description($post_id){
  $text=get_post_meta($post_id,'_bg_subtitle',true);
  if(!$text)$text=get_the_excerpt($post_id);
  if(!$text)$text=get_post_field('post_content',$post_id);
  return wp_trim_words(wp_strip_all_tags(strip_shortcodes((string)$text)),28,'…');
}
function bg_seo_meta(){
  if(is_admin() || bg_has_seo_plugin() || is_404() || is_search())return;
  $obj=get_queried_object();$id=($obj instanceof WP_Post)?(int)$obj->ID:0;
  $types=array('bg_journey','bg_destination','bg_experience');
  $is_trip=$id && is_singular($types);
  if(!$is_trip && !is_front_page())return;
  $title=$id?get_the_title($id):get_bloginfo('name');
  $desc=$is_trip?bg_seo_description($id):get_bloginfo('description');
  if(!$desc)$desc='Discover journeys, destinations and experiences in Bangkok and Thailand.';
  $url=$is_trip?get_permalink($id):home_url('/');
  echo '<meta name="description" content="'.esc_attr($desc).'">' . "
";
  echo '<meta property="og:type" content="'.($is_trip?'article':'website').'">' . "
";
  echo '<meta property="og:title" content="'.esc_attr($title).'">' . "
";
  echo '<meta property="og:description" content="'.esc_attr($desc).'">' . "
";
  echo '<meta property="og:url" content="'.esc_url($url).'">' . "
";
  echo '<meta name="twitter:card" content="summary_large_image">' . "
";
  if($id && has_post_thumbnail($id))echo '<meta property="og:image" content="'.esc_url(get_the_post_thumbnail_url($id,'large')).'">' . "
";
  $organization=array('@context'=>'https://schema.org','@type'=>'TravelAgency','name'=>get_bloginfo('name'),'url'=>home_url('/'));
  $site=array('@context'=>'https://schema.org','@type'=>'WebSite','name'=>get_bloginfo('name'),'url'=>home_url('/'));
  $schema=$is_trip?array('@context'=>'https://schema.org','@type'=>'TouristTrip','name'=>$title,'description'=>$desc,'url'=>$url):array('@context'=>'https://schema.org','@graph'=>array($organization,$site));
  if($is_trip){
    if(has_post_thumbnail($id))$schema['image']=get_the_post_thumbnail_url($id,'large');
    $place=get_post_meta($id,'_bg_location',true);
    if($place)$schema['touristType']='Travelers interested in '.sanitize_text_field($place);
    $schema['provider']=array('@type'=>'TravelAgency','name'=>get_bloginfo('name'),'url'=>home_url('/'));
  }
  if($is_trip)$schema=apply_filters('bgx_trip_schema',$schema,$id);
  echo '<script type="application/ld+json">'.wp_json_encode($schema,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>' . "
";
}
add_action('wp_head','bg_seo_meta',3);
// No duplicate canonical tags; WordPress Core or installed SEO plugin manages them.
add_filter('wp_robots',function($robots){if(isset($_GET['bg_reference'])||isset($_GET['bg_error'])||isset($_GET['bg_keyword'])||isset($_GET['bg_place'])||isset($_GET['bg_max_price'])||isset($_GET['bg_from_date'])||isset($_GET['bg_to_date'])||isset($_GET['bg_available'])||isset($_GET['bg_find'])||isset($_GET['bgx_access'])||isset($_GET['bgx_token']))$robots['noindex']=true;return $robots;});
