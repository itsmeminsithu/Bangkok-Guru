<?php
if(!defined('ABSPATH')) exit;
function bg_register_content() {
    $types = array(
        'bg_journey' => array('name'=>__('Journeys','bangkok-guru'),'single'=>__('Journey','bangkok-guru'),'slug'=>'journeys','icon'=>'dashicons-palmtree'),
        'bg_destination' => array('name'=>__('Destinations','bangkok-guru'),'single'=>__('Destination','bangkok-guru'),'slug'=>'destinations','icon'=>'dashicons-location-alt'),
        'bg_experience' => array('name'=>__('Day Experiences','bangkok-guru'),'single'=>__('Day Experience','bangkok-guru'),'slug'=>'experiences','icon'=>'dashicons-tickets-alt'),
    );
    foreach ($types as $key=>$t) {
        register_post_type($key,array(
            'labels'=>array('name'=>$t['name'],'singular_name'=>$t['single'],'add_new_item'=>sprintf(__('Add %s','bangkok-guru'),$t['single']), 'edit_item'=>sprintf(__('Edit %s','bangkok-guru'),$t['single'])),
            'public'=>true,'show_in_rest'=>true,'has_archive'=>true,'rewrite'=>array('slug'=>$t['slug']),
            'menu_icon'=>$t['icon'],'supports'=>array('title','editor','thumbnail','excerpt','revisions','custom-fields'),
        ));
    }
    register_post_type('bg_request',array('labels'=>array('name'=>__('All Requests','bangkok-guru'),'singular_name'=>__('Request','bangkok-guru')),'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'menu_icon'=>'dashicons-clipboard','supports'=>array('title'),'capabilities'=>array('edit_post'=>'manage_options','read_post'=>'manage_options','delete_post'=>'manage_options','edit_posts'=>'manage_options','edit_others_posts'=>'manage_options','publish_posts'=>'do_not_allow','read_private_posts'=>'manage_options','delete_posts'=>'manage_options','create_posts'=>'do_not_allow'),'map_meta_cap'=>false,'exclude_from_search'=>true));
}
add_action('init','bg_register_content');
function bg_meta_boxes() {
    foreach(array('bg_journey','bg_destination','bg_experience') as $type) add_meta_box('bg_trip_details',__('Bangkok Guru — Page & Booking Details','bangkok-guru'),'bg_meta_box_render',$type,'normal','high');
}
add_action('add_meta_boxes','bg_meta_boxes');
function bg_meta_box_render($post) {
    wp_nonce_field('bg_save_entry','bg_entry_nonce');
    if(get_post_meta($post->ID,'_bg_demo_item',true)==='1'){
        echo '<div style="padding:16px;border-left:4px solid #0b55b5;background:#f1f6fe;margin:12px 0"><strong>Demo preview — booking disabled.</strong><p>All dates, prices and seats are illustrative. Replace the text and photos with your verified offerings. If you are ready to accept real bookings, confirm the checkbox below and save.</p><label><input type="checkbox" name="bg_demo_enable_live" value="1"> I have verified my operator details, departure dates, availability, pricing, legal obligations and media rights. Enable real bookings for this entry.</label></div>';
    }
    $fields=array(
      '_bg_subtitle'=>array('Subtitle / short summary','text'),
      '_bg_location'=>array('Location / region','text'),
      '_bg_duration'=>array('Duration (e.g. 3 days)','text'),
      '_bg_guide'=>array('Guide name','text'),
      '_bg_price'=>array('Base price per traveller (number; WooCommerce store currency)','number'),
      '_bg_deposit'=>array('Deposit percent (0–100)','number'),
      '_bg_balance_days'=>array('Balance due days before departure','number'),
      '_bg_early_price'=>array('Early-bird price per traveller (0 disables)','number'),
      '_bg_early_end'=>array('Early-bird offer ends','date'),
      '_bg_highlights'=>array('Highlights — one per line','textarea'),
      '_bg_itinerary'=>array('Itinerary — one day/stop per line','textarea'),
      '_bg_included'=>array('Included — one per line','textarea'),
      '_bg_excluded'=>array('Not included — one per line','textarea'),
      '_bg_map_url'=>array('Optional map URL','url'),
      '_bg_badge'=>array('Card badge / highlight (optional)','text'),
    );
    $layout=get_post_meta($post->ID,'_bg_layout',true);
    echo '<p><label><strong>Entry page design</strong> <select name="bg_entry[_bg_layout]">';
    foreach(array('column'=>'01 · Column + booking','story'=>'02 · Story / stacked','route'=>'03 · Route / timeline') as $val=>$label) printf('<option value="%s" %s>%s</option>',esc_attr($val),selected($val,$layout,false),esc_html($label));
    echo '</select></label></p><div class="bg-meta-grid">';
    foreach($fields as $meta=>$cfg) {
        $val=get_post_meta($post->ID,$meta,true);
        echo '<label class="bg-meta-field"><strong>'.esc_html($cfg[0]).'</strong>';
        if($cfg[1]==='textarea') printf('<textarea name="bg_entry[%s]" rows="4">%s</textarea>',esc_attr($meta),esc_textarea($val));
        else printf('<input type="%s" name="bg_entry[%s]" value="%s" %s>',esc_attr($cfg[1]),esc_attr($meta),esc_attr($val),$cfg[1]==='number'?'step="0.01" min="0"':'');
        echo '</label>';
    }
    echo '</div>'; if(function_exists('bgx_localized_entry_fields'))bgx_localized_entry_fields($post); echo '<hr/><h3>Departures & capacity</h3><p>For each date set seats and optional price. Dates with reservations cannot be removed or reduced below booked seats; changes are preserved for safety.</p><table class="widefat striped"><thead><tr><th>Date</th><th>Total seats</th><th>Override price</th><th></th></tr></thead><tbody id="bg-departure-rows">';
    $departures=get_post_meta($post->ID,'_bg_departures',true); if(!is_array($departures))$departures=array();
    foreach($departures as $d) bg_departure_admin_row($d);
    echo '</tbody></table><p><button type="button" class="button" id="bg-add-departure">+ Add departure</button></p>';
    echo '<script type="text/template" id="bg-departure-template">';bg_departure_admin_row(array('date'=>'','seats'=>'','price'=>''));echo '</script>';
    echo '<style>.bg-meta-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.bg-meta-field{display:flex;flex-direction:column;gap:6px}.bg-meta-field input,.bg-meta-field textarea{width:100%}@media(max-width:782px){.bg-meta-grid{grid-template-columns:1fr}}</style>';
    echo '<script>document.addEventListener("DOMContentLoaded",function(){var b=document.getElementById("bg-add-departure"),rows=document.getElementById("bg-departure-rows"),tpl=document.getElementById("bg-departure-template");if(!b)return;b.addEventListener("click",function(){rows.insertAdjacentHTML("beforeend",tpl.innerHTML)});rows.addEventListener("click",function(e){if(e.target.classList.contains("bg-remove-row"))e.target.closest("tr").remove()})})</script>';
}
function bg_departure_admin_row($d) {
    printf('<tr><td><input type="date" name="bg_departure_date[]" value="%s" required></td><td><input type="number" min="0" step="1" name="bg_departure_seats[]" value="%s"></td><td><input type="number" min="0" step="0.01" name="bg_departure_price[]" value="%s"></td><td><button class="button bg-remove-row" type="button">×</button></td></tr>', esc_attr($d['date']??''), esc_attr($d['seats']??''),esc_attr($d['price']??''));
}

function bg_save_entry($post_id,$post) {
    if(!in_array($post->post_type,array('bg_journey','bg_destination','bg_experience'),true))return;
    if(!isset($_POST['bg_entry_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bg_entry_nonce'])),'bg_save_entry')||!current_user_can('edit_post',$post_id)||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE))return;
    $input=isset($_POST['bg_entry'])&&is_array($_POST['bg_entry'])?wp_unslash($_POST['bg_entry']):array();
    $text=array('_bg_subtitle','_bg_location','_bg_duration','_bg_guide','_bg_badge');
    $multi=array('_bg_highlights','_bg_itinerary','_bg_included','_bg_excluded');
    foreach($text as $k) update_post_meta($post_id,$k,sanitize_text_field($input[$k]??''));
    foreach($multi as $k) update_post_meta($post_id,$k,sanitize_textarea_field($input[$k]??''));
    update_post_meta($post_id,'_bg_map_url',esc_url_raw($input['_bg_map_url']??''));
    foreach(array('_bg_price','_bg_early_price') as $k) update_post_meta($post_id,$k,max(0,(float)($input[$k]??0)));
    update_post_meta($post_id,'_bg_deposit',min(100,max(0,(float)($input['_bg_deposit']??30))));
    update_post_meta($post_id,'_bg_balance_days',min(365,max(0,absint($input['_bg_balance_days']??14))));
    $end=sanitize_text_field($input['_bg_early_end']??'');
    update_post_meta($post_id,'_bg_early_end',bg_valid_date($end)?$end:'');
    $layout=sanitize_key($input['_bg_layout']??'column');
    update_post_meta($post_id,'_bg_layout',in_array($layout,array('column','story','route'),true)?$layout:'column');
    $dates=isset($_POST['bg_departure_date'])?(array)wp_unslash($_POST['bg_departure_date']):array();
    $seats=isset($_POST['bg_departure_seats'])?(array)wp_unslash($_POST['bg_departure_seats']):array();
    $prices=isset($_POST['bg_departure_price'])?(array)wp_unslash($_POST['bg_departure_price']):array();
    $items=array();
    foreach($dates as $i=>$date){$date=sanitize_text_field($date);if(!bg_valid_date($date)||isset($items[$date]))continue;$items[$date]=array('date'=>$date,'seats'=>min(100000,absint($seats[$i]??0)),'price'=>max(0,(float)($prices[$i]??0)));}
    // Keep departures with active reserved inventory even when an editor deletes a row.
    // Never let an edit silently orphan paying travellers or active seat holds.
    $original=bg_trip_departures($post_id);
    foreach($original as $row){
        $date=$row['date']??'';
        if(!bg_valid_date($date))continue;
        $held=bg_inventory_reserved($post_id,$date);
        if($held>0){
            if(!isset($items[$date]))$items[$date]=$row;
            $items[$date]['seats']=max($held,absint($items[$date]['seats']??$row['seats']??0));
        }
    }
    ksort($items); $data=array_values($items);
    update_post_meta($post_id,'_bg_departures',$data);
    if(function_exists('bg_inventory_sync')) bg_inventory_sync($post_id,$data);
    if(get_post_meta($post_id,'_bg_demo_item',true)==='1' && !empty($_POST['bg_demo_enable_live'])){
        $has_future=false;
        foreach($data as $row){if($row['date']>=wp_date('Y-m-d') && absint($row['seats'])>0)$has_future=true;}
        if((float)get_post_meta($post_id,'_bg_price',true)>0 && $has_future){
            delete_post_meta($post_id,'_bg_demo_item');
            update_post_meta($post_id,'_bg_badge','');
        } else {
            set_transient('bg_demo_publish_error_'.get_current_user_id(),1,MINUTE_IN_SECONDS);
        }
    }
}
add_action('save_post','bg_save_entry',10,2);
function bg_valid_date($str){if(!is_string($str)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$str))return false; $dt=DateTimeImmutable::createFromFormat('!Y-m-d',$str);return $dt && $dt->format('Y-m-d')===$str;}
function bg_lines($text){return array_filter(array_map('trim',preg_split('/\R/u',(string)$text)));}
function bg_trip_departures($id){$d=get_post_meta($id,'_bg_departures',true); return is_array($d)?$d:array();}
function bg_render_departures_board($limit=8){
    $q=get_posts(array('post_type'=>array('bg_journey','bg_experience'),'posts_per_page'=>60,'post_status'=>'publish'));
    $rows=array();foreach($q as $p){foreach(bg_trip_departures($p->ID) as $d){if($d['date']<wp_date('Y-m-d'))continue;$rows[]=array('id'=>$p->ID,'date'=>$d['date'],'price'=>bg_departure_price($p->ID,$d['date']),'available'=>bg_inventory_available($p->ID,$d['date']),'demo'=>get_post_meta($p->ID,'_bg_demo_item',true)==='1');}}
    usort($rows,function($a,$b){return strcmp($a['date'],$b['date']);});$rows=array_slice($rows,0,absint($limit));
    if(!$rows){echo '<p>No scheduled departures yet.</p>';return;}
    echo '<div class="bg-departures-board">';foreach($rows as $r){echo '<a class="bg-departure-row" href="'.esc_url(get_permalink($r['id'])).'#book"><span class="bg-date-bubble">'.esc_html(wp_date('M',strtotime($r['date']))).'<strong>'.esc_html(wp_date('d',strtotime($r['date']))).'</strong></span><span class="bg-departure-title"><strong>'.esc_html(get_the_title($r['id'])).'</strong><small>'.esc_html($r['demo']?'Demo date · not bookable':($r['available']?($r['available'].' seats left'):'Waitlist open')).'</small></span><span>'.wp_kses_post(bg_money($r['price'])).'</span><span class="bg-departure-arrow">↗</span></a>';}echo '</div>';
}

add_action('admin_notices',function(){
    $key='bg_demo_publish_error_'.get_current_user_id();
    if(get_transient($key)){
        delete_transient($key);
        echo '<div class="notice notice-error is-dismissible"><p>Bangkok Guru: A demo listing needs a positive base price and at least one future departure with available seats before you can enable real bookings.</p></div>';
    }
});
