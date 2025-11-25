<?php
get_header();

if (!defined('WIKI_ICP_PORTAL_TAXONOMY')) {
    define('WIKI_ICP_PORTAL_TAXONOMY', 'category_help_topic');
}

$related_cards = [];
if (have_posts()) {
    the_post();
    $current_id = get_the_ID();
    $portal_terms = get_the_terms($current_id, WIKI_ICP_PORTAL_TAXONOMY);
    $portal_ids   = (!empty($portal_terms) && !is_wp_error($portal_terms)) ? wp_list_pluck($portal_terms, 'term_id') : [];

    $related_args = [
        'post_type'      => 'help_topic',
        'post_status'    => 'publish',
        'posts_per_page' => 4,
        'post__not_in'   => [$current_id],
    ];

    if ($portal_ids) {
        $related_args['tax_query'] = [
            [
                'taxonomy' => WIKI_ICP_PORTAL_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $portal_ids,
            ],
        ];
    }

    $related_query = new WP_Query($related_args);
    if ($related_query->have_posts()) {
        while ($related_query->have_posts()) {
            $related_query->the_post();
            $related_cards[] = wiki_icp_format_help_topic_result(get_the_ID());
        }
        wp_reset_postdata();
    }
    rewind_posts();
}
?>

<main class="help-topic-single">
  <?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
      <article class="help-topic-article">
        <p class="search-eyebrow"><?php esc_html_e('Help Topic', 'wiki-icp'); ?></p>
        <h1><?php the_title(); ?></h1>
        <div class="help-topic-meta">
          <?php
          $portal_terms = get_the_terms(get_the_ID(), WIKI_ICP_PORTAL_TAXONOMY);
          if (!empty($portal_terms) && !is_wp_error($portal_terms)) :
              ?>
              <span><?php echo esc_html($portal_terms[0]->name); ?></span>
          <?php endif; ?>
          <span><?php echo esc_html(get_the_date()); ?></span>
        </div>
        <div class="help-topic-content">
          <?php the_content(); ?>
        </div>
      </article>
    <?php endwhile; ?>
  <?php else : ?>
    <p class="muted"><?php esc_html_e('This help topic could not be found.', 'wiki-icp'); ?></p>
  <?php endif; ?>

  <section class="related-topics">
    <div class="section-header">
      <h2><?php esc_html_e('Related help topics', 'wiki-icp'); ?></h2>
      <p><?php esc_html_e('More articles from the same portal.', 'wiki-icp'); ?></p>
    </div>
    <div class="search-results">
      <?php if ($related_cards) : ?>
        <?php foreach ($related_cards as $card) : ?>
          <article class="search-card">
            <header>
              <span class="badge"><?php echo esc_html($card['typeLabel']); ?></span>
              <?php if (!empty($card['portal'])) : ?>
                <span class="portal-label"><?php echo esc_html($card['portal']); ?></span>
              <?php endif; ?>
            </header>
            <div class="search-card-body">
              <h3><a href="<?php echo esc_url($card['link']); ?>"><?php echo esc_html($card['title']); ?></a></h3>
              <p><?php echo esc_html($card['excerpt']); ?></p>
            </div>
            <footer>
              <span></span>
              <a class="button-link" href="<?php echo esc_url($card['link']); ?>"><?php echo esc_html($card['cta']); ?></a>
            </footer>
          </article>
        <?php endforeach; ?>
      <?php else : ?>
        <p class="muted"><?php esc_html_e('No related topics yet.', 'wiki-icp'); ?></p>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>
