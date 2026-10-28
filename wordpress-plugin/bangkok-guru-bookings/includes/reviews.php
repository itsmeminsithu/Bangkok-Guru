<?php
if(!defined('ABSPATH'))exit;
add_action('init',function(){
  register_post_type('bg_review',array('labels'=>array('name'=>__('Verified Reviews','bangkok-guru-bookings'),'singular_name'=>__('Review','bangkok-guru-bookings')),
   'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'show_in_rest'=>false,'supports'=>array('title','editor'),
   'capabilities'=>array('edit_post'=>'manage_options','read_post'=>'manage_options','delete_post'=>'manage_options','edit_posts'=>'manage_options','edit_others_posts'=>'manage_options','publish_posts'=>'manage_options','read_private_posts'=>'manage_options','delete_posts'=>'manage_options','create_posts'=>'do_not_allow'),'map_meta_cap'=>false,
  ));
});
/** Eligible only for authenticated customer + confirmed/fully paid booking + completed travel. */
function bgx_review_eligible_booking($entry_id,$user_id){
  if(!$entry_id||!$user_id)return 0;
  $ids=get_posts(array('post_type'=>'bg_request','post_status'=>'publish','posts_per_page'=>50,'fields'=>'ids',
    'meta_query'=>array('relation'=>'AND',array('key'=>'_bg_entry_id','value'=>$entry_id,'compare'=>'='),array('key'=>'_bg_customer_id','value'=>$user_id,'compare'=>'='))));
  foreach($ids as $id){
    if(!in_array(get_post_meta($id,'_bg_status',true),array('paid','confirmed'),true))continue;
    $date=get_post_meta($id,'_bg_departure',true);
    if(!bg_valid_date($date)||$date>=wp_date('Y-m-d'))continue;
    $existing=get_posts(array('post_type'=>'bg_review','post_status'=>array('publish','pending','draft','private'),'posts_per_page'=>1,'fields'=>'ids','meta_query'=>array(array('key'=>'_bg_booking_id','value'=>$id,'compare'=>'='))));
    if(!$existing)return (int)$id;
  }
  return 0;
}
function bgx_review_stats($entry_id){
  $reviews=get_posts(array('post_type'=>'bg_review','post_status'=>'publish','posts_per_page'=>200,'fields'=>'ids',
    'meta_query'=>array(array('key'=>'_bg_entry_id','value'=>$entry_id,'compare'=>'='),array('key'=>'_bg_verified','value'=>'1','compare'=>'='))));
  $count=0;$sum=0;
  foreach($reviews as $review){$rating=absint(get_post_meta($review,'_bg_rating',true));if($rating>=1&&$rating<=5){$count++;$sum+=$rating;}}
  return array('count'=>$count,'average'=>$count?round($sum/$count,1):0,'ids'=>$reviews);
}
function bgx_reviews_shortcode($atts){
  $atts=shortcode_atts(array('id'=>0),$atts,'bangkok_guru_reviews');
  $entry_id=absint($atts['id'])?:get_the_ID();
  if(!in_array(get_post_type($entry_id),array('bg_journey','bg_destination','bg_experience'),true))return '';
  $stats=bgx_review_stats($entry_id);
  $out='<section class="bgx-reviews" id="reviews"><h2>'.esc_html(bgx_t('Reviews from verified travellers')).'</h2>';
  if($stats['count'])$out.='<p class="bgx-rating"><strong>'.esc_html(number_format_i18n($stats['average'],1)).'/5</strong> · '.(int)$stats['count'].' verified reviews</p>';
  else $out.='<p>'.esc_html(bgx_t('No reviews yet')).'</p>';
  foreach(array_slice($stats['ids'],0,20) as $rid){$review=get_post($rid);if(!$review)continue;
    $rating=absint(get_post_meta($rid,'_bg_rating',true));
    if($rating<1||$rating>5)continue;
    $out.='<article class="bgx-review"><strong>'.esc_html(str_repeat('★',$rating).str_repeat('☆',5-$rating)).'</strong> <small>'.esc_html(bgx_t('Verified trip')).'</small><p>'.esc_html($review->post_content).'</p></article>';
  }
  if(is_user_logged_in()){
    $booking=bgx_review_eligible_booking($entry_id,get_current_user_id());
    if($booking){
      $out.='<h3>'.esc_html(bgx_t('Write a review')).'</h3><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">'.wp_nonce_field('bgx_submit_review_'.$booking,'bgx_review_nonce',true,false);
      $out.='<input type="hidden" name="action" value="bgx_submit_review"><input type="hidden" name="entry_id" value="'.(int)$entry_id.'"><input type="hidden" name="booking_id" value="'.(int)$booking.'">';
      $out.='<label>'.esc_html(bgx_t('Rating')).' <select name="rating" required><option value="">—</option>';
      for($i=5;$i>=1;$i--)$out.='<option value="'.(int)$i.'">'.(int)$i.' / 5</option>';
      $out.='</select></label><label>'.esc_html(bgx_t('Your review')).'<textarea name="review" minlength="20" maxlength="1500" required></textarea></label><button type="submit">'.esc_html(bgx_t('Submit for approval')).'</button></form>';
    }
  }
  if(isset($_GET['bgx_review_sent']))$out.='<p role="status">'.esc_html(bgx_t('Review submitted for moderation')).'</p>';
  return $out.'</section>';
}
add_shortcode('bangkok_guru_reviews','bgx_reviews_shortcode');
add_action('admin_post_bgx_submit_review',function(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!is_user_logged_in())wp_die('Authentication required',403);
    $id=absint($_POST['entry_id']??0);$booking=absint($_POST['booking_id']??0);
    if(!isset($_POST['bgx_review_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bgx_review_nonce'])),'bgx_submit_review_'.$booking))wp_die('Invalid token',403);
    if(!in_array(get_post_type($id),array('bg_journey','bg_destination','bg_experience'),true))wp_die('Invalid entry',400);
    if(!$booking || bgx_review_eligible_booking($id,get_current_user_id())!==$booking)wp_die('You must complete an eligible trip before reviewing',403);
    $rating=absint($_POST['rating']??0);$raw=$_POST['review']??'';
    $body=is_scalar($raw)?sanitize_textarea_field(wp_unslash((string)$raw)):'';
    if($rating<1||$rating>5||strlen($body)<20||strlen($body)>4500)wp_die('Invalid review',400);
    $rid=wp_insert_post(array('post_type'=>'bg_review','post_status'=>'pending','post_title'=>'Review for trip #'.$id,'post_content'=>$body,'post_author'=>get_current_user_id()),true);
    if(is_wp_error($rid))wp_die('Could not save review',500);
    update_post_meta($rid,'_bg_entry_id',$id);update_post_meta($rid,'_bg_booking_id',$booking);
    update_post_meta($rid,'_bg_rating',$rating);update_post_meta($rid,'_bg_verified','1');
    wp_safe_redirect(add_query_arg('bgx_review_sent','1',get_permalink($id).'#reviews'));exit;
});
/** Append to theme TouristTrip schema, avoiding two conflicting graphs. */
add_filter('bgx_trip_schema',function($schema,$id){
    $stats=bgx_review_stats($id);
    if($stats['count'])$schema['aggregateRating']=array('@type'=>'AggregateRating','ratingValue'=>$stats['average'],'ratingCount'=>$stats['count'],'bestRating'=>5,'worstRating'=>1);
    return $schema;
},10,2);
/** On other themes: SEO schema still available for verified tour ratings. */
add_action('wp_head',function(){
    if(function_exists('bg_seo_meta') && (!function_exists('bg_has_seo_plugin')||!bg_has_seo_plugin()))return;
    if(!is_singular(array('bg_journey','bg_destination','bg_experience')))return;
    $id=get_queried_object_id();$stats=bgx_review_stats($id);if(!$stats['count'])return;
    $schema=array('@context'=>'https://schema.org','@type'=>'TouristTrip','name'=>get_the_title($id),'url'=>get_permalink($id),
      'aggregateRating'=>array('@type'=>'AggregateRating','ratingValue'=>$stats['average'],'ratingCount'=>$stats['count'],'bestRating'=>5,'worstRating'=>1));
    echo '<script type="application/ld+json">'.wp_json_encode($schema,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
},20);
