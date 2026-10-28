<?php
/** WordPress Core privacy tools integration. No anonymous customer-data endpoint. */
if(!defined('ABSPATH'))exit;
function bg_privacy_matches($email,$page){
    return get_posts(array(
        'post_type'=>'bg_request','post_status'=>'publish','posts_per_page'=>25,'paged'=>max(1,absint($page)),
        'fields'=>'ids','no_found_rows'=>true,
        'meta_query'=>array(array('key'=>'_bg_email','value'=>sanitize_email($email),'compare'=>'=')),
    ));
}
add_filter('wp_privacy_personal_data_exporters',function($exporters){
    $exporters['bangkok-guru']=array('exporter_friendly_name'=>'Bangkok Guru requests','callback'=>'bg_privacy_export');return $exporters;
});
function bg_privacy_export($email,$page=1){
    $ids=bg_privacy_matches($email,$page);$data=array();
    foreach($ids as $id){
        $fields=array('reference'=>'Reference','type'=>'Type','status'=>'Status','name'=>'Name','email'=>'Email','phone'=>'Phone','message'=>'Message','entry_title'=>'Tour','departure'=>'Departure','travellers'=>'Travellers','total'=>'Total','deposit_amount'=>'Deposit','balance_amount'=>'Balance','currency'=>'Currency');
        $items=array();foreach($fields as $field=>$label){$value=get_post_meta($id,'_bg_'.$field,true);if($value!=='' && is_scalar($value))$items[]=array('name'=>$label,'value'=>(string)$value);}
        $data[]=array('group_id'=>'bangkok-guru-requests','group_label'=>'Bangkok Guru booking and enquiry records','item_id'=>'bg-request-'.$id,'data'=>$items);
    }
    return array('data'=>$data,'done'=>count($ids)<25);
}
add_filter('wp_privacy_personal_data_erasers',function($erasers){
    $erasers['bangkok-guru']=array('eraser_friendly_name'=>'Bangkok Guru requests','callback'=>'bg_privacy_erase');return $erasers;
});
function bg_privacy_erase($email,$page=1){
    // Erasure mutates the email index; offset pagination would skip records.
    // Process ALL records for one verified email in this eraser pass.
    $ids=get_posts(array('post_type'=>'bg_request','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','no_found_rows'=>true,'meta_query'=>array(array('key'=>'_bg_email','value'=>sanitize_email($email),'compare'=>'='))));
    $removed=false;$retained=false;$messages=array();
    foreach($ids as $id){
        $status=get_post_meta($id,'_bg_status',true);
        $has_payment=get_post_meta($id,'_bg_initial_paid_at',true)||get_post_meta($id,'_bg_balance_paid_at',true);
        $reserved=get_post_meta($id,'_bg_reserved',true)==='1';
        $financial=get_post_meta($id,'_bg_woo_initial_order',true)||get_post_meta($id,'_bg_woo_balance_order',true);
        // Never destroy records for an active booking, any financial evidence or a booking in manual review.
        if($reserved || $has_payment || $financial || in_array($status,array('paid','deposit_paid','confirmed','pending_payment','new','manual_review'),true) && get_post_meta($id,'_bg_type',true)==='booking'){
            $retained=true;$messages[]='Request #'.$id.' was retained pending booking/payment and legal retention review.';continue;
        }
        foreach(array('name','email','phone','message') as $field){delete_post_meta($id,'_bg_'.$field);}
        wp_update_post(array('ID'=>$id,'post_title'=>'Anonymized Bangkok Guru request #'.$id));
        $removed=true;
    }
    return array('items_removed'=>$removed,'items_retained'=>$retained,'messages'=>array_values(array_unique($messages)),'done'=>true);
}
