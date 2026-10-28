<?php
/** Bangkok Guru demo installer: explicit opt-in, idempotent and safe for existing content. */
if (!defined('ABSPATH')) exit;

function bg_demo_definitions() {
    return array(
      'travel' => array('Curated Journeys','Go further. Feel closer.','Travel-led homepage with featured journeys.'),
      'daylight' => array('Day Experiences','More adventure in a day.','Experiences-led homepage for short escapes.'),
      'immersive' => array('Destinations & Stories','Every place tells a story.','Editorial homepage centered on destinations.'),
      'departures' => array('Scheduled Departures','Your next chapter starts here.','Departure-led homepage and sample availability.'),
    );
}
function bg_demo_admin_menu() {
    add_theme_page('Bangkok Guru Setup & Demo','Bangkok Guru Setup','manage_options','bangkok-guru-demo','bg_demo_screen');
}
add_action('admin_menu','bg_demo_admin_menu');
add_action('after_switch_theme',function(){if(!get_option('bg_demo_imported_v013'))delete_option('bg_demo_wizard_dismissed');});
add_action('admin_init',function(){
    if(!current_user_can('manage_options')||get_option('bg_demo_imported_v013')||get_option('bg_demo_wizard_dismissed'))return;
    if(!isset($_GET['activated']) || ($_SERVER['REQUEST_METHOD']??'GET')!=='GET')return;
    global $pagenow;
    if($pagenow==='themes.php' && !isset($_GET['page'])){
        wp_safe_redirect(admin_url('themes.php?page=bangkok-guru-demo'));exit;
    }
},30);
add_action('admin_notices',function(){
    if(!current_user_can('manage_options')||get_option('bg_demo_imported_v013')||get_option('bg_demo_wizard_dismissed'))return;
    if(isset($_GET['page']) && $_GET['page']==='bangkok-guru-demo')return;
    echo '<div class="notice notice-info"><p><strong>Bangkok Guru is ready.</strong> Would you like to install a complete sample travel website with four homepages, tours, destinations, images, menus and editable pages? '; 
    echo '<a class="button button-primary" href="'.esc_url(admin_url('themes.php?page=bangkok-guru-demo')).'">Yes — set up demo website</a> ';
    echo '<a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=bg_demo_skip'),'bg_demo_skip')).'">No thanks</a></p></div>';
});
function bg_demo_skip() {
    if(!current_user_can('manage_options'))wp_die('Not permitted');
    check_admin_referer('bg_demo_skip');
    update_option('bg_demo_wizard_dismissed',1,false);
    wp_safe_redirect(admin_url('themes.php'));exit;
}
add_action('admin_post_bg_demo_skip','bg_demo_skip');
function bg_demo_site_data() {
    $path=BG_DIR.'/demo-data/content.json';
    if(!is_file($path))return new WP_Error('demo_missing','Demo data package is missing.');
    $bytes=file_get_contents($path);
    $data=json_decode($bytes,true);
    if(!is_array($data)||!isset($data['homepages'],$data['pages'],$data['items']))return new WP_Error('demo_corrupt','Demo data cannot be read.');
    return $data;
}
function bg_demo_plugin_ready() {
    return function_exists('bg_register_content') && defined('BGX_VERSION') && version_compare(BGX_VERSION,'0.13.0','>=');
}
function bg_demo_install_plugin() {
    if(bg_demo_plugin_ready())return true;
    if(!current_user_can('install_plugins')||!current_user_can('activate_plugins'))return new WP_Error('permissions','You need permission to install and activate plugins.');
    require_once ABSPATH.'wp-admin/includes/plugin.php';
    $plugin='bangkok-guru-bookings/bangkok-guru-bookings.php';
    $file=WP_PLUGIN_DIR.'/'.$plugin;
    if(!file_exists($file)) {
        $archive=BG_DIR.'/bundled/bangkok-guru-bookings.zip';
        if(!is_file($archive))return new WP_Error('plugin_missing','The bundled Bookings plugin ZIP is missing.');
        require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
        $upgrader=new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result=$upgrader->install($archive);
        if(is_wp_error($result))return $result;
        if(!$result || !file_exists($file))return new WP_Error('plugin_install','Plugin installation failed; upload the Bookings Plugin ZIP from Plugins → Add New.');
    }
    $meta=get_plugin_data($file,false,false);
    if(version_compare($meta['Version']??'0','0.13.0','<'))return new WP_Error('plugin_outdated','Update Bangkok Guru Bookings to v0.13.0 first. Existing Bookings plugin was not overwritten.');
    if(!is_plugin_active($plugin)){
        $result=activate_plugin($plugin);
        if(is_wp_error($result))return $result;
    }
    if(!bg_demo_plugin_ready())return new WP_Error('plugin_not_loaded','Bookings plugin could not be loaded. Reload this page and try again.');
    return true;
}
function bg_demo_screen() {
    if(!current_user_can('manage_options'))return;
    $definitions=bg_demo_definitions();$done=(bool)get_option('bg_demo_imported_v013');
    $error=isset($_GET['bg_error'])?sanitize_key(wp_unslash($_GET['bg_error'])):'';
    $errors=array('missing'=>'Booking plugin is required. Please upload and activate v0.13, then return here.','plugin'=>'Booking plugin install/activation failed. Check Plugins and server write permissions.','data'=>'Demo data import failed. See WordPress logs. Existing pages were not deleted.');
    ?>
    <div class="wrap bg-demo-wizard">
      <h1>Bangkok Guru · Website Setup</h1>
      <p class="description">Welcome! Build a customer-facing travel website preview with editable content. This importer does not overwrite your existing pages or live travel bookings.</p>
      <?php if($done):?><div class="notice notice-success inline"><p><strong>Demo installed.</strong> You can edit every sample entry in WordPress and re-run the importer safely without replacing your changes.</p></div><?php endif;?>
      <?php if($error):?><div class="notice notice-error inline"><p><?php echo esc_html($errors[$error]??'The import could not finish. See server logs.'); ?></p></div><?php endif;?>
      <div class="bg-demo-steps"><div><span class="dashicons dashicons-admin-appearance"></span><strong>1 · Choose your homepage</strong><p>Choose from four layouts. You can switch later.</p></div><div><span class="dashicons dashicons-images-alt2"></span><strong>2 · Add example website content</strong><p>18 editable travel entries, original artwork, useful pages, stories and menus.</p></div><div><span class="dashicons dashicons-edit-page"></span><strong>3 · Make it yours</strong><p>Edit text, prices and photos. Demo bookings stay disabled until you confirm real details.</p></div></div>
      <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="bg_import_demo" /><?php wp_nonce_field('bg_import_demo'); ?>
        <h2>Select your homepage</h2><div class="bg-demo-choices">
        <?php foreach($definitions as $style=>$data):?><label><input type="radio" name="bg_demo_style" value="<?php echo esc_attr($style); ?>" <?php checked($style,'travel'); ?>><strong><?php echo esc_html($data[0]); ?></strong><span><?php echo esc_html($data[2]); ?></span></label><?php endforeach; ?>
        </div>
        <h2>What will be installed?</h2><p>Four homepage templates, 6 journeys, 6 destinations, 6 experiences, optional sample travel dates, 12 locally packaged illustrative images, About / Contact / FAQ / Custom Trip / Journal pages, three editorial articles and a navigation menu.</p>
        <p><label><input type="checkbox" name="bg_demo_images" value="1" checked> Add original illustrated travel images to Media Library</label></p>
        <p><label><input type="checkbox" name="bg_demo_install_plugin" value="1" checked> Install/activate the bundled Bangkok Guru Bookings plugin if it is missing (administrator permission required)</label></p>
        <p><strong>Safe demo mode:</strong> Sample departure dates, prices and seat counts are for preview only. The demo listings cannot accept reservations or payments until you review each one and explicitly enable live booking.</p>
        <?php submit_button($done?'Recheck Demo / Choose Homepage':'Yes — Install My Demo Website','primary large'); ?>
      </form>
      <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="bg_demo_skip"><?php wp_nonce_field('bg_demo_skip'); ?><?php submit_button('No thanks — start with an empty website','secondary'); ?></form>
      <hr><h2>After setup</h2><p><a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener">View website ↗</a> · <a href="<?php echo esc_url(admin_url('edit.php?post_type=bg_journey')); ?>">Edit journeys</a> · <a href="<?php echo esc_url(admin_url('edit.php?post_type=bg_experience')); ?>">Edit experiences</a> · <a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>">Edit pages</a> · <a href="<?php echo esc_url(admin_url('customize.php')); ?>">Change logo & colours</a></p>
      <?php if(did_action('elementor/loaded')): ?><hr><h2>Optional Elementor Global Styles</h2><form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="bg_sync_elementor_globals"><?php wp_nonce_field('bg_sync_elementor_globals');submit_button('Sync Brand Colors & Fonts to Elementor','secondary'); ?></form><?php endif; ?>
      <?php if(has_custom_logo()): ?><form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="bg_match_logo_colors"><?php wp_nonce_field('bg_match_logo_colors');submit_button('Match Color Palette to My Logo','secondary'); ?></form><?php endif; ?>
    </div>
    <style>.bg-demo-wizard{max-width:1120px}.bg-demo-steps,.bg-demo-choices{display:grid;grid-template-columns:repeat(auto-fit,minmax(215px,1fr));gap:14px;margin:20px 0}.bg-demo-steps>div{background:#fff;border:1px solid #d7dfea;border-radius:14px;padding:20px}.bg-demo-steps strong{display:block;margin:8px 0}.bg-demo-steps p,.bg-demo-choices span{color:#64748b}.bg-demo-choices label{display:flex;flex-direction:column;align-items:flex-start;gap:12px;padding:18px;background:#fff;border-radius:12px;border:2px solid #d6e1ef;cursor:pointer}.bg-demo-choices label:has(input:checked){border-color:#0b55b5;background:#f3f7ff}.bg-demo-choices input{margin:0}</style>
    <?php
}

function bg_demo_page($slug,$title,$content,$option) {
    $id=(int)get_option($option,0);
    if($id && get_post($id))return $id; // Never overwrite or revive edited or trashed content.
    $existing=get_page_by_path($slug,OBJECT,'page');
    if($existing && !str_starts_with($slug,'bangkok-guru-home-')) {
        // Reuse existing Contact/About/etc. without changing their content.
        update_option($option,$existing->ID,false);return (int)$existing->ID;
    }
    if($existing && get_post_meta($existing->ID,'_bg_demo_managed',true)) {update_option($option,$existing->ID,false);return (int)$existing->ID;}
    $id=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>$content),true);
    if(is_wp_error($id))return 0;
    update_post_meta($id,'_bg_demo_managed','1');update_option($option,$id,false);
    return (int)$id;
}
function bg_demo_image($name,$parent_id,$label) {
    if(!preg_match('/^[a-z0-9-]+$/',$name))return 0;
    $existing=(int)get_option('bg_demo_media_'.$name,0);
    if($existing && get_post($existing))return $existing;
    $source=BG_DIR.'/assets/demo/'.$name.'.jpg';
    if(!is_file($source))return 0;
    $contents=file_get_contents($source);
    if(!$contents)return 0;
    $uploaded=wp_upload_bits('bangkok-guru-demo-'.$name.'.jpg',null,$contents);
    if(!empty($uploaded['error']))return 0;
    $id=wp_insert_attachment(array('post_mime_type'=>'image/jpeg','post_title'=>'Bangkok Guru · '.$label.' (illustration)','post_status'=>'inherit','post_content'=>'Illustrative original travel artwork for demo use. Replace with your licensed travel photography.'),$uploaded['file'],$parent_id,true);
    if(is_wp_error($id))return 0;
    require_once ABSPATH.'wp-admin/includes/image.php';
    wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$uploaded['file']));
    update_post_meta($id,'_wp_attachment_image_alt','Illustrated travel scene: '.$label);
    update_post_meta($id,'_bg_demo_managed','1');update_option('bg_demo_media_'.$name,$id,false);
    return (int)$id;
}
function bg_demo_import_entry($entry,$index,$with_images) {
    $types=array('bg_journey','bg_destination','bg_experience');
    $type=$entry['type']??'';if(!in_array($type,$types,true))return 0;
    $slug=sanitize_title($entry['slug']??'');if(!$slug)return 0;
    $option='bg_demo_entry_'.$type.'_'.$slug;
    $id=(int)get_option($option,0);
    if($id && get_post($id))return $id;
    // Upgrade only recognisable, untouched pre-v0.13 placeholder records.
    $prior=array_search($type,$types,true);$legacy_index=(int)$index;
    $legacy_id=$legacy_index<3?(int)get_option('bg_demo_sample_'.$type.'_'.$legacy_index,0):0;
    $legacy=get_post($legacy_id);
    $legacy_marker=$legacy && get_post_meta($legacy_id,'_bg_badge',true)==='SAMPLE — EDIT ME' && strpos($legacy->post_title,'Sample · ')===0 && strpos($legacy->post_content,'This is an illustrative Bangkok Guru starter entry.')!==false;
    $excerpt=sanitize_text_field($entry['subtitle']??'');
    $parts=array('<p>'.esc_html($excerpt).'</p>','<h2>Experience overview</h2><p>This is an editorial sample itinerary intended to show the look and workflow of Bangkok Guru. Route, services, pricing, dates and images must be verified and replaced before you offer it to travellers.</p>','<h2>What makes it special</h2><ul>');
    foreach(($entry['highlights']??array()) as $h)$parts[]='<li>'.esc_html($h).'</li>';
    $parts[]='</ul><h2>Suggested itinerary</h2><ol>';
    foreach(($entry['route']??array()) as $stop)$parts[]='<li>'.esc_html($stop).'</li>';
    $parts[]='</ol><p><em>All descriptions and prices are illustrative. No actual reservation or guide is provided by this demo.</em></p>';
    $content=implode("\n",$parts);
    if($legacy_marker){
        $id=wp_update_post(array('ID'=>$legacy_id,'post_title'=>$entry['title'],'post_excerpt'=>$excerpt,'post_content'=>$content,'post_name'=>'bg-demo-'.$slug),true);
    } else {
        $id=wp_insert_post(array('post_type'=>$type,'post_name'=>'bg-demo-'.$slug,'post_title'=>$entry['title'],'post_status'=>'publish','post_excerpt'=>$excerpt,'post_content'=>$content),true);
    }
    if(is_wp_error($id))return 0;
    $id=(int)$id;update_option($option,$id,false);
    update_post_meta($id,'_bg_demo_managed','1');update_post_meta($id,'_bg_demo_item','1');
    $metas=array('_bg_subtitle'=>$excerpt,'_bg_location'=>$entry['location']??'','_bg_duration'=>$entry['duration']??'','_bg_price'=>max(0,(float)($entry['price']??0)),'_bg_deposit'=>max(0,(float)($entry['deposit']??0)),'_bg_balance_days'=>14,'_bg_badge'=>'DEMO · EDIT ME','_bg_layout'=>in_array($entry['layout']??'',array('column','story','route'),true)?$entry['layout']:'column');
    foreach(array('highlights'=>'_bg_highlights','route'=>'_bg_itinerary','included'=>'_bg_included','excluded'=>'_bg_excluded') as $field=>$key)$metas[$key]=implode("\n",array_map('sanitize_text_field',(array)($entry[$field]??array())));
    foreach($metas as $key=>$value)update_post_meta($id,$key,$value);
    $departures=array();
    foreach((array)($entry['days']??array()) as $offset){
        $offset=(int)$offset;
        if($offset<7||$offset>365)continue;
        $departures[]=array('date'=>wp_date('Y-m-d',time()+$offset*DAY_IN_SECONDS),'seats'=>10+($offset%9),'price'=>max(0,(float)($entry['price']??0)));
    }
    update_post_meta($id,'_bg_departures',$departures);
    if(function_exists('bg_inventory_sync'))bg_inventory_sync($id,$departures);
    if($with_images && !has_post_thumbnail($id)){
        $image=bg_demo_image(sanitize_key($entry['cover']??''),$id,$entry['title']);
        if($image)set_post_thumbnail($id,$image);
    }
    return $id;
}
function bg_demo_build_nav($page_ids) {
    $menu_id=(int)get_option('bg_demo_nav_menu',0);
    if(!$menu_id){
        $existing=wp_get_nav_menu_object('Bangkok Guru Demo Navigation');
        $menu_id=$existing?(int)$existing->term_id:wp_create_nav_menu('Bangkok Guru Demo Navigation');
        if(is_wp_error($menu_id))return;
        update_option('bg_demo_nav_menu',$menu_id,false);
    }
    if(!wp_get_nav_menu_items($menu_id)){
        foreach(array('Home'=>'home','Journeys'=>'journeys','Destinations'=>'destinations','Experiences'=>'experiences','About'=>'about','Contact'=>'contact','My Bookings'=>'bookings') as $label=>$role){
            if(in_array($role,array('journeys','destinations','experiences'),true)){
                $type=array('journeys'=>'bg_journey','destinations'=>'bg_destination','experiences'=>'bg_experience')[$role];
                wp_update_nav_menu_item($menu_id,0,array('menu-item-title'=>$label,'menu-item-url'=>get_post_type_archive_link($type),'menu-item-status'=>'publish','menu-item-type'=>'custom'));
            }else{
                $id=(int)($page_ids[$role]??0);
                if($id)wp_update_nav_menu_item($menu_id,0,array('menu-item-title'=>$label,'menu-item-object'=>'page','menu-item-object-id'=>$id,'menu-item-type'=>'post_type','menu-item-status'=>'publish'));
            }
        }
    }
    $locations=(array)get_theme_mod('nav_menu_locations',array());
    if(empty($locations['primary'])){$locations['primary']=$menu_id;set_theme_mod('nav_menu_locations',$locations);}
}
/** Runs only after explicit user action, or via the bundled local WP-CLI demo command. */
function bg_demo_seed_site($style='travel',$with_images=true) {
    if(!bg_demo_plugin_ready())return new WP_Error('plugin_missing','Install Bangkok Guru Bookings v0.13 first.');
    $data=bg_demo_site_data();if(is_wp_error($data))return $data;
    $all=bg_demo_definitions();if(!isset($all[$style]))$style='travel';
    $ids=array();$created=0;
    foreach($data['homepages'] as $key=>$meta){
        if(!isset($all[$key]))continue;
        $id=bg_demo_page('bangkok-guru-home-'.$key,'Bangkok Guru — '.$meta['name'],'','bg_demo_homepage_'.$key);
        if(!$id)continue;
        // Only initialise homepage fields on first migration/import; later edits are preserved.
        if(!get_post_meta($id,'_bg_demo_initialized_v013',true)){
            $home_meta=array('_bg_home_style'=>$key,'_bg_hero_title'=>$meta['headline'],'_bg_hero_eyebrow'=>$meta['eyebrow'],'_bg_hero_subtitle'=>$meta['sub'],'_bg_hero_cta'=>$meta['cta']);
            foreach($home_meta as $field=>$value){if(!get_post_meta($id,$field,true))update_post_meta($id,$field,sanitize_text_field($value));}
            update_post_meta($id,'_bg_demo_initialized_v013','1');
        }
        if($with_images&&!has_post_thumbnail($id)){
            $img=bg_demo_image($meta['cover'],$id,$meta['name']);if($img)set_post_thumbnail($id,$img);
        }
        $ids[$key]=$id;
    }
    $page_ids=array('home'=>$ids[$style]??0);
    foreach($data['pages'] as $page){
        $slug=sanitize_title($page['slug']);$id=bg_demo_page($slug,$page['title'],$page['content'],'bg_demo_page_'.$slug);
        if($slug==='about-bangkok-guru')$page_ids['about']=$id;
        if($slug==='contact')$page_ids['contact']=$id;
        $created+=!!$id;
    }
    $booking=get_page_by_path('my-bookings');if($booking)$page_ids['bookings']=(int)$booking->ID;
    foreach($data['items'] as $i=>$entry){
        $index=0;foreach(array_slice($data['items'],0,$i) as $prev)if($prev['type']===$entry['type'])$index++;
        if(bg_demo_import_entry($entry,$index,$with_images))$created++;
    }
    foreach($data['stories']??array() as $entry){
        $slug=sanitize_title($entry['slug']);$option='bg_demo_story_'.$slug;
        if(get_option($option) && get_post((int)get_option($option)))continue;
        $id=wp_insert_post(array('post_type'=>'post','post_title'=>$entry['title'],'post_name'=>'bg-demo-'.$slug,'post_status'=>'publish','post_excerpt'=>$entry['excerpt'],'post_content'=>$entry['content']),true);
        if(!is_wp_error($id)){update_option($option,$id,false);update_post_meta($id,'_bg_demo_managed','1');$created++;}
    }
    bg_demo_build_nav($page_ids);
    if(isset($ids[$style])){update_option('show_on_front','page');update_option('page_on_front',(int)$ids[$style]);}
    // Publishing a privacy policy requires the site owner's real contact and retention terms.
    if(!get_option('wp_page_for_privacy_policy')){
        $privacy=get_page_by_path('privacy-policy');
        if(!$privacy){
            $option='bg_demo_privacy_draft';
            if(!get_option($option)){
                $id=wp_insert_post(array('post_type'=>'page','post_name'=>'privacy-policy','post_title'=>'Privacy Policy — REVIEW BEFORE PUBLISHING','post_status'=>'draft','post_content'=>'<p>DRAFT ONLY: replace this with your own privacy notice before collecting real customer data. Describe business identity, contact, booking records, payment providers, retention periods, cookies and individual rights.</p>'),true);
                if(!is_wp_error($id))update_option($option,$id,false);
            }
        }
    }
    if(get_option('blogdescription')==='Just another WordPress site')update_option('blogdescription','Local stories. Remarkable journeys.');
    update_option('bg_demo_imported_v013',array('at'=>time(),'home'=>$style,'entries'=>$created),false);
    delete_option('bg_demo_wizard_dismissed');
    return array('home'=>$style,'entries'=>$created);
}
function bg_import_demo() {
    if(!current_user_can('manage_options'))wp_die('Not permitted');
    check_admin_referer('bg_import_demo');
    if(!empty($_POST['bg_demo_install_plugin'])){
        $plugin=bg_demo_install_plugin();
        if(is_wp_error($plugin)){error_log('Bangkok Guru demo plugin: '.$plugin->get_error_message());wp_safe_redirect(admin_url('themes.php?page=bangkok-guru-demo&bg_error=plugin'));exit;}
    }
    if(!bg_demo_plugin_ready()){wp_safe_redirect(admin_url('themes.php?page=bangkok-guru-demo&bg_error=missing'));exit;}
    $choice=sanitize_key(wp_unslash($_POST['bg_demo_style']??'travel'));
    $result=bg_demo_seed_site($choice,!empty($_POST['bg_demo_images']));
    if(is_wp_error($result)){error_log('Bangkok Guru demo: '.$result->get_error_message());wp_safe_redirect(admin_url('themes.php?page=bangkok-guru-demo&bg_error=data'));exit;}
    wp_safe_redirect(add_query_arg('bg_demo_installed','1',admin_url('themes.php?page=bangkok-guru-demo')));exit;
}
add_action('admin_post_bg_import_demo','bg_import_demo');
/** Opt-in Elementor Global Kit setup. Does not overwrite existing other global colors. */
function bg_sync_elementor_globals(){
  if(!current_user_can('manage_options'))wp_die('Not permitted');
  check_admin_referer('bg_sync_elementor_globals');
  $ok=false;
  if(class_exists('\\Elementor\\Plugin')){
    $manager=\Elementor\Plugin::$instance->kits_manager;
    $kit=$manager?$manager->get_active_kit():null;
    if($kit && method_exists($kit,'get_settings') && method_exists($kit,'update_settings')){
      $colors=$kit->get_settings('custom_colors');if(!is_array($colors))$colors=array();
      foreach(bg_color_tokens() as $key=>$spec){
        $id=substr(md5('bangkok-guru-color-'.$key),0,7);$value=get_theme_mod('bg_color_'.$key,$spec[1]);$value=sanitize_hex_color($value)?:$spec[1];
        $colors=array_values(array_filter($colors,function($x)use($id){return !isset($x['id'])||$x['id']!==$id;}));
        $colors[]=array('id'=>$id,'title'=>'Bangkok Guru · '.$spec[0],'color'=>$value);
      }
      $fonts=$kit->get_settings('custom_typography');if(!is_array($fonts))$fonts=array();
      foreach(array('body'=>'Body','heading'=>'Headings','eyebrow'=>'Eyebrow','button'=>'Buttons') as $key=>$label){
        $id=substr(md5('bangkok-guru-font-'.$key),0,7);$family=bg_sanitize_font(get_theme_mod('bg_font_'.$key,'system'));
        $name=$family==='serif'?'Georgia':($family==='mono'?'monospace':'Arial');
        $fonts=array_values(array_filter($fonts,function($x)use($id){return !isset($x['id'])||$x['id']!==$id;}));
        $fonts[]=array('id'=>$id,'title'=>'Bangkok Guru · '.$label,'typography_typography'=>'custom','typography_font_family'=>$name);
      }
      try{$kit->update_settings(array('custom_colors'=>$colors,'custom_typography'=>$fonts));$ok=true;}
      catch(Throwable $e){$ok=false;}
    }
  }
  wp_safe_redirect(add_query_arg('bg_global_synced',$ok?'1':'0',admin_url('themes.php?page=bangkok-guru-demo')));exit;
}
add_action('admin_post_bg_sync_elementor_globals','bg_sync_elementor_globals');

