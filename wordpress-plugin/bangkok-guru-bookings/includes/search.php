<?php
if(!defined('ABSPATH'))exit;
/** [bangkok_guru_trip_search] provides cross-type filtered search with indexed availability. */
function bgx_search_shortcode(){
    $f=bg_archive_filter_params();$did_search=isset($_GET['bg_find']);$out='<section class="bgx-search"><h2>'.esc_html(bgx_t('Find your next journey')).'</h2>';
    $out.='<form method="get" class="bgx-search-form" role="search">';
    $fields=array('bg_keyword'=>array('Search','search',$f['keyword']),'bg_place'=>array('Destination','text',$f['place']),
      'bg_from_date'=>array('From date','date',$f['min_date']),'bg_to_date'=>array('Until date','date',$f['max_date']),
      'bg_max_price'=>array('Budget','number',$f['max_price']?:''),'bg_seats'=>array('Travellers','number',$f['seats']));
    foreach($fields as $key=>$args){$out.='<label>'.esc_html(bgx_t($args[0])).'<input type="'.esc_attr($args[1]).'" name="'.esc_attr($key).'" value="'.esc_attr($args[2]).'" '.($key==='bg_seats'?'min="1" max="20"':($key==='bg_max_price'?'min="0"':'')).'></label>';}
    $out.='<label class="bgx-check"><input type="checkbox" name="bg_available" value="1" '.checked($f['available'],true,false).'>'.esc_html(bgx_t('Available only')).'</label><input type="hidden" name="bg_find" value="1"><button type="submit">'.esc_html(bgx_t('Find trips')).'</button></form>';
    if(!$did_search)return $out.'</section>';
    $args=array('post_type'=>array('bg_journey','bg_destination','bg_experience'),'post_status'=>'publish','posts_per_page'=>12,
      'paged'=>max(1,get_query_var('paged')?:absint($_GET['pg']??1)),'no_found_rows'=>false);
    if($f['keyword'])$args['s']=$f['keyword'];
    $meta=bgx_apply_filter_args($f);if($meta)$args['meta_query']=$meta;
    if($f['available']||$f['min_date']||$f['max_date'])$args['post__in']=bgx_available_entry_ids($f)?:array(0);
    $q=new WP_Query($args);$out.='<div class="bgx-grid">';
    while($q->have_posts()){$q->the_post();$id=get_the_ID();$loc=get_post_meta($id,'_bg_location',true);$price=get_post_meta($id,'_bg_price',true);
      $out.='<article class="bgx-result">'.get_the_post_thumbnail($id,'medium',array('loading'=>'lazy')).'<div><small>'.esc_html($loc).'</small><h3><a href="'.esc_url(get_permalink($id)).'">'.esc_html(get_the_title($id)).'</a></h3><p>'.esc_html(wp_trim_words(get_the_excerpt($id),20)).'</p><strong>'.esc_html($price!==''?number_format_i18n((float)$price,0).' '.(function_exists('get_woocommerce_currency')?get_woocommerce_currency():'THB'):'').'</strong> <a href="'.esc_url(get_permalink($id)).'">'.esc_html(bgx_t('See details')).' →</a></div></article>';}
    $out.='</div>';
    if(!$q->post_count)$out.='<p>'.esc_html(bgx_t('No matching tours')).'</p>';
    if($q->max_num_pages>1){$out.='<nav class="bgx-pager">';for($i=1;$i<=min(20,$q->max_num_pages);$i++){$url=add_query_arg('pg',$i);$out.='<a href="'.esc_url($url).'" '.($i===(int)$args['paged']?'aria-current="page"':'').'>'.(int)$i.'</a> ';}$out.='</nav>';}
    wp_reset_postdata();return $out.'</section>';
}
add_shortcode('bangkok_guru_trip_search','bgx_search_shortcode');
