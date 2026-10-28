<?php
if(!defined('ABSPATH'))exit;
function bg_archive_filter_params(){
    $input=function($key){$v=$_GET[$key]??'';return is_scalar($v)?sanitize_text_field(wp_unslash((string)$v)):'';};
    $from=$input('bg_from_date');$to=$input('bg_to_date');
    $today=wp_date('Y-m-d');
    if(!bg_valid_date($from))$from='';
    if(!bg_valid_date($to))$to='';
    if($from && $from<$today)$from=$today;
    if($to && $to<$today)$to='';
    if($from && $to && $from>$to)$to=$from;
    $short=function($str,$max){return function_exists('mb_substr')?mb_substr($str,0,$max,'UTF-8'):substr($str,0,$max);};
    return array('keyword'=>$short($input('bg_keyword'),80),'place'=>$short($input('bg_place'),70),
      'max_price'=>min(10000000,max(0,(int)$input('bg_max_price'))), 'min_date'=>$from,'max_date'=>$to,
      'seats'=>min(20,max(1,(int)$input('bg_seats'))),'available'=>($input('bg_available')==='1'));
}
/** SQL preselects the indexed departure inventory; all public input uses placeholders. */
function bgx_available_entry_ids($filters){
    global $wpdb;$table=bg_inventory_table();
    $from=max(wp_date('Y-m-d'),$filters['min_date']?:wp_date('Y-m-d'));
    $sql="SELECT DISTINCT entry_id FROM $table WHERE departure >= %s AND (capacity-reserved) >= %d";
    $args=array($from,$filters['seats']);
    if($filters['max_date']){$sql.=' AND departure <= %s';$args[]=$filters['max_date'];}
    // Prevent unbounded database fetches; 5000 entries max for public search.
    return array_map('absint',$wpdb->get_col($wpdb->prepare($sql.' LIMIT 5000',...$args)));
}
function bgx_apply_filter_args($filters){
    $meta=array('relation'=>'AND');
    if($filters['place'])$meta[]=array('key'=>'_bg_location','value'=>$filters['place'],'compare'=>'LIKE');
    if($filters['max_price'])$meta[]=array('key'=>'_bg_price','value'=>$filters['max_price'],'compare'=>'<=','type'=>'NUMERIC');
    return count($meta)>1?$meta:array();
}
function bg_archive_query($query){
    if(is_admin() || !$query->is_main_query() || !$query->is_post_type_archive(array('bg_journey','bg_destination','bg_experience')))return;
    $f=bg_archive_filter_params();
    $query->set('posts_per_page',12);
    if($f['keyword'])$query->set('s',$f['keyword']);
    $meta=bgx_apply_filter_args($f);if($meta)$query->set('meta_query',$meta);
    if($f['available']||$f['min_date']||$f['max_date']){
        $ids=bgx_available_entry_ids($f);
        $query->set('post__in',$ids?:array(0));
    }
}
add_action('pre_get_posts','bg_archive_query');
