<?php
/** Isolated state-machine tests. Not a real WordPress or WooCommerce payment integration. */
define('ABSPATH',__DIR__.'/');define('MINUTE_IN_SECONDS',60);
function add_action(){}function add_filter(){}function absint($x){return abs((int)$x);}function wp_date($f,$t=null){return gmdate($f,$t??time());}
function sanitize_key($x){return strtolower(preg_replace('/[^a-z0-9_\-]/i','',(string)$x));}function wp_generate_password($len){return str_repeat('X',$len);}
function get_current_user_id(){return 12;}function get_post_type($id){return 'bg_request';}
function wp_cache_delete(){} function wp_mail(){return true;} function get_option($name){return 'admin@example.com';}
function wp_parse_url($x,$key=null){return parse_url($x,$key);}function wc_get_order($id){return $GLOBALS['orders'][$id]??null;}
function get_woocommerce_currency(){return 'THB';}
class WP_Error {}
$GLOBALS['meta']=[];
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function update_post_meta($id,$key,$v){$GLOBALS['meta'][$id][$key]=$v;return true;}
function delete_post_meta($id,$key){unset($GLOBALS['meta'][$id][$key]);}
class TestDB {
 public $prefix='wp_';public $postmeta='wp_postmeta';public $released=0;public $claim=0;public $lastSQL='';
 function prepare($sql,...$args){foreach($args as $a){$pos=strpos($sql,'%d');$p2=strpos($sql,'%s');if($pos===false||($p2!==false&&$p2<$pos)){$sql=preg_replace('/%s/',"'".addslashes((string)$a)."'",$sql,1);}else{$sql=preg_replace('/%d/',(string)(int)$a,$sql,1);}}return $sql;}
 function query($sql){$this->lastSQL=$sql;
  if(str_contains($sql,'UPDATE wp_postmeta') && str_contains($sql,"meta_value='0'")){if($this->claim)return 0;$this->claim++;return 1;}
  if(str_contains($sql,'UPDATE wp_bg_inventory') && str_contains($sql,'reserved=GREATEST')){$this->released++;return 1;}
  if(str_contains($sql,'UPDATE wp_bg_inventory') && str_contains($sql,'reserved=reserved+'))return 1;
  return 1;
 }
 function get_var($sql){return 2;}
}
$wpdb=new TestDB();
require dirname(__DIR__).'/wordpress-plugin/bangkok-guru-bookings/includes/content.php';
require dirname(__DIR__).'/wordpress-plugin/bangkok-guru-bookings/includes/booking.php';
require dirname(__DIR__).'/wordpress-plugin/bangkok-guru-bookings/includes/woocommerce.php';
function verify($b,$what){if(!$b){fwrite(STDERR,"FAIL $what\n");exit(1);}echo "PASS $what\n";}
verify(bg_valid_date('2028-02-29'),'leap year');
verify(!bg_valid_date('2026-02-29'),'invalid leap');
verify(bg_inventory_reserved(123,'2026-12-20')===2,'reserved lookup');
verify(!bg_inventory_hold(1,'2026-12-20',0),'negative/zero qty excluded');
verify(!bg_inventory_hold(1,'2026-12-20',21),'oversized qty excluded');
verify(str_contains(bg_reference(7),'BG-'),'reference generated');
$id=51;
function seed($id,$status,$mode='deposit'){$GLOBALS['meta'][$id]=['_bg_status'=>$status,'_bg_reserved'=>'1','_bg_reference'=>'BG-51','_bg_entry_id'=>99,'_bg_departure'=>'2026-12-20','_bg_travellers'=>2,'_bg_payment_mode'=>$mode,'_bg_total'=>2000,'_bg_deposit_amount'=>600,'_bg_balance_amount'=>1400,'_bg_woo_initial_order'=>101,'_bg_woo_balance_order'=>102];}
seed($id,'paid');bg_request_status($id,'cancelled','admin');
verify(get_post_meta($id,'_bg_status',true)==='manual_review','paid cancellation becomes manual review');
verify($wpdb->released===0,'paid inventory never automatically released');
verify(count(get_post_meta($id,'_bg_audit',true))===1,'audit transition recorded');
seed($id,'new');bg_request_status($id,'expired','expiry');
verify($wpdb->released===1,'unpaid expiry releases hold');
verify(bg_inventory_release($id)===false,'idempotent release claim');
class FakeOrder {public $id,$stage,$total,$currency,$paid;function __construct($id,$stage,$total,$currency='THB',$paid=true){$this->id=$id;$this->stage=$stage;$this->total=$total;$this->currency=$currency;$this->paid=$paid;}function is_paid(){return $this->paid;}function get_meta($key){return $key==='_bg_request_id'?51:($key==='_bg_payment_stage'?$this->stage:null);}function get_total(){return $this->total;}function get_currency(){return $this->currency;}}
$GLOBALS['orders']=[101=>new FakeOrder(101,'initial',600),102=>new FakeOrder(102,'balance',1400),103=>new FakeOrder(103,'initial',600)];
seed($id,'pending_payment');bg_woo_on_paid(103);
verify(get_post_meta($id,'_bg_status',true)==='pending_payment','unregistered paid order ignored');
bg_woo_on_paid(101);verify(get_post_meta($id,'_bg_status',true)==='deposit_paid','verified deposit callback');
bg_woo_on_paid(101);verify(get_post_meta($id,'_bg_status',true)==='deposit_paid','idempotent deposit callback');
bg_request_status($id,'confirmed','admin');bg_woo_on_paid(102);
verify(get_post_meta($id,'_bg_status',true)==='paid','balance paid after admin confirmation');
seed($id,'pending_payment');$GLOBALS['orders'][101]=new FakeOrder(101,'initial',555);bg_woo_on_paid(101);
verify(get_post_meta($id,'_bg_status',true)==='manual_review','mismatched amount quarantined');
seed($id,'pending_payment');$GLOBALS['orders'][101]=new FakeOrder(101,'initial',600,'USD');bg_woo_on_paid(101);
verify(get_post_meta($id,'_bg_status',true)==='manual_review','mismatched currency quarantined');
seed($id,'expired');update_post_meta($id,'_bg_late_alerted',1);$GLOBALS['orders'][101]=new FakeOrder(101,'initial',600);bg_woo_on_paid(101);
verify(get_post_meta($id,'_bg_status',true)==='manual_review','late payment never re-confirms seat');
echo "All runtime smoke tests passed.\n";

