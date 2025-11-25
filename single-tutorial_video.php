<?php
get_header();
$video_url = get_post_meta(get_the_ID(), 'youtube_video', true);
$video_embed = wiki_icp_prepare_video_embed($video_url);
$duration = get_post_meta(get_the_ID(), 'video_time', true);
?>

<main class="tutorial-single container">
  <article <?php post_class('tutorial-single-card'); ?>>
    <header>
      <p class="text-xs uppercase tracking-[0.3em] text-slate-400 mb-2"><?php esc_html_e('Tutorial Video', 'wiki-icp'); ?></p>
      <h1 class="text-3xl font-semibold text-slate-900 mb-4"><?php the_title(); ?></h1>
      <?php if ($duration) : ?>
        <div class="tutorial-duration-chip">
          <span><?php esc_html_e('Duration', 'wiki-icp'); ?></span>
          <strong><?php echo esc_html($duration); ?></strong>
        </div>
      <?php endif; ?>
    </header>

    <?php if ($video_embed) : ?>
      <div class="tutorial-video-wrapper">
        <div class="tutorial-video">
          <?php echo $video_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="tutorial-body">
      <?php the_content(); ?>
    </div>
  </article>
</main>

<?php
get_footer();
