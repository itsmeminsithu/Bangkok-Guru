<?php
/** Local-only integration smoke: simulates payment_complete; never processes real card funds. */
if(!defined('ABSPATH') || !defined('BGX_SANDBOX') || !BGX_SANDBOX || wp_get_environment_type()!=='local'){
    WP_CLI::error('This test may ONLY run in local, explicitly sandboxed WordPress.');
}
if(!function_exists('wc_create_order')||!function_exists('bg_create_request'))WP_CLI::error('Activate WooCommerce and Bangkok Guru Bookings');
$assert=function($ok,$message){if(!$ok)WP_CLI::error('FAILED: '.$message);WP_CLI::log('PASS: '.$message);};
$trip=wp_insert_post(array('post_type'=>'bg_journey','post_status'=>'publish','post_title'=>'LOCAL QA — delete after test'));
$depart=wp_date('Y-m-d',time()+5*DAY_IN_SECONDS);
update_post_meta($trip,'_bg_price',3500);update_post_meta($trip,'_bg_departures',array(array('date'=>$depart,'seats'=>5,'price'=>0)));
bg_inventory_sync($trip,array(array('date'=>$depart,'seats'=>5,'price'=>0)));
$assert(bg_inventory_hold($trip,$depart,1),'atomic inventory hold');
$req=bg_create_request(array('type'=>'booking','status'=>'new','entry_id'=>$trip,'departure'=>$depart,'travellers'=>1,'name'=>'Local QA','email'=>'sandbox@example.test','total'=>3500,'deposit_amount'=>700,'balance_amount'=>2800,'payment_mode'=>'deposit','currency'=>get_woocommerce_currency(),'reserved'=>'1','expires_at'=>time()+1800));
$assert(!is_wp_error($req),'booking request created');
$initial=bg_woo_create_order($req,700,'initial');$assert(!is_wp_error($initial),'WooCommerce deposit order created');
update_post_meta($req,'_bg_woo_initial_order',$initial->get_id());bg_request_status($req,'pending_payment','sandbox');
$initial->payment_complete(); // marks order paid without real money; local-only
bg_woo_on_paid($initial->get_id());
$assert(get_post_meta($req,'_bg_status',true)==='deposit_paid','deposit event verified');
$balance=bg_woo_create_order($req,2800,'balance');$assert(!is_wp_error($balance),'balance order created');
update_post_meta($req,'_bg_woo_balance_order',$balance->get_id());
$balance->payment_complete();bg_woo_on_paid($balance->get_id());
$assert(get_post_meta($req,'_bg_status',true)==='paid','balance event marks booking paid');
$assert(bg_inventory_available($trip,$depart)===4,'seat remains reserved after payment');
$before=get_post_meta($req,'_bg_status',true);bg_woo_on_paid($balance->get_id());
$assert(get_post_meta($req,'_bg_status',true)===$before,'duplicate callback idempotent');
WP_CLI::success('LOCAL simulated payment and inventory smoke passed. No external gateway or real money was tested. QA trip ID: '.$trip.'; booking request ID: '.$req);