seed($id,'expired');verify(bg_request_status($id,'new','admin')===false,'terminal booking cannot reopen');
seed($id,'cancelled');verify(bg_request_status($id,'confirmed','admin')===false,'canceled seat cannot confirm');
seed($id,'pending_payment');update_post_meta($id,'_bg_expires_at',time()-300);$GLOBALS['orders'][101]=new FakeOrder(101,'initial',600);bg_woo_on_paid(101);
verify(get_post_meta($id,'_bg_status',true)==='manual_review','stale paid webhook quarantined');
seed($id,'pending_payment');update_post_meta($id,'_bg_reserved','0');bg_woo_on_paid(101);
verify(get_post_meta($id,'_bg_status',true)==='manual_review','released seat never paid/confirmed');
seed($id,'pending_payment');$GLOBALS['orders'][103]=new FakeOrder(103,'initial',600,'THB',false);bg_woo_on_cancel(103);
verify(get_post_meta($id,'_bg_status',true)==='pending_payment','unregistered cancellation ignored');
seed($id,'pending_payment');$GLOBALS['orders'][101]=new FakeOrder(101,'initial',600,'THB',false);$wpdb->claim=0;bg_woo_on_cancel(101);
verify(get_post_meta($id,'_bg_status',true)==='cancelled','matching unpaid cancellation closes booking');
verify(get_post_meta($id,'_bg_reserved',true)==='0' || $wpdb->released>=2,'matching cancellation frees held seat');
seed($id,'pending_payment');$GLOBALS['orders'][101]=new FakeOrder(101,'initial',600,'THB',true);bg_woo_on_cancel(101);
verify(get_post_meta($id,'_bg_status',true)==='pending_payment','paid WooCommerce order cannot cancel hold');
seed($id,'pending_payment');update_post_meta($id,'_bg_currency','EUR');$GLOBALS['orders'][101]=new FakeOrder(101,'initial',600,'EUR',true);bg_woo_on_paid(101);
verify(get_post_meta($id,'_bg_status',true)==='deposit_paid','stored booking currency used for verification');

