<?php
/**
 * Template Name: Homepage Template
 */
get_header(); ?>

<main class="home-shell">
  <section class="hero-section">
    <div class="hero-bg-overlay"></div>
    <div class="container hero-content">
      <?php
      $brand      = get_brand_data();
      $brand_name = isset($brand['name']) ? trim((string) $brand['name']) : '';
      ?>
      <div class="hero-inner">
        <div class="hero-copy">
          <p class="search-eyebrow">
            <?php
            if ($brand_name) {
                printf(
                    /* translators: %s: Brand name */
                    esc_html__('%s Knowledge Base', 'wiki-icp'),
                    esc_html($brand_name)
                );
            } else {
                esc_html_e('Knowledge Base', 'wiki-icp');
            }
            ?>
          </p>
          <h1><?php esc_html_e('Welcome to our help center', 'wiki-icp'); ?></h1>
          <p class="info"><?php esc_html_e('Search across help topics, tutorial videos, and AI-guided answers.', 'wiki-icp'); ?></p>
          <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/knowledge-search/')); ?>">
            <label class="screen-reader-text" for="home-search"><?php esc_html_e('Search the knowledge base', 'wiki-icp'); ?></label>
            <input id="home-search" type="search" placeholder="<?php esc_attr_e('Search…', 'wiki-icp'); ?>" name="q">
            <button class="primary-button" type="submit">
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              <span><?php esc_html_e('Search', 'wiki-icp'); ?></span>
            </button>
          </form>
        </div>
        <div class="hero-visual">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/SVG/help-bot.svg'); ?>" alt="<?php esc_attr_e('Support bot illustration', 'wiki-icp'); ?>">
        </div>
      </div>
    </div>
  </section>

  <section class="home-section container">
    <div class="section-header">
      <p class="search-eyebrow"><?php esc_html_e('Featured Guides', 'wiki-icp'); ?></p>
      <h2><?php esc_html_e('Browse essential resources', 'wiki-icp'); ?></h2>
      <p><?php esc_html_e('Quick entry points into the most-requested support topics.', 'wiki-icp'); ?></p>
    </div>
    <div class="services-grid">
      <?php
      $featured_pages = new WP_Query([
          'post_type'      => 'page',
          'posts_per_page' => 8,
          'orderby'        => 'menu_order',
          'order'          => 'ASC',
          'tax_query'      => [
              [
                  'taxonomy' => 'page_category',
                  'field'    => 'slug',
                  'terms'    => ['featured'],
              ],
          ],
      ]);

      if ($featured_pages->have_posts()) :
          while ($featured_pages->have_posts()) :
              $featured_pages->the_post();
              $excerpt = get_the_excerpt() ?: wp_trim_words(get_the_content(), 25);
              $image_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
              if (!$image_url) {
                  $image_url = get_stylesheet_directory_uri() . '/assets/SVG/help-bot.svg';
              }
              ?>
              <article class="featured-card">
                <div class="featured-card__media">
                  <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>
                <div class="featured-card__body">
                  <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                  <p><?php echo esc_html($excerpt); ?></p>
                  <a class="secondary-button featured-card__cta" href="<?php the_permalink(); ?>">
                    <span><?php esc_html_e('Get started', 'wiki-icp'); ?></span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                  </a>
                </div>
              </article>
          <?php
          endwhile;
          wp_reset_postdata();
      else :
          ?>
          <p class="muted"><?php esc_html_e('Add the "featured" page category to highlight quick links here.', 'wiki-icp'); ?></p>
      <?php endif; ?>
    </div>
  </section>

  <section class="home-section container popular-videos">
    <div class="section-header">
      <p class="search-eyebrow"><?php esc_html_e('Tutorial Library', 'wiki-icp'); ?></p>
      <h2><?php esc_html_e('Browse popular videos', 'wiki-icp'); ?></h2>
      <p><?php esc_html_e('Quick how-tos hand-picked for common support requests.', 'wiki-icp'); ?></p>
    </div>
    <div class="popular-videos-grid">
      <?php
      $video_query = new WP_Query([
          'post_type'      => wiki_icp_get_video_post_types(),
          'posts_per_page' => 6,
          'tax_query'      => [
              [
                  'taxonomy'         => 'category_tutorial_video',
                  'field'            => 'slug',
                  'terms'            => ['featured'],
                  'include_children' => true,
              ],
          ],
      ]);

      if ($video_query->have_posts()) :
          while ($video_query->have_posts()) :
              $video_query->the_post();
              $portal_terms = get_the_terms(get_the_ID(), 'tutorial_video_portal');
              $portal       = (!empty($portal_terms) && !is_wp_error($portal_terms)) ? $portal_terms[0]->name : '';
              ?>
              <article class="search-card">
                <header>
                  <span class="badge"><?php esc_html_e('Tutorial Video', 'wiki-icp'); ?></span>
                  <?php if ($portal) : ?>
                    <span class="portal-label"><?php echo esc_html($portal); ?></span>
                  <?php endif; ?>
                </header>
                <div class="search-card-body">
                  <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                  <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 24)); ?></p>
                </div>
                <footer>
                  <span></span>
                  <a class="button-link" href="<?php the_permalink(); ?>"><?php esc_html_e('Watch video', 'wiki-icp'); ?></a>
                </footer>
              </article>
          <?php
          endwhile;
          wp_reset_postdata();
      else :
          ?>
          <p class="muted"><?php esc_html_e('Tag videos with the "featured" category to highlight them here.', 'wiki-icp'); ?></p>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>
