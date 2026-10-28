<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function bg_setup() {
    load_theme_textdomain( 'bangkok-guru', BG_DIR . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'custom-logo', array( 'height' => 84, 'width' => 280, 'flex-height' => true, 'flex-width' => true ) );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'align-wide' );
    register_nav_menus( array( 'primary' => __( 'Primary Navigation', 'bangkok-guru' ), 'footer' => __( 'Footer Navigation', 'bangkok-guru' ) ) );
    add_image_size( 'bg-card', 760, 520, true );
}
add_action( 'after_setup_theme', 'bg_setup' );
function bg_assets() {
    wp_enqueue_style('bangkok-guru',get_stylesheet_uri(),array(),BG_VERSION);
    wp_enqueue_style('bangkok-guru-main',BG_URI.'/assets/css/theme.css',array('bangkok-guru'),BG_VERSION);
    $post=get_queried_object();
    $is_custom=is_singular(array('bg_journey','bg_destination','bg_experience'));
    $is_archive=is_post_type_archive(array('bg_journey','bg_destination','bg_experience'));
    $elementor=is_object($post)&&!empty($post->ID)&&get_post_meta($post->ID,'_elementor_edit_mode',true)==='builder';
    $enquiry=is_object($post)&&!empty($post->post_content)&&has_shortcode($post->post_content,'bangkok_guru_enquiry');
    if(is_front_page()||$elementor)wp_enqueue_style('bangkok-guru-home',BG_URI.'/assets/css/home.css',array('bangkok-guru-main'),BG_VERSION);
    if($is_custom||is_page()||is_single()||is_search()||is_404())wp_enqueue_style('bangkok-guru-entry',BG_URI.'/assets/css/entry.css',array('bangkok-guru-main'),BG_VERSION);
    if(is_front_page()||$is_custom||$is_archive||$elementor)wp_enqueue_style('bangkok-guru-cards',BG_URI.'/assets/css/cards.css',array('bangkok-guru-main'),BG_VERSION);
    if(is_front_page()||$is_custom||$elementor)wp_enqueue_style('bangkok-guru-departures',BG_URI.'/assets/css/departures.css',array('bangkok-guru-main'),BG_VERSION);
    if($is_custom||$enquiry||$elementor||is_front_page())wp_enqueue_style('bangkok-guru-form',BG_URI.'/assets/css/form.css',array('bangkok-guru-main'),BG_VERSION);
    if(is_rtl())wp_enqueue_style('bangkok-guru-rtl',BG_URI.'/assets/css/rtl.css',array('bangkok-guru-main'),BG_VERSION);
    wp_enqueue_script('bangkok-guru',BG_URI.'/assets/js/theme.js',array(),BG_VERSION,array('in_footer'=>true,'strategy'=>'defer'));
}
add_action( 'wp_enqueue_scripts', 'bg_assets' );
function bg_body_classes( $classes ) { $classes[] = 'bg-site'; return $classes; }
add_filter( 'body_class', 'bg_body_classes' );
function bg_excerpt_length() { return 19; }
add_filter( 'excerpt_length', 'bg_excerpt_length', 99 );
function bg_pagination() { the_posts_pagination( array('mid_size'=>1,'prev_text'=>'←','next_text'=>'→') ); }
function bg_money( $amount ) {
    $amount = (float) $amount;
    if ( function_exists( 'wc_price' ) ) { return wc_price( $amount ); }
    return '<span class="amount">฿' . esc_html( number_format_i18n( $amount, 0 ) ) . '</span>';
}
function bg_hero_background( $post_id ) {
    if ( has_post_thumbnail( $post_id ) ) {
        $url = get_the_post_thumbnail_url( $post_id, 'large' );
        return ' style="--bg-featured:url(' . esc_url( $url ) . ')"';
    }
    return '';
}
function bg_card( $post_id ) {
    $type = get_post_type($post_id);
    $subtitle = function_exists('bgx_local_meta')?bgx_local_meta($post_id,'_bg_subtitle'):get_post_meta($post_id,'_bg_subtitle',true);
    $price = get_post_meta( $post_id, '_bg_price', true );
    $duration = get_post_meta( $post_id, '_bg_duration', true );
    $badge = get_post_meta($post_id,'_bg_badge',true);
    ?>
    <article class="bg-trip-card">
        <a class="bg-card-media" href="<?php echo esc_url(get_permalink($post_id)); ?>" aria-label="<?php echo esc_attr(get_the_title($post_id)); ?>">
            <?php if ( has_post_thumbnail($post_id) ) { echo get_the_post_thumbnail($post_id, 'bg-card', array('loading'=>'lazy','decoding'=>'async','sizes'=>'(max-width: 680px) 100vw, (max-width: 1100px) 50vw, 33vw')); } else { ?><span class="bg-card-placeholder" aria-hidden="true">✦</span><?php } ?>
            <span class="bg-card-tag"><?php echo esc_html($badge ?: ( $type === 'bg_journey' ? __('Journey','bangkok-guru') : ($type === 'bg_destination' ? __('Destination','bangkok-guru') : __('Experience','bangkok-guru')) )); ?></span>
        </a>
        <div class="bg-card-body">
            <?php if ($duration) { ?><span class="bg-eyebrow"><?php echo esc_html($duration); ?></span><?php } ?>
            <h3><a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(function_exists('bgx_local_title')?bgx_local_title($post_id):get_the_title($post_id)); ?></a></h3>
            <?php if ($subtitle) { ?><p><?php echo esc_html($subtitle); ?></p><?php } ?>
            <div class="bg-card-bottom"><span><?php if ($price !== '') { echo wp_kses_post(bg_money($price)); } ?></span><a href="<?php echo esc_url(get_permalink($post_id)); ?>" aria-label="<?php esc_attr_e('Explore trip','bangkok-guru'); ?>">↗</a></div>
        </div>
    </article>
    <?php
}
function bg_cards( $post_type = 'bg_journey', $limit = 3 ) {
    $q = new WP_Query( array('post_type'=>$post_type,'posts_per_page'=>absint($limit),'post_status'=>'publish','no_found_rows'=>true) );
    if ($q->have_posts()) { echo '<div class="bg-card-grid">'; while ($q->have_posts()) { $q->the_post(); bg_card(get_the_ID()); } echo '</div>'; wp_reset_postdata(); }
    else { echo '<p class="bg-muted">' . esc_html__('No trips yet. Add your first trip in the Dashboard.','bangkok-guru') . '</p>'; }
}
function bg_home_link( $slug ) { $p = get_page_by_path('home-'.$slug); return $p ? get_permalink($p) : home_url('/'); }
