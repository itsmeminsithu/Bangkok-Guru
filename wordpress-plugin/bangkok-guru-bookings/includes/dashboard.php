<?php
if(!defined('ABSPATH'))exit;
function bgx_dashboard_url(){
    $id=absint(get_option('bgx_dashboard_page',0));
    return $id&&get_post_status($id)==='publish'?get_permalink($id):home_url('/my-bookings/');
}
function bgx_booking_row($id,$is_guest=false){
    $ref=get_post_meta($id,'_bg_reference',true);
    $entry_id=absint(get_post_meta($id,'_bg_entry_id',true));
    $type=get_post_meta($id,'_bg_type',true);
    $status=get_post_meta($id,'_bg_status',true);
    $date=get_post_meta($id,'_bg_departure',true);
    $qty=absint(get_post_meta($id,'_bg_travellers',true));
    $currency=get_post_meta($id,'_bg_currency',true)?:'THB';
    $money=function($amount)use($currency){return esc_html(number_format_i18n((float)$amount,2).' '.$currency);};
    $out='<article class="bgx-booking"><div class="bgx-booking-heading"><h3>'.esc_html($ref).'</h3><span class="bgx-status">'.esc_html(ucwords(str_replace('_',' ',$status))).'</span></div>';
    if($entry_id)$out.='<p><a href="'.esc_url(get_permalink($entry_id)).'">'.esc_html(get_the_title($entry_id)).'</a></p>';
    $out.='<dl><div><dt>'.esc_html(bgx_t('Departure')).'</dt><dd>'.esc_html($date).'</dd></div><div><dt>'.esc_html(bgx_t('Guests')).'</dt><dd>'.(int)$qty.'</dd></div>';
    foreach(array('total'=>'Total','deposit_amount'=>'Deposit','balance_amount'=>'Balance') as $meta=>$label)$out.='<div><dt>'.esc_html(bgx_t($label)).'</dt><dd>'.$money(get_post_meta($id,'_bg_'.$meta,true)).'</dd></div>';
    $out.='</dl>';
    if(function_exists('wc_get_order')){
        foreach(array('initial','balance') as $stage){
            $order_id=absint(get_post_meta($id,$stage==='balance'?'_bg_woo_balance_order':'_bg_woo_initial_order',true));
            $order=$order_id?wc_get_order($order_id):null;
            if($order && !$order->is_paid() && !in_array($order->get_status(),array('cancelled','refunded','failed'),true) && get_post_meta($id,'_bg_reserved',true)==='1'){
                $out.='<a class="bgx-pay" href="'.esc_url($order->get_checkout_payment_url()).'">'.esc_html(bgx_t('Pay securely')).' — '.esc_html(ucfirst($stage)).'</a>';
            }
        }
    }
    return $out.'</article>';
}
function bgx_my_bookings_shortcode(){
    $out='<section class="bgx-dashboard"><h2>'.esc_html(bgx_t('Bookings & payments')).'</h2>';
    if(is_user_logged_in()){
        $ids=get_posts(array('post_type'=>'bg_request','post_status'=>'publish','posts_per_page'=>25,'fields'=>'ids','orderby'=>'date','order'=>'DESC',
           'meta_query'=>array(array('key'=>'_bg_customer_id','value'=>get_current_user_id(),'compare'=>'=','type'=>'NUMERIC'))));
        if(!$ids)$out.='<p>'.esc_html(bgx_t('No bookings yet')).'</p>';
        foreach($ids as $id){if(in_array(get_post_meta($id,'_bg_type',true),array('booking','waitlist'),true))$out.=bgx_booking_row($id);}
    } else {
        $out.='<p>'.esc_html(bgx_t('Sign in to view your bookings')).' <a href="'.esc_url(wp_login_url(bgx_dashboard_url())).'">'.esc_html(bgx_t('My account')).'</a></p>';
    }
    // No data disclosure from a public reference number; guest lookup is an email-based capability link.
    $out.='<div class="bgx-guest"><h3>'.esc_html(bgx_t('Look up a guest booking')).'</h3>';
    if(isset($_GET['bgx_lookup_sent']))$out.='<p role="status">'.esc_html(bgx_t('Check your email for a secure access link if the details match')).'</p>';
    $out.='<form action="'.esc_url(admin_url('admin-post.php')).'" method="post">'.wp_nonce_field('bgx_guest_lookup','bgx_guest_nonce',true,false).'<input type="hidden" name="action" value="bgx_guest_lookup"><label>'.esc_html(bgx_t('Booking reference')).'<input name="reference" required maxlength="50"></label><label>'.esc_html(bgx_t('Email')).'<input type="email" name="email" required maxlength="190"></label><button>'.esc_html(bgx_t('Send private access link')).'</button></form>';
    $out.='</div>';
    if(!empty($_GET['bgx_access']) && is_scalar($_GET['bgx_access']) && !empty($_GET['bgx_token']) && is_scalar($_GET['bgx_token'])){
        $id=absint($_GET['bgx_access']);$token=sanitize_text_field(wp_unslash($_GET['bgx_token']));
        $hash=get_post_meta($id,'_bg_guest_token_hash',true);$expires=absint(get_post_meta($id,'_bg_guest_token_expires',true));
        nocache_headers();
        if(!headers_sent())header('Referrer-Policy: no-referrer');
        if(get_post_type($id)==='bg_request' && $expires>time() && strlen($token)===64 && is_string($hash) && hash_equals($hash,hash('sha256',$token)))$out.=bgx_booking_row($id,true);
        else $out.='<p role="alert">This access link is invalid or has expired.</p>';
    }
    return $out.'</section>';
}
add_shortcode('bangkok_guru_my_bookings','bgx_my_bookings_shortcode');
add_action('admin_post_nopriv_bgx_guest_lookup','bgx_guest_lookup');
add_action('admin_post_bgx_guest_lookup','bgx_guest_lookup');
function bgx_guest_lookup(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST')wp_die('Method not allowed',403);
    if(!isset($_POST['bgx_guest_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bgx_guest_nonce'])),'bgx_guest_lookup'))wp_die('Invalid request',403);
    $ref=isset($_POST['reference']) && is_scalar($_POST['reference'])?sanitize_text_field(wp_unslash($_POST['reference'])):'';
    $email=isset($_POST['email'])&&is_scalar($_POST['email'])?sanitize_email(wp_unslash($_POST['email'])):'';
    $ip=isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'';
    $limit_key='bgx_lookup_'.hash('sha256',$ip.'|'.strtolower($ref));
    $attempts=absint(get_transient($limit_key));
    if($attempts>=5)wp_die('Please try again later',429);
    set_transient($limit_key,$attempts+1,15*MINUTE_IN_SECONDS);
    if($ref && strlen($ref)<51 && is_email($email)){
        $ids=get_posts(array('post_type'=>'bg_request','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','meta_query'=>array(array('key'=>'_bg_reference','value'=>$ref,'compare'=>'='))));
        if($ids){$id=(int)$ids[0];$stored=get_post_meta($id,'_bg_email',true);
            if(is_string($stored) && hash_equals(strtolower(trim($stored)),strtolower(trim($email)))){
                $token=bin2hex(random_bytes(32));
                update_post_meta($id,'_bg_guest_token_hash',hash('sha256',$token));
                update_post_meta($id,'_bg_guest_token_expires',time()+15*MINUTE_IN_SECONDS);
                $url=add_query_arg(array('bgx_access'=>$id,'bgx_token'=>$token),bgx_dashboard_url());
                wp_mail($stored,'Bangkok Guru — Private booking access',"Your booking details can be viewed for 15 minutes using this private link:\n".$url."\nIf you did not request this, ignore this email.");
            }
        }
    }
    wp_safe_redirect(add_query_arg('bgx_lookup_sent','1',bgx_dashboard_url()));exit;
}
add_action('wp_head',function(){if(!empty($_GET['bgx_access'])&&!empty($_GET['bgx_token']))echo '<meta name="robots" content="noindex,nofollow,noarchive">' ;},1);
add_shortcode('bangkok_guru_booking',function($a){$a=shortcode_atts(array('id'=>0),$a);$id=absint($a['id']);return $id?bg_booking_form($id):'';});
