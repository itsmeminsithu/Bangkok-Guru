<?php
if(!defined('ABSPATH'))exit;
/** Entry booking and central enquiry forms; never trust browser-supplied prices. */
function bg_honeypot(){return '<div class="bg-hidden-field" aria-hidden="true"><label>Website<input type="text" name="bg_website" tabindex="-1" autocomplete="off"></label></div><input type="hidden" name="bg_rendered_at" value="'.esc_attr(time()).'">';}
function bg_booking_form($entry_id){
    if(get_post_meta($entry_id,'_bg_demo_item',true)==='1') {
        $departures=bg_trip_departures($entry_id);
        ob_start(); ?>
        <section class="bg-booking-panel bg-demo-booking" id="book">
          <span class="bg-eyebrow">PREVIEW ITINERARY · NOT FOR SALE</span>
          <h2>See how a booking page could look.</h2>
          <p>This is a sample journey with illustrative prices, dates and availability. Reservations and online payments are disabled for demo listings.</p>
          <?php if($departures): ?><div class="bg-demo-schedule"><strong>Sample departures</strong><ul>
          <?php foreach($departures as $row): ?><li><?php echo esc_html(wp_date('M j, Y',strtotime($row['date']))); ?> · <?php echo esc_html(absint($row['seats'])); ?> sample places</li><?php endforeach; ?>
          </ul></div><?php endif; ?>
          <a class="bg-button" href="<?php echo esc_url(home_url('/tailor-made/')); ?>">Preview enquiry page ↗</a>
          <?php if(current_user_can('edit_post',$entry_id)): ?><p><a href="<?php echo esc_url(get_edit_post_link($entry_id)); ?>">Edit this sample and verify real details before enabling booking</a></p><?php endif; ?>
        </section><?php return ob_get_clean();
    }
    $departures=bg_trip_departures($entry_id);
    $available=array();foreach($departures as $d){if($d['date']>=wp_date('Y-m-d'))$available[]=$d;}
    ob_start();?><section class="bg-booking-panel" id="book"><div class="bg-eyebrow">BOOK YOUR PLACE</div><h2><?php esc_html_e('A journey worth taking.','bangkok-guru'); ?></h2>
    <p class="bg-muted"><?php esc_html_e('Choose your departure, tell us who is coming, and we will take care of the details.','bangkok-guru'); ?></p>
    <p class="bg-form-note"><?php echo esc_html(sprintf(__('Deposit: %s%% · Remaining balance due %s days before departure. Early-bird prices apply when eligible.','bangkok-guru'), (float)get_post_meta($entry_id,'_bg_deposit',true), (int)get_post_meta($entry_id,'_bg_balance_days',true))); ?></p>
    <?php if(!$available){?><p class="bg-info"><?php esc_html_e('No upcoming departures published. Ask us to plan one for you.','bangkok-guru'); ?></p><?php echo bg_enquiry_form('tailor'); ?><?php }else{?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="bg-form bg-form-booking" data-currency="<?php echo esc_attr(function_exists('get_woocommerce_currency')?get_woocommerce_currency():'THB'); ?>" data-deposit="<?php echo esc_attr(min(100,max(0,(float)get_post_meta($entry_id,'_bg_deposit',true)))); ?>">
    <input type="hidden" name="action" value="bg_submit_booking"><input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>">
    <?php wp_nonce_field('bg_submit_booking','bg_nonce');echo bg_honeypot(); ?>
    <label><?php esc_html_e('Departure date','bangkok-guru'); ?> <select name="departure" required>
    <?php foreach($available as $d){$left=bg_inventory_available($entry_id,$d['date']);$label=wp_date(get_option('date_format'),strtotime($d['date'])).' · '.($left ? $left.' places left' : 'Full · waitlist');printf('<option value="%s" data-price="%s" data-left="%d">%s</option>',esc_attr($d['date']),esc_attr(bg_departure_price($entry_id,$d['date'])),(int)$left,esc_html($label));} ?></select></label>
    <div class="bg-form-two"><label><?php esc_html_e('Travellers','bangkok-guru'); ?><input name="travellers" type="number" min="1" max="20" value="2" required></label><label><?php esc_html_e('Payment preference','bangkok-guru'); ?><select name="payment"><option value="request">Request a booking</option><?php if(function_exists('wc_create_order')){?><option value="deposit">Pay deposit online</option><option value="full">Pay full amount online</option><?php } ?></select></label></div>
    <div class="bg-form-two"><label><?php esc_html_e('Your name','bangkok-guru'); ?><input name="name" autocomplete="name" maxlength="100" required></label><label><?php esc_html_e('Email','bangkok-guru'); ?><input type="email" name="email" autocomplete="email" maxlength="160" required></label></div>
    <label><?php esc_html_e('Phone (optional)','bangkok-guru'); ?><input name="phone" autocomplete="tel" maxlength="40"></label>
    <label><?php esc_html_e('Anything we should know?','bangkok-guru'); ?><textarea name="message" rows="3" maxlength="1500"></textarea></label>
    <div class="bg-booking-summary" role="status" aria-live="polite"><strong>Price estimate</strong><span data-bg-estimate>Choose departure and number of travellers.</span><small>Final price and availability are checked securely on submission.</small></div>
    <label class="bg-consent"><input type="checkbox" name="bg_consent" value="1" required> <span><?php esc_html_e('I agree to be contacted about this booking.','bangkok-guru'); ?> <?php if(get_privacy_policy_url()){?><a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Privacy policy','bangkok-guru'); ?></a><?php } ?></span></label>
    <p class="bg-form-note"><?php esc_html_e('Prices are calculated securely on the server. A non-payment request holds seats temporarily; availability can change.','bangkok-guru'); ?></p>
    <button type="submit" class="bg-button bg-button-block"><?php esc_html_e('Continue to booking →','bangkok-guru'); ?></button>
    </form><?php }?></section><?php return ob_get_clean();
}
function bg_enquiry_form($type='message'){
    ob_start();?><form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="bg-form bg-enquiry-form">
        <input type="hidden" name="action" value="bg_submit_enquiry"><input type="hidden" name="request_type" value="<?php echo esc_attr($type==='tailor'?'tailor':'message'); ?>"><?php wp_nonce_field('bg_submit_enquiry','bg_nonce');echo bg_honeypot(); ?>
        <div class="bg-form-two"><label>Your name<input name="name" autocomplete="name" maxlength="100" required></label><label>Email address<input name="email" type="email" autocomplete="email" maxlength="160" required></label></div>
        <label>What are you dreaming of?<textarea name="message" rows="5" maxlength="2000" required></textarea></label><label class="bg-consent"><input type="checkbox" name="bg_consent" value="1" required><span>I agree to be contacted about my enquiry. <?php if(get_privacy_policy_url()){?><a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank" rel="noopener noreferrer">Privacy policy</a><?php } ?></span></label><button class="bg-button" type="submit">Send enquiry ↗</button>
    </form><?php return ob_get_clean();
}
add_shortcode('bangkok_guru_enquiry',function($atts){$a=shortcode_atts(array('type'=>'message'),$atts);return bg_enquiry_form($a['type']);});
function bg_form_error($reason){$url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('bg_error',rawurlencode($reason),$url));exit;}
function bg_form_success($id,$url=''){$url=$url?:home_url('/');wp_safe_redirect(add_query_arg('bg_reference',rawurlencode(get_post_meta($id,'_bg_reference',true)),$url));exit;}
function bg_check_public_form($nonce_action){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||(int)($_SERVER['CONTENT_LENGTH']??0)>20000)bg_form_error('Invalid request');
    if(!isset($_POST['bg_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bg_nonce'])),$nonce_action))bg_form_error('Security check failed');
    if(!empty($_POST['bg_website']))bg_form_error('Invalid submission');
    if(bg_post_field('bg_consent')!=='1')bg_form_error('Please agree to our contact and privacy terms');
    $rendered=absint($_POST['bg_rendered_at']??0);
    // Friction against simple automated submissions; NOT a substitute for edge WAF/CAPTCHA.
    if(!$rendered || $rendered>time()+30 || time()-$rendered<2 || time()-$rendered>DAY_IN_SECONDS)bg_form_error('Please reload and try again');
    $ip=isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'unknown';
    $key='bg_limit_'.md5($ip.$nonce_action);
    $count=(int)get_transient($key); if($count>=8)bg_form_error('Too many requests; try again later');
    set_transient($key,$count+1,HOUR_IN_SECONDS);
}
function bg_post_field($key){$value=$_POST[$key]??'';return is_scalar($value)?sanitize_text_field(wp_unslash((string)$value)):'';}
function bg_post_message($key){$value=$_POST[$key]??'';return is_scalar($value)?sanitize_textarea_field(wp_unslash((string)$value)):'';}
function bg_str_length($value){return function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value);}
function bg_submit_booking(){
    bg_check_public_form('bg_submit_booking');
    $id=absint($_POST['entry_id']??0);$post=get_post($id);
    if(get_post_meta($id,'_bg_demo_item',true)==='1')bg_form_error('This is a demo itinerary. Online bookings are disabled.');
    if(!$post||$post->post_status!=='publish'||!in_array($post->post_type,array('bg_journey','bg_destination','bg_experience'),true))bg_form_error('Trip not found');
    $date=bg_post_field('departure');$dates=wp_list_pluck(bg_trip_departures($id),'date');
    if(!bg_valid_date($date)||$date<wp_date('Y-m-d')||!in_array($date,$dates,true))bg_form_error('Departure not available');
    $qty=absint($_POST['travellers']??0);if($qty<1||$qty>20)bg_form_error('Invalid party size');
    $name=bg_post_field('name');$email=sanitize_email(bg_post_field('email'));
    if(bg_str_length($name)>100||bg_str_length($name)<2||!is_email($email))bg_form_error('Enter a valid name and email');
    $price=bg_departure_price($id,$date);if($price<=0)bg_form_error('Price not configured');
    $total=round($qty*$price,2);$pct=min(100,max(0,(float)get_post_meta($id,'_bg_deposit',true)));
    $deposit=round($total*$pct/100,2);$balance=round($total-$deposit,2);
    $payment=bg_post_field('payment');if(!in_array($payment,array('request','deposit','full'),true))$payment='request';
    if($payment==='deposit'&&($deposit<=0||$balance<=0))$payment='full';
    if(!function_exists('wc_create_order'))$payment='request';
    // Reconcile already-expired holds before checking actual inventory.
    bg_expire_holds();
    $held=bg_inventory_hold($id,$date,$qty);$status=$held?'new':'waitlist';
    $due_days=absint(get_post_meta($id,'_bg_balance_days',true));
    $due=max(wp_date('Y-m-d'),gmdate('Y-m-d',strtotime($date.' -'.$due_days.' days')));
    $request=bg_create_request(array('type'=>$held?'booking':'waitlist','status'=>$status,'entry_id'=>$id,'entry_title'=>get_the_title($id),'departure'=>$date,'travellers'=>$qty,'name'=>$name,'email'=>$email,'phone'=>bg_post_field('phone'),'message'=>bg_post_message('message'),'total'=>$total,'deposit_amount'=>$deposit,'balance_amount'=>$balance,'balance_due'=>$due,'payment_mode'=>$payment,'currency'=>function_exists('get_woocommerce_currency')?get_woocommerce_currency():'THB','reserved'=>$held?'1':'0','expires_at'=>$held?time()+45*MINUTE_IN_SECONDS:0));
    if(is_wp_error($request)){if($held){global $wpdb;$table=bg_inventory_table();$wpdb->query($wpdb->prepare("UPDATE $table SET reserved=GREATEST(0,reserved-%d) WHERE entry_id=%d AND departure=%s",$qty,$id,$date));}bg_form_error('Request could not be saved');}
    if($held && $payment!=='request'){
        $pay_amount=$payment==='full'?$total:$deposit;
        $order=bg_woo_create_order($request,$pay_amount,'initial');
        if(is_wp_error($order)){
            // A failed checkout isn't a booking; give seats back and show a clear error.
            bg_request_status($request,'cancelled','checkout_setup_failure');
            bg_form_error('Online checkout is temporarily unavailable. Please send a new booking request.');
        }
        update_post_meta($request,'_bg_woo_initial_order',$order->get_id());
        update_post_meta($request,'_bg_expires_at',time()+30*MINUTE_IN_SECONDS);
        bg_request_status($request,'pending_payment');
        bg_notify_request($request);
        wp_safe_redirect($order->get_checkout_payment_url());exit;
    }
    bg_notify_request($request);bg_form_success($request,get_permalink($id));
}
add_action('admin_post_nopriv_bg_submit_booking','bg_submit_booking');add_action('admin_post_bg_submit_booking','bg_submit_booking');
function bg_submit_enquiry(){
    bg_check_public_form('bg_submit_enquiry');$name=bg_post_field('name');$email=sanitize_email(bg_post_field('email'));$message=bg_post_message('message');
    if(bg_str_length($name)<2||bg_str_length($name)>100||!is_email($email)||bg_str_length($message)<5||bg_str_length($message)>2000)bg_form_error('Please complete the form');
    $type=bg_post_field('request_type')==='tailor'?'tailor':'message';
    $id=bg_create_request(array('type'=>$type,'status'=>'new','name'=>$name,'email'=>$email,'message'=>$message,'reserved'=>'0'));
    if(is_wp_error($id))bg_form_error('Could not send message');
    bg_notify_request($id);bg_form_success($id,wp_get_referer()?:home_url('/'));
}
add_action('admin_post_nopriv_bg_submit_enquiry','bg_submit_enquiry');add_action('admin_post_bg_submit_enquiry','bg_submit_enquiry');
function bg_form_notices(){if(isset($_GET['bg_reference'])){echo '<div class="bg-notice" role="status">'.esc_html__('Thank you — your reference is','bangkok-guru').' <strong>'.esc_html(sanitize_text_field(wp_unslash($_GET['bg_reference']))).'</strong>. '.esc_html__('We will contact you shortly.','bangkok-guru').'</div>';}
 if(isset($_GET['bg_error']))echo '<div class="bg-notice bg-notice-error" role="alert">'.esc_html(sanitize_text_field(wp_unslash($_GET['bg_error']))).'</div>';}
add_action('wp_body_open','bg_form_notices',20);