function sanitize_email($x){return strtolower(trim($x));}
function get_posts($args){
 $out=[];$addr=$args['meta_query'][0]['value']??'';
 foreach($GLOBALS['meta'] as $id=>$items){if(($items['_bg_email']??'')===$addr)$out[]=$id;}
 return $out;
}
function wp_update_post($params){$GLOBALS['anon'][]=$params;return $params['ID'];}
require dirname(__DIR__).'/wordpress-plugin/bangkok-guru-bookings/includes/privacy.php';
$GLOBALS['meta'][72]=['_bg_email'=>'test@example.com','_bg_type'=>'message','_bg_status'=>'new','_bg_name'=>'Person','_bg_message'=>'Hello'];
$GLOBALS['meta'][73]=['_bg_email'=>'test@example.com','_bg_type'=>'booking','_bg_status'=>'paid','_bg_woo_initial_order'=>'101','_bg_name'=>'Person'];
$exp=bg_privacy_export('test@example.com',1);verify(count($exp['data'])===2,'WordPress privacy export includes bookings and enquiries');
$er=bg_privacy_erase('test@example.com',1);verify($er['items_removed'] && $er['items_retained'] && $er['done'],'privacy erases enquiries, retains paid bookings');
verify(get_post_meta(72,'_bg_email',true)==='' && get_post_meta(73,'_bg_email',true)==='test@example.com','privacy erasure keeps payment evidence');
echo "Additional v0.11 security smoke tests passed.\n";
seed($id,'paid');$GLOBALS['orders'][101]=new FakeOrder(101,'initial',600,'THB',true);$wpdb->released=0;
bg_woo_on_refund(101);
verify(get_post_meta($id,'_bg_status',true)==='manual_review','refunded booking goes to manual review');
verify($wpdb->released===0,'refunded booking seats are not silently released');
seed($id,'paid');$GLOBALS['orders'][103]=new FakeOrder(103,'initial',600,'THB',true);
bg_woo_on_refund(103);
verify(get_post_meta($id,'_bg_status',true)==='paid','unregistered refund ignored');
function is_admin(){return false;}function wp_unslash($str){return $str;}function sanitize_text_field($str){return strip_tags((string)$str);}
require dirname(__DIR__).'/wordpress-plugin/bangkok-guru-bookings/includes/filters.php';
$_GET=['bg_keyword'=>' River  ','bg_place'=>'Chiang Mai','bg_max_price'=>'2700'];$filters=bg_archive_filter_params();
verify($filters['keyword']===' River  ' && $filters['place']==='Chiang Mai' && $filters['max_price']===2700,'archive search filter input sanitized');
class FakeQuery {public $values=[];function is_main_query(){return true;}function is_post_type_archive($k){return true;}function set($k,$v){$this->values[$k]=$v;}}
$q=new FakeQuery();bg_archive_query($q);
verify($q->values['s']===' River  ' && count($q->values['meta_query'])===3,'archive filters use parameterized WP query API');
$_GET=['bg_max_price'=>'9999999999'];verify(bg_archive_filter_params()['max_price']===10000000,'filter budget range clamped');
echo "v0.11 refund and archive checks passed.\n";
