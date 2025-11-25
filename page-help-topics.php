<?php
/**
 * Template Name: Help Topics Directory
 */

get_header();

$taxonomy_map = [
    WIKI_ICP_PORTAL_TAXONOMY => __('Portals', 'wiki-icp'),
    'help_topic_type'        => __('Topic Types', 'wiki-icp'),
    'help_topic_style'       => __('Styles', 'wiki-icp'),
    'glass_style'            => __('Glass Styles', 'wiki-icp'),
    'help_topic_grade'       => __('Grades', 'wiki-icp'),
];

$current_filters = [];
if (!empty($_GET['topic_filter']) && is_array($_GET['topic_filter'])) {
    foreach ($_GET['topic_filter'] as $taxonomy => $slugs) {
        if (!isset($taxonomy_map[$taxonomy]) || !is_array($slugs)) {
            continue;
        }
        $clean = array_filter(array_map('sanitize_title', $slugs));
        if ($clean) {
            $current_filters[$taxonomy] = $clean;
        }
    }
}

$tax_query = [];
foreach ($current_filters as $taxonomy => $slugs) {
    $tax_query[] = [
        'taxonomy' => $taxonomy,
        'field'    => 'slug',
        'terms'    => $slugs,
    ];
}
if (count($tax_query) > 1) {
    $tax_query = array_merge(['relation' => 'AND'], $tax_query);
}

$topics_query = new WP_Query([
    'post_type'      => 'help_topic',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => max(1, get_query_var('paged', 1)),
    'tax_query'      => $tax_query ?: null,
]);

$cards = [];
if ($topics_query->have_posts()) {
    while ($topics_query->have_posts()) {
        $topics_query->the_post();
        $cards[] = wiki_icp_format_help_topic_result(get_the_ID());
    }
    wp_reset_postdata();
}
?>

<main class="help-topic-layout container">
  <div class="help-topic-main">
    <section class="help-topic-article">
      <p class="search-eyebrow"><?php esc_html_e('Help Topics Directory', 'wiki-icp'); ?></p>
      <h1><?php the_title(); ?></h1>
      <div class="help-topic-content">
        <?php the_content(); ?>
      </div>
    </section>

    <section class="related-topics">
      <div class="section-header">
        <h2><?php esc_html_e('All help topics', 'wiki-icp'); ?></h2>
        <p><?php esc_html_e('Use the filters to find the articles you need.', 'wiki-icp'); ?></p>
      </div>
      <div class="search-results">
        <?php if ($cards) : ?>
          <?php foreach ($cards as $card) : ?>
            <article class="search-card">
              <header>
                <span class="badge"><?php echo esc_html($card['typeLabel']); ?></span>
                <?php if (!empty($card['portal'])) : ?>
                  <span class="portal-label"><?php echo esc_html($card['portal']); ?></span>
                <?php endif; ?>
              </header>
              <div class="search-card-body">
                <h3><a href="<?php echo esc_url($card['link']); ?>"><?php echo esc_html($card['title']); ?></a></h3>
                <p><?php echo wp_kses_post(wiki_icp_render_text_with_icons($card['excerpt'])); ?></p>
              </div>
              <footer>
                <span></span>
                <a class="button-link" href="<?php echo esc_url($card['link']); ?>"><?php echo esc_html($card['cta']); ?></a>
              </footer>
            </article>
          <?php endforeach; ?>
        <?php else : ?>
          <p class="muted"><?php esc_html_e('No help topics match these filters yet.', 'wiki-icp'); ?></p>
        <?php endif; ?>
      </div>

      <?php if ($topics_query->max_num_pages > 1) : ?>
        <div class="pagination">
          <?php
          echo paginate_links([
              'total'   => $topics_query->max_num_pages,
              'current' => max(1, get_query_var('paged', 1)),
          ]);
          ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <aside class="help-topic-sidebar">
    <form class="topic-filter-form" method="get" action="<?php echo esc_url(get_permalink()); ?>">
      <h3><?php esc_html_e('Filter help topics', 'wiki-icp'); ?></h3>
      <?php foreach ($taxonomy_map as $taxonomy => $label) :
          $terms = get_terms([
              'taxonomy'   => $taxonomy,
              'hide_empty' => false,
          ]);
          if (empty($terms) || is_wp_error($terms)) {
              continue;
          }
          $selected = isset($current_filters[$taxonomy]) ? $current_filters[$taxonomy] : [];
          ?>
        <fieldset class="topic-filter-group">
          <legend><?php echo esc_html($label); ?></legend>
          <?php foreach ($terms as $term) : ?>
            <label>
              <input
                type="checkbox"
                name="topic_filter[<?php echo esc_attr($taxonomy); ?>][]"
                value="<?php echo esc_attr($term->slug); ?>"
                <?php checked(in_array($term->slug, $selected, true)); ?>
              >
              <span><?php echo esc_html($term->name); ?></span>
            </label>
          <?php endforeach; ?>
        </fieldset>
      <?php endforeach; ?>
      <div class="topic-filter-actions">
        <button type="submit" class="secondary-button"><?php esc_html_e('Apply filters', 'wiki-icp'); ?></button>
        <?php if ($current_filters) : ?>
          <a class="muted" href="<?php echo esc_url(get_permalink()); ?>"><?php esc_html_e('Clear all', 'wiki-icp'); ?></a>
        <?php endif; ?>
      </div>
    </form>
  </aside>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var filterForms = document.querySelectorAll('.topic-filter-form');
  filterForms.forEach(function (form) {
    form.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        form.requestSubmit();
      });
    });
  });
});
</script>

<?php get_footer(); ?>
