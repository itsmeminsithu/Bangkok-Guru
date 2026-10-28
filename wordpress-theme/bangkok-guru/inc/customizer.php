<?php
if(!defined('ABSPATH'))exit;
function bg_color_tokens(){return array(
  'ink'=>array('Ink','#182339'), 'paper'=>array('Paper','#f7f9fe'), 'surface'=>array('Surface','#ffffff'), 'forest'=>array('Royal Blue','#0854bc'),
  'sun'=>array('Sky Accent','#67b7ff'), 'muted'=>array('Muted text','#596b84'), 'line'=>array('Border','#e1e9f4'), 'night'=>array('Night surface','#101d32')
);}
function bg_customize($wp_customize){
  $wp_customize->add_section('bg_design',array('title'=>'Bangkok Guru · Design System','priority'=>31,'description'=>'Eight site-wide light-theme colors, font roles, page width, side gutters, section spacing and corner radius. Elementor widgets inherit these CSS tokens.')); 
  foreach(bg_color_tokens() as $key=>$spec){$id='bg_color_'.$key;$wp_customize->add_setting($id,array('default'=>$spec[1],'sanitize_callback'=>'sanitize_hex_color','transport'=>'refresh'));$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize,$id,array('label'=>$spec[0],'section'=>'bg_design')));}
  foreach(array('width'=>array('Page width (px)',1240,900,1700),'gutter'=>array('Side gutter (px)',28,12,90),'section'=>array('Section spacing (px)',92,36,160),'radius'=>array('Card corner radius (px)',24,0,60)) as $key=>$def){
    $id='bg_design_'.$key;$wp_customize->add_setting($id,array('default'=>$def[1],'sanitize_callback'=>'absint'));$wp_customize->add_control($id,array('label'=>$def[0],'type'=>'number','section'=>'bg_design','input_attrs'=>array('min'=>$def[2],'max'=>$def[3])));
  }
  foreach(array('body'=>'Body','heading'=>'Headings','eyebrow'=>'Eyebrows','button'=>'Buttons') as $key=>$label){$id='bg_font_'.$key;$wp_customize->add_setting($id,array('default'=>'system','sanitize_callback'=>'bg_sanitize_font'));$wp_customize->add_control($id,array('label'=>$label.' font role','type'=>'select','section'=>'bg_design','choices'=>array('system'=>'System Sans (private)','serif'=>'System Serif (private)','mono'=>'System Monospace (private)')));}
  $wp_customize->add_section('bg_brand_content',array('title'=>'Bangkok Guru · Header & Footer','priority'=>32));
  $txt=array(
    'bg_announcement'=>array('Announcement','Discover Bangkok and beyond, your way.'),
    'bg_header_cta'=>array('Header action label','Explore trips'),
    'bg_brand_tagline'=>array('Small logo tagline','THE CITY. THE JOURNEY.'),
    'bg_footer_intro'=>array('Footer introduction','Beautiful journeys, thoughtfully planned. Discover Thailand with a little more curiosity.'),
    'bg_footer_note'=>array('Footer note','Go somewhere new. Come back with a story.')
  );
  foreach($txt as $id=>$info){$wp_customize->add_setting($id,array('default'=>$info[1],'sanitize_callback'=>'sanitize_text_field'));$wp_customize->add_control($id,array('label'=>$info[0],'section'=>'bg_brand_content','type'=>'text'));}
  $wp_customize->add_setting('bg_self_host_font_url',array('default'=>'','sanitize_callback'=>'esc_url_raw'));$wp_customize->add_control('bg_self_host_font_url',array('section'=>'bg_design','label'=>'Optional self-hosted font WOFF2 URL (Media Library on this site)','type'=>'url','description'=>'No remote Google Fonts requests. Use only a properly licensed font hosted on your WordPress domain.'));
}
add_action('customize_register','bg_customize');
function bg_sanitize_font($s){return in_array($s,array('system','serif','mono'),true)?$s:'system';}
function bg_dynamic_styles(){
 $css=':root{';foreach(bg_color_tokens() as $key=>$spec){$value=get_theme_mod('bg_color_'.$key,$spec[1]);$css.='--bg-'.esc_attr($key).':'.(sanitize_hex_color($value)?:$spec[1]).';';}
 foreach(array('width'=>array(1240,900,1700),'gutter'=>array(28,12,90),'section'=>array(92,36,160),'radius'=>array(24,0,60)) as $key=>$limits){$n=min($limits[2],max($limits[1],absint(get_theme_mod('bg_design_'.$key,$limits[0]))));$css.='--bg-'.$key.':'.$n.'px;';}
 $fonts=array('system'=>'-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif','serif'=>'Georgia,"Times New Roman",serif','mono'=>'ui-monospace,SFMono-Regular,Consolas,monospace');
 foreach(array('body','heading','eyebrow','button') as $role){$selected=bg_sanitize_font(get_theme_mod('bg_font_'.$role,'system'));$css.='--bg-font-'.$role.':'.$fonts[$selected].';';}
 $css.='}';
 $fonturl=get_theme_mod('bg_self_host_font_url','');
 if($fonturl){$host=wp_parse_url($fonturl,PHP_URL_HOST);$sitehost=wp_parse_url(home_url(),PHP_URL_HOST);if($host===$sitehost && preg_match('/\.woff2(?:\?.*)?$/i',$fonturl)){$css.='@font-face{font-family:"BGSelfHosted";src:url("'.esc_url_raw($fonturl).'") format("woff2");font-display:swap} :root{--bg-font-body:"BGSelfHosted",sans-serif;--bg-font-heading:"BGSelfHosted",sans-serif;}';}}
 wp_add_inline_style('bangkok-guru-main',$css);
}
add_action('wp_enqueue_scripts','bg_dynamic_styles',30);
