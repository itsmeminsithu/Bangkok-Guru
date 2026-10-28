<?php
/** Isolated demo import runtime. WordPress mocked; not a real WordPress installation. */
error_reporting(E_ALL);
define('ABSPATH',dirname(__DIR__).'/');
define('BG_DIR',dirname(__DIR__).'/wordpress-theme/bangkok-guru');
define('BGX_VERSION','0.13.0');
define('DAY_IN_SECONDS',86400);
class WP_Error {private $m;function __construct($k='',$m=''){$this->m=$m;}function get_error_message(){return $this->m;}}
function is_wp_error($a){return $a instanceof WP_Error;}
function add_action(){}function add_theme_page(){}function current_user_can(){return true;}
function sanitize_title($s){return preg_replace('/[^a-z0-9-]+/','-',strtolower((string)$s));}
function sanitize_key($s){return preg_replace('/[^a-z0-9_-]+/','',strtolower((string)$s));}
function sanitize_text_field($s){return strip_tags((string)$s);}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function absint($s){return abs((int)$s);}function wp_date($fmt,$timestamp=null){return gmdate($fmt,$timestamp??time());}
$GLOBALS['opts']=['blogdescription'=>'Just another WordPress site'];$GLOBALS['posts']=[];$GLOBALS['meta']=[];$GLOBALS['thumbnails']=[];$GLOBALS['nav_items']=[];$GLOBALS['theme_mods']=[];$GLOBALS['seq']=100;
function get_option($key,$default=false){return $GLOBALS['opts'][$key]??$default;}
function update_option($key,$value,$autoload=false){$GLOBALS['opts'][$key]=$value;}
function delete_option($key){unset($GLOBALS['opts'][$key]);}
function get_post($id){return $GLOBALS['posts'][$id]??null;}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function update_post_meta($id,$key,$val){$GLOBALS['meta'][$id][$key]=$val;}
function get_post_status($id){$p=get_post($id);return $p?$p->post_status:false;}
function wp_insert_post($params,$wp_error=false){$id=++$GLOBALS['seq'];$params['ID']=$id;$GLOBALS['posts'][$id]=(object)$params;return $id;}
function wp_update_post($params,$wp_error=false){$id=$params['ID'];$GLOBALS['posts'][$id]=(object)array_merge((array)$GLOBALS['posts'][$id],$params);return $id;}
function get_page_by_path($slug,$mode=OBJECT,$type='page'){
 foreach($GLOBALS['posts'] as $p){if(($p->post_name??'')===$slug&&($p->post_type??'')===$type)return $p;}return null;
}
function bg_register_content(){}
function bg_inventory_sync($id,$rows){$GLOBALS['sync'][$id]=$rows;}
function wp_get_nav_menu_object($s){return null;}
function wp_create_nav_menu($s){return 1;}
function wp_get_nav_menu_items($id){return $GLOBALS['nav_items'][$id]??[];}
function wp_update_nav_menu_item($id,$item,$data){$GLOBALS['nav_items'][$id][]=$data;}
function get_post_type_archive_link($type){return '/'.$type.'/';}
function get_theme_mod($k,$d=null){return $GLOBALS['theme_mods'][$k]??$d;}
function set_theme_mod($k,$v){$GLOBALS['theme_mods'][$k]=$v;}
function has_post_thumbnail($id){return isset($GLOBALS['thumbnails'][$id]);}
function verify($b,$name){if(!$b){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS $name\n";}
// Required by theme demo for page path OBJECT constant.
if(!defined('OBJECT'))define('OBJECT','OBJECT');
wp_insert_post(['post_type'=>'page','post_name'=>'my-bookings','post_title'=>'My Bookings','post_status'=>'publish','post_content'=>'[bangkok_guru_my_bookings]']);
require BG_DIR.'/inc/demo.php';
$result=bg_demo_seed_site('travel',false);
verify(!is_wp_error($result),'first import succeeds');
verify(get_option('show_on_front')==='page','front page mode');
verify(count(array_filter($GLOBALS['posts'],fn($p)=>in_array($p->post_type,['bg_journey','bg_destination','bg_experience'])))===18,'18 original entries');
verify(count(array_filter($GLOBALS['posts'],fn($p)=>$p->post_type==='post'))===3,'3 editorial stories');
verify((int)get_option('page_on_front')>0,'selected front page');
verify(count($GLOBALS['nav_items'][1])===7,'seven navigation links');
$first=get_option('bg_demo_entry_bg_journey_bangkok-after-dark');
verify(get_post_meta($first,'_bg_demo_item',true)==='1','sample booking protected');
verify(count(get_post_meta($first,'_bg_departures',true))===3,'illustrative future departures');
$before=count($GLOBALS['posts']);$edited='CUSTOMER-EDITED TITLE';$GLOBALS['posts'][$first]->post_title=$edited;
$result=bg_demo_seed_site('daylight',false);
verify(!is_wp_error($result),'repeat import succeeds');
verify(count($GLOBALS['posts'])===$before,'reimport does not duplicate pages/items');
verify(get_post($first)->post_title===$edited,'existing edits preserved');
verify(get_post_meta(get_option('page_on_front'),'_bg_home_style',true)==='daylight','homepage switch works');
$privacy_id=get_option('bg_demo_privacy_draft');verify(get_post($privacy_id)->post_status==='draft','legal policy remains draft');
echo "Demo runtime smoke tests passed.\n";
