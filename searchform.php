<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/knowledge-search/')); ?>">
  <label class="screen-reader-text" for="wiki-search"><?php esc_html_e('Search for:', 'wiki-icp'); ?></label>
  <input id="wiki-search" type="search" placeholder="<?php esc_attr_e('Search…', 'wiki-icp'); ?>" value="<?php echo esc_attr(isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : ''); ?>" name="q">
  <input class="secondary-button" type="submit" value="<?php esc_attr_e('Search', 'wiki-icp'); ?>">
</form>
