<?php
/** Optional WooCommerce order-based payments (no gateway lock-in). */
if(!defined('ABSPATH'))exit;
function bg_woo_create_order($request_id,$amount,$stage='initial'){
    if(!function_exists('wc_create_order'))return new WP_Error('bg_no_woo','WooCommerce is not active');
    $amount=round((float)$amount,2);if($amount<=0)return new WP_Error('bg_invalid_amount','Payment amount must be positive');
    $order=wc_create_order(array('customer_id'=>0,'created_via'=>'bangkok-guru'));
    if(is_wp_error($order))return $order;
    try {
        $name=get_post_meta($request_id,'_bg_name',true);$email=get_post_meta($request_id,'_bg_email',true);
        $currency=get_post_meta($request_id,'_bg_currency',true);
        if($currency && method_exists($order,'set_currency'))$order->set_currency($currency);
        $order->set_billing_first_name($name);$order->set_billing_email($email);
        $order->set_billing_phone(get_post_meta($request_id,'_bg_phone',true));
        $item=new WC_Order_Item_Fee();
        $item->set_name(($stage==='balance'?'Remaining balance':'Travel booking').' · '.get_post_meta($request_id,'_bg_reference',true));
        $item->set_amount($amount);$item->set_total($amount);$item->set_tax_status('none');
        $order->add_item($item);
        $order->update_meta_data('_bg_request_id',$request_id);$order->update_meta_data('_bg_payment_stage',$stage);
        $order->calculate_totals(false);$order->save();
    } catch(Throwable $e) { $order->delete(true);return new WP_Error('bg_woo_order','Payment order could not be created'); }
    return $order;
}
function bg_woo_on_paid($order_id){
    if(!function_exists('wc_get_order'))return;$order=wc_get_order($order_id);if(!$order||!$order->is_paid())return;
    $request_id=absint($order->get_meta('_bg_request_id'));if(!$request_id||get_post_type($request_id)!=='bg_request')return;
    $stage=$order->get_meta('_bg_payment_stage');
    if(!in_array($stage,array('initial','balance'),true))return;
    $registered=absint(get_post_meta($request_id,$stage==='balance'?'_bg_woo_balance_order':'_bg_woo_initial_order',true));
    if($registered !== absint($order_id))return;
    $expected=(float)get_post_meta($request_id,$stage==='balance'?'_bg_balance_amount':(get_post_meta($request_id,'_bg_payment_mode',true)==='full'?'_bg_total':'_bg_deposit_amount'),true);
    if($expected<=0 || abs((float)$order->get_total()-$expected)>0.01){
        bg_request_status($request_id,'manual_review','payment_mismatch');
        return;
    }
    $currency=get_post_meta($request_id,'_bg_currency',true)?:get_woocommerce_currency();
    if(method_exists($order,'get_currency') && $order->get_currency()!==$currency){
        bg_request_status($request_id,'manual_review','currency_mismatch');return;
    }
    $current=get_post_meta($request_id,'_bg_status',true);
    $reserved=get_post_meta($request_id,'_bg_reserved',true)==='1';
    $expires=absint(get_post_meta($request_id,'_bg_expires_at',true));
    // A paid callback must NEVER turn a released or stale seat into a confirmed reservation.
    if(!$reserved || ($stage==='initial' && $expires && $expires<time())){
        bg_request_status($request_id,'manual_review','released_or_stale_payment');
        return;
    }
    if(in_array($current,array('cancelled','expired'),true)){
        // Never silently confirm a late order after inventory was released.
        bg_request_status($request_id,'manual_review','late_payment');
        if(!get_post_meta($request_id,'_bg_late_alerted',true)){
            update_post_meta($request_id,'_bg_late_alerted','1');
            wp_mail(get_option('admin_email'),'[Bangkok Guru] Late payment needs review','Payment received on an expired/cancelled inventory hold. Manually inspect request #'.$request_id.' and contact the traveller.');
        }
        return;
    }
    if($stage==='balance'){
        if($current==='paid')return; // Idempotent gateway callback.
        if(!in_array($current,array('deposit_paid','confirmed'),true)){
            bg_request_status($request_id,'manual_review','out_of_order_payment');
            return;
        }
        bg_request_status($request_id,'paid','woocommerce');update_post_meta($request_id,'_bg_balance_paid_at',time());
    } else {
        if(in_array($current,array('paid','deposit_paid','confirmed'),true))return;
        if(!in_array($current,array('new','pending_payment'),true)){
            bg_request_status($request_id,'manual_review','unexpected_payment');return;
        }
        $mode=get_post_meta($request_id,'_bg_payment_mode',true);
        bg_request_status($request_id,$mode==='full'?'paid':'deposit_paid','woocommerce');
        if($mode==='full')update_post_meta($request_id,'_bg_balance_amount',0);
        update_post_meta($request_id,'_bg_initial_paid_at',time());
    }
    delete_post_meta($request_id,'_bg_expires_at');
}
add_action('woocommerce_payment_complete','bg_woo_on_paid');
add_action('woocommerce_order_status_processing','bg_woo_on_paid');
add_action('woocommerce_order_status_completed','bg_woo_on_paid');
function bg_send_balance(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST')wp_die('Invalid request method');
    $id=absint($_POST['id']??0);
    if(!$id||!current_user_can('edit_post',$id)||get_post_type($id)!=='bg_request')wp_die('Not permitted');
    check_admin_referer('bg_send_balance_'.$id);
    if(!in_array(get_post_meta($id,'_bg_status',true),array('deposit_paid','confirmed'),true))wp_die('Deposit must be paid before invoicing the balance.');
    if(!function_exists('wc_create_order'))wp_die('Please activate WooCommerce.');
    $remaining=(float)get_post_meta($id,'_bg_balance_amount',true);
    if($remaining<=0)wp_die('No remaining balance.');
    $existing=absint(get_post_meta($id,'_bg_woo_balance_order',true));
    $order=$existing?wc_get_order($existing):null;
    if(!$order){$order=bg_woo_create_order($id,$remaining,'balance');if(is_wp_error($order))wp_die('Could not create WooCommerce balance order');update_post_meta($id,'_bg_woo_balance_order',$order->get_id());}
    $url=$order->get_checkout_payment_url();$email=get_post_meta($id,'_bg_email',true);
    $ref=get_post_meta($id,'_bg_reference',true);$due=get_post_meta($id,'_bg_balance_due',true);
    wp_mail($email,'Bangkok Guru · Balance due '.$ref,"Your remaining balance is due on $due.\nSecure WooCommerce payment link:\n$url\nReference: $ref");
    wp_safe_redirect(add_query_arg('bg_balance_sent','1',admin_url('post.php?post='.$id.'&action=edit')));exit;
}
add_action('admin_post_bg_send_balance','bg_send_balance');
add_action('admin_notices',function(){if(!empty($_GET['bg_balance_sent']))echo '<div class="notice notice-success is-dismissible"><p>Balance payment link emailed to traveller. Confirm email delivery in your configured SMTP logs.</p></div>';});
function bg_woo_on_cancel($order_id){
 if(!function_exists('wc_get_order'))return;$order=wc_get_order($order_id);if(!$order)return;
 $id=absint($order->get_meta('_bg_request_id'));
 if(!$id || get_post_type($id)!=='bg_request')return;
 // Only the registered initial order may cancel a still-unpaid booking hold.
 if($order->get_meta('_bg_payment_stage')!=='initial')return;
 if(absint(get_post_meta($id,'_bg_woo_initial_order',true))!==absint($order_id))return;
 if(get_post_meta($id,'_bg_reserved',true)!=='1')return;
 if($order->is_paid() || get_post_meta($id,'_bg_initial_paid_at',true))return;
 if(!in_array(get_post_meta($id,'_bg_status',true),array('new','pending_payment'),true))return;
 bg_request_status($id,'cancelled','woocommerce');
}
add_action('woocommerce_order_status_cancelled','bg_woo_on_cancel');
add_action('woocommerce_order_status_failed','bg_woo_on_cancel');

