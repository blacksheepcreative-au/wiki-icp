<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://kit.fontawesome.com/977ae106ed.js" crossorigin="anonymous"></script>
  <?php wp_head(); ?>
</head>
<body <?php body_class('antialiased'); ?>>
<?php wp_body_open(); ?>

<?php
$brand = get_brand_data();
$menu_base_args = [
    'theme_location' => 'primary-menu',
    'container'      => false,
    'menu_class'     => 'menu primary-menu',
    'fallback_cb'    => false,
];
$desktop_menu = wp_nav_menu(array_merge($menu_base_args, [
    'echo'    => false,
    'menu_id' => 'primary-menu-desktop',
]));
$mobile_menu = wp_nav_menu(array_merge($menu_base_args, [
    'echo'    => false,
    'menu_id' => 'primary-menu-mobile',
]));
?>

<header class="site-header" data-site-header>
  <div class="header-primary">
    <div class="container nav-row">
      <div class="nav-left">
        <a class="logo" href="<?php echo esc_url(home_url('/')); ?>">
          <img src="<?php echo esc_url($brand['logo']); ?>" alt="<?php echo esc_attr($brand['name']); ?>">
          <span class="logo-tagline">
            <span class="logo-divider">|</span>
            <span><?php esc_html_e('Help Center', 'wiki-icp'); ?></span>
          </span>
        </a>
      </div>

      <nav
        class="primary-nav primary-nav-desktop"
        aria-label="<?php esc_attr_e('Primary menu', 'wiki-icp'); ?>"
      >
        <?php echo $desktop_menu ?: ''; ?>
      </nav>

      <div class="nav-actions">
        <button
          class="search-toggle desktop-search-toggle"
          type="button"
          aria-expanded="false"
          aria-controls="header-search-panel"
          data-search-toggle
        >
          <span class="screen-reader-text"><?php esc_html_e('Toggle search', 'wiki-icp'); ?></span>
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        </button>
        
      </div>
    </div>
  </div>

  <div class="header-secondary">
    <div class="container mobile-controls" aria-label="<?php esc_attr_e('Mobile controls', 'wiki-icp'); ?>">
      <button
        class="mobile-control-button"
        type="button"
        aria-expanded="false"
        aria-controls="mobile-menu-panel"
        data-nav-toggle
      >
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
        <span><?php esc_html_e('Menu', 'wiki-icp'); ?></span>
      </button>
      <button
        class="mobile-control-button"
        type="button"
        aria-expanded="false"
        aria-controls="header-search-panel"
        data-search-toggle
      >
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <span><?php esc_html_e('Search', 'wiki-icp'); ?></span>
      </button>
    </div>

    <div class="header-mobile-panel" id="mobile-menu-panel" data-nav-panel>
      <div class="mobile-panel-header">
        <div class="mobile-panel-title">
          <i class="fa-solid fa-border-all" aria-hidden="true"></i>
          <span><?php esc_html_e('Menu', 'wiki-icp'); ?></span>
        </div>
        <button type="button" class="mobile-panel-close" data-nav-close aria-label="<?php esc_attr_e('Close menu', 'wiki-icp'); ?>">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </div>
      <div class="mobile-panel-inner">
        <nav aria-label="<?php esc_attr_e('Mobile menu', 'wiki-icp'); ?>">
          <?php echo $mobile_menu ?: ''; ?>
        </nav>
        
      </div>
    </div>

    <div class="header-search-panel" id="header-search-panel" data-search-panel>
      <div class="container search-panel-inner">
        <?php get_search_form(); ?>
      </div>
    </div>
  </div>
</header>