/** Optional color sampling from site owner's original raster custom logo. */
function bg_logo_palette_from_file($path){
  if(!is_string($path)||!is_file($path)||!function_exists('imagecolorat'))return array();
  $info=@getimagesize($path);if(!$info)return array();
  if((int)$info[0]*(int)$info[1]>20000000)return array();
  $type=$info[2];$reader=($type===IMAGETYPE_PNG?'imagecreatefrompng':($type===IMAGETYPE_JPEG?'imagecreatefromjpeg':($type===IMAGETYPE_WEBP?'imagecreatefromwebp':'')));
  if(!$reader||!function_exists($reader))return array();
  $im=@$reader($path);if(!$im)return array();
  $w=imagesx($im);$h=imagesy($im);if($w<4||$h<4){imagedestroy($im);return array();}
  $hist=array();$step=max(1,(int)ceil(max($w,$h)/90));
  for($y=0;$y<$h;$y+=$step){for($x=0;$x<$w;$x+=$step){
    $val=imagecolorat($im,$x,$y);$alpha=($val>>24)&0x7f;if($type===IMAGETYPE_PNG&&$alpha>80)continue;
    $r=($val>>16)&255;$g=($val>>8)&255;$b=$val&255;
    $max=max($r,$g,$b);$min=min($r,$g,$b);$s=$max?($max-$min)/$max:0;
    if($s<.20||$max<35)continue;
    $key=sprintf('%02x%02x%02x',min(255,(int)(round($r/32)*32)),min(255,(int)(round($g/32)*32)),min(255,(int)(round($b/32)*32)));
    $hist[$key]=($hist[$key]??0)+1;
  }}imagedestroy($im);
  arsort($hist);$colors=array();
  foreach(array_keys($hist) as $hex){
    $r=hexdec(substr($hex,0,2));$g=hexdec(substr($hex,2,2));$b=hexdec(substr($hex,4,2));
    $brightness=(.2126*$r+.7152*$g+.0722*$b)/255;
    if(!$colors && $brightness>.65)continue; // Prefer a darker, readable primary.
    if($colors){
      $base=ltrim($colors[0],'#');$d=(abs(hexdec(substr($base,0,2))-$r)+abs(hexdec(substr($base,2,2))-$g)+abs(hexdec(substr($base,4,2))-$b))/765;
      if($d<.28)continue;
    }
    $colors[]='#'.$hex;
    if(count($colors)===2)break;
  }
  return $colors;
}
function bg_match_logo_colors(){
  if(!current_user_can('manage_options'))wp_die('Not permitted');
  check_admin_referer('bg_match_logo_colors');
  $id=(int)get_theme_mod('custom_logo');$path=$id?get_attached_file($id):'';
  $palette=bg_logo_palette_from_file($path);
  if(count($palette)===2){set_theme_mod('bg_color_forest',$palette[0]);set_theme_mod('bg_color_sun',$palette[1]);}
  wp_safe_redirect(add_query_arg('bg_logo_colors',count($palette)===2?'1':'0',admin_url('themes.php?page=bangkok-guru-demo')));exit;
}
add_action('admin_post_bg_match_logo_colors','bg_match_logo_colors');
