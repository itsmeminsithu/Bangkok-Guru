<?php
if(!defined('ABSPATH'))exit;
/** Public UI translations for the booking plugin; no translation claims for user-entered descriptions. */
function bgx_language(){
    $raw=$_GET['bg_lang']??($_COOKIE['bgx_lang']??'en');
    if(!is_scalar($raw))return 'en';
    $candidate=sanitize_key(wp_unslash((string)$raw));
    return in_array($candidate,array('en','th','my'),true)?$candidate:'en';
}
add_action('init',function(){
    if(is_admin()||!isset($_GET['bg_lang'])||!is_scalar($_GET['bg_lang']))return;
    $lang=sanitize_key(wp_unslash((string)$_GET['bg_lang']));
    if(!in_array($lang,array('en','th','my'),true))return;
    if(!headers_sent())setcookie('bgx_lang',$lang,array('expires'=>time()+365*DAY_IN_SECONDS,'path'=>'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Lax'));
},1);
function bgx_t($key){
    $translations=array(
      'th'=>array(
        'Home'=>'หน้าแรก','Journeys'=>'ทริปท่องเที่ยว','Destinations'=>'จุดหมาย','Experiences'=>'กิจกรรม','My bookings'=>'การจองของฉัน','Find trips'=>'ค้นหาทริป','From date'=>'ตั้งแต่วันที่','Until date'=>'ถึงวันที่','Travellers'=>'ผู้เดินทาง','Available only'=>'เฉพาะที่ว่าง',
        'Find your next journey'=>'ค้นหาทริปที่ใช่','Search'=>'ค้นหา','Destination'=>'จุดหมายปลายทาง','Budget'=>'งบประมาณ','Any date'=>'ทุกวัน','See details'=>'ดูรายละเอียด',
        'No matching tours'=>'ไม่พบทริปที่ตรงกับเงื่อนไข','Bookings & payments'=>'การจองและการชำระเงิน','Sign in to view your bookings'=>'เข้าสู่ระบบเพื่อดูการจอง',
        'Booking reference'=>'หมายเลขการจอง','Status'=>'สถานะ','Departure'=>'วันออกเดินทาง','Guests'=>'ผู้เดินทาง','Total'=>'ยอดรวม',
        'Deposit'=>'เงินมัดจำ','Balance'=>'ยอดคงเหลือ','Pay securely'=>'ชำระเงินอย่างปลอดภัย','No bookings yet'=>'ยังไม่มีการจอง',
        'Look up a guest booking'=>'ตรวจสอบการจองของผู้เยี่ยมชม','Email'=>'อีเมล','Send private access link'=>'ส่งลิงก์ส่วนตัว','Check your email for a secure access link if the details match'=>'หากข้อมูลถูกต้อง กรุณาตรวจสอบลิงก์ในอีเมล',
        'Reviews from verified travellers'=>'รีวิวจากผู้เดินทางที่ยืนยันแล้ว','Write a review'=>'เขียนรีวิว','Rating'=>'คะแนน','Your review'=>'รีวิวของคุณ','Submit for approval'=>'ส่งเพื่อตรวจสอบ','Review submitted for moderation'=>'ส่งรีวิวเพื่อตรวจสอบแล้ว','Verified trip'=>'ทริปที่ยืนยันแล้ว',
        'English'=>'อังกฤษ','Thai'=>'ไทย','Myanmar'=>'พม่า','No reviews yet'=>'ยังไม่มีรีวิว','My account'=>'บัญชีของฉัน','Availability'=>'ที่ว่าง',
      ),
      'my'=>array(
        'Home'=>'ပင်မ','Journeys'=>'ခရီးစဉ်များ','Destinations'=>'ခရီးဆုံးများ','Experiences'=>'အတွေ့အကြုံများ','My bookings'=>'ကျွန်ုပ်၏ ဘွတ်ကင်များ','Find trips'=>'ခရီးစဉ်ရှာရန်','From date'=>'စတင်ရက်','Until date'=>'နောက်ဆုံးရက်','Travellers'=>'ခရီးသည်','Available only'=>'နေရာလွတ်ရှိသည်များသာ',
        'Find your next journey'=>'နောက်ထပ်ခရီးစဉ် ရှာဖွေပါ','Search'=>'ရှာရန်','Destination'=>'ခရီးဆုံးနေရာ','Budget'=>'အသုံးစရိတ်','Any date'=>'မည်သည့်ရက်မဆို','See details'=>'အသေးစိတ်ကြည့်ရန်',
        'No matching tours'=>'ကိုက်ညီသော ခရီးစဉ် မတွေ့ပါ','Bookings & payments'=>'ဘွတ်ကင်နှင့် ငွေပေးချေမှု','Sign in to view your bookings'=>'ဘွတ်ကင်များကြည့်ရန် အကောင့်ဝင်ပါ',
        'Booking reference'=>'ဘွတ်ကင်နံပါတ်','Status'=>'အခြေအနေ','Departure'=>'ထွက်ခွာမည့်ရက်','Guests'=>'ခရီးသည်များ','Total'=>'စုစုပေါင်း',
        'Deposit'=>'စရံငွေ','Balance'=>'ကျန်ငွေ','Pay securely'=>'လုံခြုံစွာ ငွေပေးချေရန်','No bookings yet'=>'ဘွတ်ကင် မရှိသေးပါ',
        'Look up a guest booking'=>'ဧည့်သည်ဘွတ်ကင် ရှာရန်','Email'=>'အီးမေးလ်','Send private access link'=>'ကိုယ်ပိုင်လင့်ခ် ပို့ရန်','Check your email for a secure access link if the details match'=>'အချက်အလက်မှန်ကန်ပါက အီးမေးလ်တွင် လင့်ခ်ရရှိပါမည်',
        'Reviews from verified travellers'=>'အတည်ပြုခရီးသည်များ၏ သုံးသပ်ချက်များ','Write a review'=>'သုံးသပ်ချက်ရေးရန်','Rating'=>'အဆင့်သတ်မှတ်ချက်','Your review'=>'သုံးသပ်ချက်','Submit for approval'=>'အတည်ပြုရန် ပို့မည်','Review submitted for moderation'=>'သုံးသပ်ချက်ကို စစ်ဆေးရန် ပို့ပြီးပါပြီ','Verified trip'=>'အတည်ပြုခရီးစဉ်',
        'English'=>'အင်္ဂလိပ်','Thai'=>'ထိုင်း','Myanmar'=>'မြန်မာ','No reviews yet'=>'သုံးသပ်ချက် မရှိသေးပါ','My account'=>'ကျွန်ုပ်၏အကောင့်','Availability'=>'နေရာလွတ်',
      ),
    );
    return $translations[bgx_language()][$key]??$key;
}
function bgx_language_switcher(){
    $html='<div class="bg-lang-switcher" aria-label="Language">';
    foreach(array('en'=>'EN','th'=>'TH','my'=>'မြန်မာ') as $key=>$title){
        // Build internal URLs only; avoid attacker-controlled open redirects.
        $path=wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']??'/'),PHP_URL_PATH)?:'/';
        $queries=array();
        $url_query=wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']??'/'),PHP_URL_QUERY);
        if(is_string($url_query))parse_str($url_query,$queries);
        unset($queries['bg_lang']);$queries['bg_lang']=$key;
        $url=add_query_arg($queries,home_url($path));
        $html.='<a lang="'.esc_attr($key==='my'?'my':$key).'" href="'.esc_url($url).'" aria-current="'.(bgx_language()===$key?'true':'false').'">'.esc_html($title).'</a>';
    }
    return $html.'</div>';
}
add_shortcode('bangkok_guru_languages','bgx_language_switcher');

