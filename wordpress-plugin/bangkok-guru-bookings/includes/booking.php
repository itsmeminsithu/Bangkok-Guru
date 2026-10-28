<?php
/** Booking inventory, request records and administrative actions. */
if(!defined('ABSPATH'))exit;
function bg_inventory_table(){global $wpdb;return $wpdb->prefix.'bg_inventory';}
function bg_install_inventory(){
    global $wpdb;
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    $table=bg_inventory_table();$charset=$wpdb->get_charset_collate();
    dbDelta("CREATE TABLE $table (
       id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
       entry_id bigint(20) unsigned NOT NULL,
       departure varchar(10) NOT NULL,
       capacity int(10) unsigned NOT NULL DEFAULT 0,
       reserved int(10) unsigned NOT NULL DEFAULT 0,
       PRIMARY KEY  (id),
       UNIQUE KEY entry_departure (entry_id,departure)
    ) ENGINE=InnoDB $charset;");
    update_option('bg_inventory_version','1');
}
/** WordPress cron is traffic-driven; production requires an actual system cron trigger. */
add_filter('cron_schedules',function($schedules){$schedules['bg_five_minutes']=array('interval'=>300,'display'=>'Bangkok Guru: every 5 minutes');return $schedules;});
function bg_ensure_expiry_schedule(){
    $event=wp_get_scheduled_event('bg_expire_holds');
    if($event && $event->schedule!=='bg_five_minutes')wp_clear_scheduled_hook('bg_expire_holds');
    if(!wp_next_scheduled('bg_expire_holds'))wp_schedule_event(time()+300,'bg_five_minutes','bg_expire_holds');
}
function bg_after_switch(){bg_install_inventory();bg_register_content();flush_rewrite_rules();bg_ensure_expiry_schedule();}
// Inventory is installed by the independent plugin, not by theme switching.
add_action('init','bg_ensure_expiry_schedule',99);
// Switching theme must not disable outstanding holds or expiry processing.
function bg_inventory_sync($entry_id,$departures){
    global $wpdb;$table=bg_inventory_table();
    foreach($departures as $item){
        if(!bg_valid_date($item['date']))continue;
        $capacity=absint($item['seats']);
        $wpdb->query($wpdb->prepare("INSERT INTO $table (entry_id,departure,capacity,reserved) VALUES (%d,%s,%d,0) ON DUPLICATE KEY UPDATE capacity=GREATEST(reserved,VALUES(capacity))",$entry_id,$item['date'],$capacity));
    }
}
function bg_departure_price($entry_id,$date){
    $price=(float)get_post_meta($entry_id,'_bg_price',true);
    foreach(bg_trip_departures($entry_id) as $row){if($row['date']===$date && !empty($row['price'])){$price=(float)$row['price'];break;}}
    $early=(float)get_post_meta($entry_id,'_bg_early_price',true);$end=get_post_meta($entry_id,'_bg_early_end',true);
    if($early>0 && $early<$price && bg_valid_date($end) && wp_date('Y-m-d')<=$end) $price=$early;
    return max(0,$price);
}
function bg_inventory_available($entry_id,$date){
    global $wpdb;$table=bg_inventory_table();
    $row=$wpdb->get_row($wpdb->prepare("SELECT capacity,reserved FROM $table WHERE entry_id=%d AND departure=%s",$entry_id,$date),ARRAY_A);
    return $row ? max(0,(int)$row['capacity']-(int)$row['reserved']) : 0;
}
function bg_inventory_reserved($entry_id,$date){
    global $wpdb;$table=bg_inventory_table();
    return max(0,(int)$wpdb->get_var($wpdb->prepare("SELECT reserved FROM $table WHERE entry_id=%d AND departure=%s",absint($entry_id),$date)));
}
function bg_inventory_hold($entry_id,$date,$qty){
    if($qty < 1 || $qty > 20 || !bg_valid_date($date) || $date < wp_date('Y-m-d')) return false;
    global $wpdb;$table=bg_inventory_table();
    return 1===$wpdb->query($wpdb->prepare("UPDATE $table SET reserved=reserved+%d WHERE entry_id=%d AND departure=%s AND reserved<=capacity AND reserved+%d<=capacity",$qty,$entry_id,$date,$qty));
}
function bg_inventory_release($request_id){
    global $wpdb;
    // Atomic claim: only one worker may release a hold. Prevents double release.
    $meta_table=$wpdb->postmeta;
    // InnoDB transaction keeps the reservation claim and inventory decrement together.
    // If the DB engine does not support transactions, manual reconciliation is required.
    $wpdb->query('START TRANSACTION');
    $claimed=$wpdb->query($wpdb->prepare("UPDATE $meta_table SET meta_value='0' WHERE post_id=%d AND meta_key=%s AND meta_value='1'",$request_id,'_bg_reserved'));
    if($claimed!==1){$wpdb->query('ROLLBACK');return false;}
    wp_cache_delete($request_id,'post_meta');
    $id=absint(get_post_meta($request_id,'_bg_entry_id',true));$date=get_post_meta($request_id,'_bg_departure',true);$qty=absint(get_post_meta($request_id,'_bg_travellers',true));
    $table=bg_inventory_table();
    if(!$id || !bg_valid_date($date) || !$qty){$wpdb->query('ROLLBACK');wp_cache_delete($request_id,'post_meta');return false;}
    $changed=$wpdb->query($wpdb->prepare("UPDATE $table SET reserved=GREATEST(0,reserved-%d) WHERE entry_id=%d AND departure=%s AND reserved>=%d",$qty,$id,$date,$qty));
    if($changed!==1){
        $wpdb->query('ROLLBACK');
        wp_cache_delete($request_id,'post_meta');
        return false;
    }
    $wpdb->query('COMMIT');
    return true;
}
function bg_reference($id){return 'BG-'.wp_date('ymd').'-'.str_pad((string)absint($id),6,'0',STR_PAD_LEFT).'-'.strtoupper(wp_generate_password(5,false,false));}
function bg_create_request($data){
    $post_id=wp_insert_post(array('post_type'=>'bg_request','post_status'=>'publish','post_title'=>'Bangkok Guru request · '.sanitize_key($data['type']??'message'),'post_author'=>0),true);
    if(is_wp_error($post_id))return $post_id;
    foreach($data as $key=>$value){if(!is_scalar($value))continue;update_post_meta($post_id,'_bg_'.$key,$value);}
    update_post_meta($post_id,'_bg_reference',bg_reference($post_id));
    if(get_current_user_id() && in_array(($data['type']??''),array('booking','waitlist'),true)){update_post_meta($post_id,'_bg_customer_id',get_current_user_id());}
    return $post_id;
}
/** Append a minimal internal state transition audit trail without customer PII. */
function bg_request_audit($id,$old,$next,$source='system'){
    $log=get_post_meta($id,'_bg_audit',true);
    if(!is_array($log)) $log=array();
    $log[]=array('at'=>time(),'from'=>sanitize_key($old),'to'=>sanitize_key($next),'by'=>absint(get_current_user_id()),'source'=>sanitize_key($source));
    update_post_meta($id,'_bg_audit',array_slice($log,-30));
}
function bg_request_status($id,$status,$source='system'){
    $valid=array('new','pending_payment','deposit_paid','paid','confirmed','replied','cancelled','waitlist','expired','manual_review');
    if(!in_array($status,$valid,true))return;
    if(get_post_type($id)!=='bg_request') return false;
    $old=get_post_meta($id,'_bg_status',true);
    if($old===$status) return true;
    // Terminal bookings are not permitted to become active without a NEW stock claim.
    if(in_array($old,array('cancelled','expired'),true) && !in_array($status,array('manual_review'),true))return false;
    if(in_array($status,array('new','pending_payment','confirmed','deposit_paid','paid'),true) && get_post_meta($id,'_bg_reserved',true)!=='1')return false;
    // Never free paid seats without a separate, audited refund and inventory process.
    if(in_array($old,array('paid','deposit_paid','confirmed'),true) && in_array($status,array('cancelled','expired'),true)){
        $status='manual_review';
    }
    if(in_array($status,array('cancelled','expired'),true) && get_post_meta($id,'_bg_reserved',true)==='1'){
        if(!bg_inventory_release($id)){
            $status='manual_review'; // Reservation must not disappear while inventory is inconsistent.
        }
    }
    update_post_meta($id,'_bg_status',$status);
    bg_request_audit($id,$old,$status,$source);
    return true;
}
function bg_expire_holds(){
    $ids=get_posts(array('post_type'=>'bg_request','post_status'=>'publish','posts_per_page'=>200,'fields'=>'ids','no_found_rows'=>true,'meta_query'=>array('relation'=>'AND',array('key'=>'_bg_status','value'=>array('new','pending_payment'),'compare'=>'IN'),array('key'=>'_bg_expires_at','value'=>time(),'compare'=>'<=','type'=>'NUMERIC'))));
    foreach($ids as $id){
        $expires=absint(get_post_meta($id,'_bg_expires_at',true));
        if(!$expires || $expires>=time())continue;
        // WooCommerce can settle before our status webhook has executed. Never release a paid seat.
        $order_id=absint(get_post_meta($id,'_bg_woo_initial_order',true));
        $order=$order_id && function_exists('wc_get_order')?wc_get_order($order_id):null;
        if($order && $order->is_paid()){
            bg_request_status($id,'manual_review','paid_order_unreconciled');
            if(!get_post_meta($id,'_bg_reconciliation_alerted',true)){
                update_post_meta($id,'_bg_reconciliation_alerted','1');
                wp_mail(get_option('admin_email'),'[Bangkok Guru] Paid checkout needs review','Paid WooCommerce order found after hold deadline for booking #'.$id.'. No seats were released. Reconcile manually.');
            }
            continue;
        }
        bg_request_status($id,'expired','expiry');
    }
}
add_action('bg_expire_holds','bg_expire_holds');
function bg_notify_request($id){
    $ref=get_post_meta($id,'_bg_reference',true);$type=get_post_meta($id,'_bg_type',true);$email=get_post_meta($id,'_bg_email',true);
    $subject=sprintf('[Bangkok Guru] %s · %s',$type,$ref);
    $lines=array('New request: '.$ref,'Type: '.$type,'Name: '.get_post_meta($id,'_bg_name',true),'Email: '.$email,'Status: '.get_post_meta($id,'_bg_status',true),'Review: '.admin_url('post.php?post='.$id.'&action=edit'));
    wp_mail(get_option('admin_email'),$subject,implode("\n",$lines));
    if(is_email($email))wp_mail($email,sprintf('Bangkok Guru · %s',$ref),"Thanks for reaching out.\nYour reference: $ref\nWe will contact you with the next steps.");
}
function bg_admin_requests_columns($columns){return array('cb'=>'<input type="checkbox" />','title'=>'Request','bg_ref'=>'Reference','bg_type'=>'Type','bg_status'=>'Status','bg_contact'=>'Contact','bg_date'=>'Departure','date'=>'Created');}
add_filter('manage_bg_request_posts_columns','bg_admin_requests_columns');
function bg_admin_request_column($col,$id){
    $map=array('bg_ref'=>'reference','bg_type'=>'type','bg_status'=>'status','bg_date'=>'departure');
    if(isset($map[$col]))echo esc_html(get_post_meta($id,'_bg_'.$map[$col],true));
    if($col==='bg_contact'){echo esc_html(get_post_meta($id,'_bg_name',true));echo '<br>';echo esc_html(get_post_meta($id,'_bg_email',true));}
}
add_action('manage_bg_request_posts_custom_column','bg_admin_request_column',10,2);
function bg_request_admin_box(){add_meta_box('bg_request_detail','Booking / Contact Request','bg_request_admin_render','bg_request','normal','high');}
add_action('add_meta_boxes','bg_request_admin_box');
function bg_request_admin_render($post){
    wp_nonce_field('bg_save_request','bg_request_admin_nonce');
    $fields=array('reference','type','status','entry_id','departure','travellers','name','email','phone','message','total','deposit_amount','balance_amount','balance_due','payment_mode','reserved','expires_at','woo_initial_order','woo_balance_order');
    echo '<table class="widefat striped"><tbody>';
    foreach($fields as $field){$val=get_post_meta($post->ID,'_bg_'.$field,true);echo '<tr><th>'.esc_html(ucwords(str_replace('_',' ',$field))).'</th><td>'.esc_html($val).'</td></tr>';}
    echo '</tbody></table>';
    $log=get_post_meta($post->ID,'_bg_audit',true);
    if(is_array($log)&&$log){echo '<h3>Status history</h3><ul>';foreach(array_reverse($log) as $event){echo '<li>'.esc_html(wp_date('Y-m-d H:i',absint($event['at']??0)).' · '.($event['from']??'').' → '.($event['to']??'').' · '.($event['source']??'')).'</li>';}echo '</ul>';}
    echo '<p><small>Payment states are applied by WooCommerce only. Paid booking cancellation requires manual refund and inventory reconciliation.</small></p><p><strong>Status:</strong> <select name="bg_admin_status">';$status=get_post_meta($post->ID,'_bg_status',true);
    foreach(array('new','pending_payment','deposit_paid','paid','confirmed','replied','cancelled','waitlist','expired','manual_review') as $s)printf('<option value="%s" %s>%s</option>',esc_attr($s),selected($s,$status,false),esc_html(ucwords(str_replace('_',' ',$s))));
    echo '</select></p>';
    if(in_array($status,array('deposit_paid','confirmed'),true) && function_exists('wc_create_order') && (float)get_post_meta($post->ID,'_bg_balance_amount',true)>0){echo '<p><button type="submit" form="bg-balance-post-form" class="button button-primary">Create / send WooCommerce balance payment link</button></p>';}
}
function bg_save_request_admin($id,$post){
    if($post->post_type!=='bg_request'||empty($_POST['bg_request_admin_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bg_request_admin_nonce'])),'bg_save_request')||!current_user_can('edit_post',$id)||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE))return;
    $s=sanitize_key(wp_unslash($_POST['bg_admin_status']??''));     if($s){
        $old=get_post_meta($id,'_bg_status',true);
        // Only verified WooCommerce payment callbacks may apply financial states.
        if(in_array($s,array('deposit_paid','paid','pending_payment','new','expired','waitlist'),true) && $s!==$old) return;
        if(in_array($old,array('expired','cancelled'),true) && $s!=='manual_review' && $s!==$old)return;
        if($s==='confirmed' && get_post_meta($id,'_bg_reserved',true)!=='1')return;
        if($s==='confirmed' && !in_array($old,array('paid','deposit_paid','confirmed'),true)){
            if(get_post_meta($id,'_bg_payment_mode',true)!=='request'||get_post_meta($id,'_bg_reserved',true)!=='1')return;
        }
        if(in_array($old,array('deposit_paid','paid','confirmed'),true) && !in_array($s,array('confirmed','manual_review','cancelled'),true))return;
        if($s==='replied' && !in_array(get_post_meta($id,'_bg_type',true),array('tailor','message'),true))return;
        bg_request_status($id,$s,'admin');
    }
}
add_action('save_post_bg_request','bg_save_request_admin',10,2);

/** A distinct form in the admin footer avoids invalid nested forms inside the WP post editor. */
function bg_admin_balance_footer_form(){
    global $post;
    if(!$post || $post->post_type!=='bg_request' || !current_user_can('edit_post',$post->ID))return;
    if(!in_array(get_post_meta($post->ID,'_bg_status',true),array('deposit_paid','confirmed'),true))return;
    if((float)get_post_meta($post->ID,'_bg_balance_amount',true)<=0)return;
    echo '<form id="bg-balance-post-form" action="'.esc_url(admin_url('admin-post.php')).'" method="post">';
    echo '<input type="hidden" name="action" value="bg_send_balance"><input type="hidden" name="id" value="'.esc_attr($post->ID).'">';
    wp_nonce_field('bg_send_balance_'.$post->ID);
    echo '</form>';
}
add_action('admin_footer-post.php','bg_admin_balance_footer_form');