/** Refunds never silently free inventory. Reconcile payments and seats manually. */
function bg_woo_on_refund($order_id){
    if(!function_exists('wc_get_order'))return;
    $order=wc_get_order($order_id);if(!$order)return;
    $id=absint($order->get_meta('_bg_request_id'));
    if(!$id||get_post_type($id)!=='bg_request')return;
    $stage=$order->get_meta('_bg_payment_stage');
    if(!in_array($stage,array('initial','balance'),true))return;
    $registered=absint(get_post_meta($id,$stage==='balance'?'_bg_woo_balance_order':'_bg_woo_initial_order',true));
    if($registered!==absint($order_id))return;
    if(get_post_meta($id,'_bg_status',true)!=='manual_review'){
        bg_request_status($id,'manual_review','woocommerce_refund');
    }
    if(!get_post_meta($id,'_bg_refund_alerted',true)){
        update_post_meta($id,'_bg_refund_alerted','1');
        wp_mail(get_option('admin_email'),'[Bangkok Guru] Refund requires booking reconciliation','Review booking #'.$id.'. Verify refund amount, customer contact and held seats before releasing inventory.');
    }
}
add_action('woocommerce_order_refunded','bg_woo_on_refund',10,1);
add_action('woocommerce_order_status_refunded','bg_woo_on_refund',10,1);