/** Per-entry custom translations. Empty translation falls back to original. */
function bgx_local_meta($post_id,$key){
    $lang=bgx_language();
    if($lang!=='en'){
        $candidate=get_post_meta($post_id,$key.'_'.$lang,true);
        if(is_string($candidate)&&trim($candidate)!=='')return $candidate;
    }
    return get_post_meta($post_id,$key,true);
}
function bgx_local_title($post_id){
    $lang=bgx_language();
    if($lang!=='en'){
        $candidate=get_post_meta($post_id,'_bg_title_'.$lang,true);
        if(is_string($candidate)&&trim($candidate)!=='')return $candidate;
    }
    return get_the_title($post_id);
}
function bgx_localized_entry_fields($post){
    echo '<hr><h3>Translations · Thai / Myanmar</h3><p>Blank translations fall back to the original. Use UTF-8 Unicode for Myanmar text.</p>';
    $fields=array('_bg_title'=>'Title','_bg_subtitle'=>'Subtitle','_bg_content'=>'Description','_bg_highlights'=>'Highlights (one per line)','_bg_itinerary'=>'Itinerary (one per line)','_bg_included'=>'Included (one per line)','_bg_excluded'=>'Not included (one per line)');
    foreach(array('th'=>'ภาษาไทย · Thai','my'=>'မြန်မာစာ · Myanmar') as $lang=>$name){
        echo '<details class="bg-translation-group"><summary><strong>'.esc_html($name).'</strong></summary><div class="bg-meta-grid">';
        foreach($fields as $meta=>$label){$key=$meta.'_'.$lang;$value=get_post_meta($post->ID,$key,true);
            echo '<label class="bg-meta-field"><strong>'.esc_html($label).'</strong>';
            if($meta==='_bg_title'||$meta==='_bg_subtitle')echo '<input type="text" name="bg_local['.esc_attr($key).']" value="'.esc_attr($value).'">';
            else echo '<textarea rows="4" name="bg_local['.esc_attr($key).']">'.esc_textarea($value).'</textarea>';
            echo '</label>';
        }
        echo '</div></details>';
    }
}
add_action('save_post',function($id,$post){
    if(!in_array($post->post_type,array('bg_journey','bg_destination','bg_experience'),true))return;
    if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return;
    if(empty($_POST['bg_entry_nonce'])||!is_scalar($_POST['bg_entry_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bg_entry_nonce'])),'bg_save_entry')||!current_user_can('edit_post',$id))return;
    $fields=array('_bg_title','_bg_subtitle','_bg_content','_bg_highlights','_bg_itinerary','_bg_included','_bg_excluded');
    $input=isset($_POST['bg_local'])&&is_array($_POST['bg_local'])?wp_unslash($_POST['bg_local']):array();
    foreach(array('th','my') as $lang){foreach($fields as $field){$key=$field.'_'.$lang;
        if(!array_key_exists($key,$input)||!is_scalar($input[$key]))continue;
        update_post_meta($id,$key,in_array($field,array('_bg_title','_bg_subtitle'),true)?sanitize_text_field($input[$key]):sanitize_textarea_field($input[$key]));
    }}
},20,2);
