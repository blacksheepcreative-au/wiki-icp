<?php get_header(); ?>

<main id="main-content" class="site-main container">
  <?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
      <article <?php post_class('site-article'); ?>>
        <h1 class="entry-title"><?php the_title(); ?></h1>
        <div class="entry-content">
          <?php the_content(); ?>
        </div>
      </article>
    <?php endwhile; ?>
    <?php the_posts_navigation(); ?>
  <?php else : ?>
    <p><?php esc_html_e('No posts found.', 'wiki-icp'); ?></p>
  <?php endif; ?>
</main>

<?php get_footer(); ?>
