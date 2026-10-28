<?php
get_header();
$filters=bg_theme_archive_filters();
?>
<main id="main" class="bg-container bg-page">
  <div class="bg-page-heading"><span class="bg-eyebrow">EXPLORE THAILAND</span><h1><?php echo esc_html(post_type_archive_title('',false)); ?></h1><p><?php esc_html_e('Discover journeys and experiences that suit your plans.','bangkok-guru'); ?></p></div>
  <form class="bg-archive-filters" action="<?php echo esc_url(get_post_type_archive_link(get_query_var('post_type'))); ?>" method="get" role="search">
    <label><?php esc_html_e('Search','bangkok-guru'); ?><input type="search" name="bg_keyword" value="<?php echo esc_attr($filters['keyword']); ?>" placeholder="Search trips"></label>
    <label><?php esc_html_e('Destination','bangkok-guru'); ?><input name="bg_place" value="<?php echo esc_attr($filters['place']); ?>" placeholder="Bangkok, Chiang Mai…"></label>
    <label><?php esc_html_e('Max price','bangkok-guru'); ?><input type="number" min="0" max="10000000" name="bg_max_price" value="<?php echo $filters['max_price']?esc_attr($filters['max_price']):''; ?>" placeholder="Any budget"></label>
    <label><?php echo esc_html(function_exists('bgx_t')?bgx_t('From date'):'From date'); ?><input type="date" name="bg_from_date" value="<?php echo esc_attr($filters['min_date']??''); ?>"></label>
    <label><?php echo esc_html(function_exists('bgx_t')?bgx_t('Until date'):'Until date'); ?><input type="date" name="bg_to_date" value="<?php echo esc_attr($filters['max_date']??''); ?>"></label>
    <label><?php echo esc_html(function_exists('bgx_t')?bgx_t('Travellers'):'Travellers'); ?><input type="number" min="1" max="20" name="bg_seats" value="<?php echo esc_attr($filters['seats']??1); ?>"></label>
    <label class="bg-checkbox"><input type="checkbox" name="bg_available" value="1" <?php checked(!empty($filters['available'])); ?>><?php echo esc_html(function_exists('bgx_t')?bgx_t('Available only'):'Available only'); ?></label>
    <button class="bg-button" type="submit"><?php echo esc_html(function_exists('bgx_t')?bgx_t('Find trips'):'Find trips'); ?> ↗</button>
    <?php if(!empty($_GET['bg_keyword'])||!empty($_GET['bg_place'])||!empty($_GET['bg_max_price'])||!empty($_GET['bg_from_date'])||!empty($_GET['bg_to_date'])||!empty($_GET['bg_available'])){ ?><a class="bg-clear-filters" href="<?php echo esc_url(get_post_type_archive_link(get_query_var('post_type'))); ?>">Clear</a><?php } ?>
  </form>
  <div class="bg-card-grid"><?php if(have_posts()){while(have_posts()){the_post();bg_card(get_the_ID());}}else{echo '<p class="bg-muted">No matching trips. Try a different destination or budget.</p>';} ?></div>
  <?php bg_pagination(); ?>
</main>
<?php get_footer();
