<?php
/**
 * ICP Wiki Child functions
 * - Extends ewenique-core with wiki branding, CPTs and assets.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('get_subdomain')) {
    /**
     * Return the first label of the current HTTP host (subdomain) if present.
     */
    function get_subdomain() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $host = explode(':', $host)[0];
        $parts = explode('.', $host);
        return count($parts) > 2 ? $parts[0] : '';
    }
}

/**
 * Enqueue wiki core + brand-specific assets on top of ewenique-core builds.
 */
function wiki_icp_enqueue_assets() {
    $theme_version = wp_get_theme()->get('Version');
    $dir           = get_stylesheet_directory();
    $uri           = get_stylesheet_directory_uri();

    $style_rel  = '/dist/style.css';
    $style_path = $dir . $style_rel;

    if (file_exists($style_path)) {
        wp_enqueue_style(
            'wiki-core-style',
            $uri . $style_rel,
            ['ewenique-core-style'],
            $theme_version
        );
    } else {
        wp_enqueue_style(
            'wiki-core-style',
            get_stylesheet_uri(),
            ['ewenique-core-style'],
            $theme_version
        );
    }

    $legacy_path = $dir . '/legacy-styles.css';
    if (file_exists($legacy_path)) {
        wp_enqueue_style(
            'wiki-legacy-style',
            $uri . '/legacy-styles.css',
            ['wiki-core-style'],
            $theme_version
        );
    }

    $js_rel  = '/dist/main.js';
    $js_path = $dir . $js_rel;
    if (file_exists($js_path)) {
        wp_enqueue_script(
            'wiki-theme-script',
            $uri . $js_rel,
            ['jquery'],
            $theme_version,
            true
        );
        wp_localize_script('wiki-theme-script', 'wikiIcpData', [
            'restUrl' => esc_url_raw(rest_url()),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'wiki_icp_enqueue_assets', 20);

/**
 * Register navigation menus used by wiki templates.
 */
function wiki_icp_register_menus() {
    register_nav_menus([
        'primary-menu' => __('Primary Menu', 'wiki-icp'),
    ]);
}
add_action('after_setup_theme', 'wiki_icp_register_menus');

/**
 * Enable featured images for posts and pages.
 */
add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails', ['post', 'page']);
});

/**
 * Allow adding Font Awesome classes on menu items to render leading icons while keeping the label.
 * Add classes like "fa-solid fa-house" or "fa-regular fa-circle" on a menu item in Appearance > Menus.
 * We strip the FA classes from the <li> to prevent the FA kit from replacing the whole item, and insert
 * an <i> inside the link that the kit can safely swap to SVG.
 */
function wiki_icp_menu_icon_extract_classes($classes) {
    return array_values(array_filter($classes, function ($class) {
        $class = trim((string) $class);
        // Allow FA class prefixes (fa-solid, fa-regular, fa-light, fa-duotone, fa-brands, fa-*)
        return preg_match('/^(fa(s|r|l|b|d)?(-|$)|fa-[a-z0-9])/i', $class);
    }));
}

function wiki_icp_menu_icons($title, $item, $args, $depth) {
    if (($args->theme_location ?? '') !== 'primary-menu') {
        return $title;
    }

    $classes      = is_array($item->classes) ? $item->classes : [];
    $icon_classes = wiki_icp_menu_icon_extract_classes($classes);

    if (empty($icon_classes)) {
        return $title;
    }

    $icon_html  = sprintf(
        '<span class="menu-link-icon"><i class="%s" aria-hidden="true"></i></span>',
        esc_attr(implode(' ', array_map('sanitize_html_class', $icon_classes)))
    );
    $label_html = sprintf('<span class="menu-link-label">%s</span>', esc_html($title));

    return $icon_html . $label_html;
}
add_filter('nav_menu_item_title', 'wiki_icp_menu_icons', 10, 4);

function wiki_icp_strip_icon_classes_from_li($classes, $item, $args, $depth) {
    if (($args->theme_location ?? '') !== 'primary-menu') {
        return $classes;
    }

    $icon_classes = wiki_icp_menu_icon_extract_classes(is_array($classes) ? $classes : []);
    if (empty($icon_classes)) {
        return $classes;
    }

    $filtered = array_diff($classes, $icon_classes);
    return array_values($filtered);
}
add_filter('nav_menu_css_class', 'wiki_icp_strip_icon_classes_from_li', 10, 4);

if (!function_exists('get_brand_data')) {
    /**
     * Return brand metadata keyed by current subdomain.
     */
    function get_brand_data() {
        $base_uri = get_stylesheet_directory_uri() . '/assets/logos';
        $brands   = [
            'starline' => [
                'name'    => 'Starline Security',
                'phone'   => '(07) 3272 2974',
                'address' => 'Unit 8/47 Overlord Place, Acacia Ridge, QLD 4108',
                'website' => 'https://www.starlinesecurity.net.au',
                'logo'    => $base_uri . '/starline-logo.svg',
                'css_vars' => [
                    '--PrimaryColor'               => '#51b592',
                    '--SecondaryColor'             => '#f1c40f',
                    '--BackgroundNavFooter'        => '#0e3753',
                    '--HeadingFontFamily'          => "'Sansation', sans-serif",
                    '--BodyFontFamily'             => "'Helvetica', sans-serif",
                    '--ButtonFontFamily'           => "'Sansation', sans-serif",
                    '--PrimaryColorLight'          => '#ffffff',
                    '--SecondaryColorLight'        => '#fde68a',
                    '--OnBackgroundNavFooterDefault' => '#ffffff',
                    '--OnBackgroundNavFooterMeta'  => '#51b592',
                ],
            ],
            'supaview' => [
                'name'    => 'Supaview Screens & Blinds',
                'phone'   => '(07) 9876 5432',
                'address' => '456 Supaview Road, Gold Coast QLD',
                'website' => 'https://www.supaview.com.au',
                'logo'    => $base_uri . '/supaview-logo.png',
                'css_vars' => [
                    '--PrimaryColor'        => '#f97316',
                    '--SecondaryColor'      => '#f59e0b',
                    '--BackgroundNavFooter' => '#232833',
                    '--PrimaryColorLight'   => '#fde4d0',
                    '--SecondaryColorLight' => '#fff0d6',
                ],
            ],
            'default' => [
                'name'    => 'ICP Portal Systems',
                'phone'   => '(07) 3273 6020',
                'address' => 'Unit 8/47 Overlord Place, Acacia Ridge, QLD 4108',
                'website' => 'https://www.wikiicpsystems.com.au',
                'logo'    => $base_uri . '/default-logo.svg',
                
            ],
        ];

        $subdomain = get_subdomain();
        return $brands[$subdomain] ?? $brands['default'];
    }
}

/**
 * Allow shortcodes like [brand key="phone"] to render metadata dynamically.
 */
add_shortcode('brand', function ($atts) {
    $atts       = shortcode_atts(['key' => 'name'], $atts, 'brand');
    $brand_data = get_brand_data();
    return esc_html($brand_data[$atts['key']] ?? '');
});

/**
 * Force URLs generated by WordPress to keep the current subdomain host.
 */
function wiki_icp_force_current_host($url) {
    $current_host = $_SERVER['HTTP_HOST'] ?? '';
    if (!$current_host || empty($url)) {
        return $url;
    }

    $parsed = wp_parse_url($url);
    if (!$parsed || empty($parsed['host']) || $parsed['host'] === $current_host) {
        return $url;
    }

    $scheme   = $parsed['scheme'] ?? (is_ssl() ? 'https' : 'http');
    $path     = $parsed['path'] ?? '';
    $query    = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

    return sprintf('%s://%s%s%s%s', $scheme, $current_host, $path, $query, $fragment);
}
add_filter('home_url', 'wiki_icp_force_current_host', 10, 1);
add_filter('site_url', 'wiki_icp_force_current_host', 10, 1);
add_filter('network_home_url', 'wiki_icp_force_current_host', 10, 1);
add_filter('network_site_url', 'wiki_icp_force_current_host', 10, 1);

/**
 * Override CSS variables per brand without maintaining separate stylesheets.
 */
function wiki_icp_enqueue_brand_styles() {
    if (!wp_style_is('wiki-core-style', 'enqueued')) {
        return;
    }

    $brand = get_brand_data();
    if (empty($brand['css_vars']) || !is_array($brand['css_vars'])) {
        return;
    }

    $declarations = [];
    foreach ($brand['css_vars'] as $var => $value) {
        $var_name = preg_replace('/[^a-zA-Z0-9\-\_]/', '', (string) $var);
        $declarations[] = sprintf('%s:%s;', $var_name, sanitize_text_field($value));
    }

    if ($declarations) {
        $css = ':root{' . implode('', $declarations) . '}';
        wp_add_inline_style('wiki-core-style', $css);
    }
}
add_action('wp_enqueue_scripts', 'wiki_icp_enqueue_brand_styles', 25);

$child_dir = get_stylesheet_directory();

// Include local-only overrides if present (never committed).
$local = $child_dir . '/localfunctions.php';
if (file_exists($local)) {
    include_once $local;
}

// Register CPTs and taxonomies for Help Topics.
$tax = $child_dir . '/functions-taxonomies.php';
if (file_exists($tax)) {
    include_once $tax;
}

$ai = $child_dir . '/inc/ai.php';
if (file_exists($ai)) {
    include_once $ai;
}

/**
 * Ensure custom page templates like Help Topics Directory override conflicting rewrites.
 */
function wiki_icp_register_directory_template_rewrites() {
    $pages = get_pages([
        'post_type'   => 'page',
        'post_status' => 'publish',
        'meta_key'    => '_wp_page_template',
        'meta_value'  => 'page-help-topics.php',
        'number'      => -1,
    ]);

    if (!$pages) {
        return;
    }

    foreach ($pages as $page) {
        $slug = trim(get_page_uri($page->ID), '/');
        if (!$slug) {
            continue;
        }
        $regex = sprintf('^%s/?$', preg_quote($slug, '/'));
        add_rewrite_rule(
            $regex,
            'index.php?page_id=' . $page->ID,
            'top'
        );
    }
}
add_action('init', 'wiki_icp_register_directory_template_rewrites', 12);

function wiki_icp_get_video_post_types() {
    return ['tutorial_video', 'installation_video'];
}

/**
 * Tutorial video meta box for YouTube embed + duration.
 */
function wiki_icp_tutorial_video_meta_box() {
    foreach (wiki_icp_get_video_post_types() as $post_type) {
        add_meta_box(
            'wiki-icp-tutorial-video-meta',
            __('Video Details', 'wiki-icp'),
            'wiki_icp_render_tutorial_meta_box',
            $post_type,
            'normal',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'wiki_icp_tutorial_video_meta_box');

function wiki_icp_render_tutorial_meta_box($post) {
    wp_nonce_field('wiki_icp_save_tutorial_meta', 'wiki_icp_tutorial_meta_nonce');

    $video_embed = get_post_meta($post->ID, 'youtube_video', true);
    $video_time  = get_post_meta($post->ID, 'video_time', true);
    $video_order = get_post_meta($post->ID, 'tutorial_order', true);
    ?>
    <div style="display:flex;flex-wrap:wrap;gap:16px;">
    <p style="flex:1 1 65%;min-width:260px;">
        <label for="wiki-icp-youtube-video"><?php esc_html_e('YouTube URL', 'wiki-icp'); ?></label>
        <input id="wiki-icp-youtube-video" type="url" name="wiki_icp_youtube_video" value="<?php echo esc_attr($video_embed); ?>" style="width:100%;">
        <small><?php esc_html_e('Use the share link or full YouTube URL (no embed code).', 'wiki-icp'); ?></small>
    </p>
    <p style="flex:1 1 30%;min-width:200px;">
        <label for="wiki-icp-video-time"><?php esc_html_e('Duration (e.g. 2:35)', 'wiki-icp'); ?></label>
        <input id="wiki-icp-video-time" type="text" name="wiki_icp_video_time" value="<?php echo esc_attr($video_time); ?>" class="regular-text" style="width:100%;">
        <small><?php esc_html_e('Displayed next to the title.', 'wiki-icp'); ?></small>
    </p>
    <p style="flex:1 1 20%;min-width:140px;">
        <label for="wiki-icp-video-order"><?php esc_html_e('Display Order', 'wiki-icp'); ?></label>
        <input id="wiki-icp-video-order" type="number" name="wiki_icp_video_order" value="<?php echo esc_attr($video_order); ?>" style="width:100%;" min="0" step="1">
        <small><?php esc_html_e('Lower numbers appear first per category.', 'wiki-icp'); ?></small>
    </p>
    </div>
    <?php
}

function wiki_icp_save_tutorial_meta($post_id) {
    if (!isset($_POST['wiki_icp_tutorial_meta_nonce']) || !wp_verify_nonce($_POST['wiki_icp_tutorial_meta_nonce'], 'wiki_icp_save_tutorial_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['wiki_icp_youtube_video'])) {
        update_post_meta($post_id, 'youtube_video', esc_url_raw($_POST['wiki_icp_youtube_video']));
    }

    if (isset($_POST['wiki_icp_video_time'])) {
        update_post_meta($post_id, 'video_time', sanitize_text_field($_POST['wiki_icp_video_time']));
    }

    if (isset($_POST['wiki_icp_video_order'])) {
        update_post_meta($post_id, 'tutorial_order', intval($_POST['wiki_icp_video_order']));
    }
}
add_action('save_post_tutorial_video', 'wiki_icp_save_tutorial_meta');
add_action('save_post_installation_video', 'wiki_icp_save_tutorial_meta');

/**
 * Portal selector meta box for pages using the tutorial template.
 */
function wiki_icp_add_page_portal_meta_box() {
    add_meta_box(
        'wiki-icp-page-portal',
        __('Tutorial Portal', 'wiki-icp'),
        'wiki_icp_render_page_portal_meta_box',
        'page',
        'side'
    );
}
add_action('add_meta_boxes', 'wiki_icp_add_page_portal_meta_box');

function wiki_icp_render_page_portal_meta_box($post) {
    $template = get_page_template_slug($post);
    if ($template !== 'page-tutorial-videos.php') {
        echo '<p>' . esc_html__('Assign the "Tutorial Videos" template to enable portal filtering.', 'wiki-icp') . '</p>';
        return;
    }

    wp_nonce_field('wiki_icp_save_page_portal_meta', 'wiki_icp_page_portal_nonce');
    $portal_slug = get_post_meta($post->ID, 'tutorial_portal_slug', true);
    ?>
    <p>
        <label for="wiki-icp-page-portal"><?php esc_html_e('Portal Slug', 'wiki-icp'); ?></label>
        <input id="wiki-icp-page-portal" type="text" name="wiki_icp_page_portal_slug" value="<?php echo esc_attr($portal_slug); ?>" style="width:100%;" placeholder="ordering-portal">
        <small><?php esc_html_e('Slug from the Tutorial Portal taxonomy (e.g., ordering-portal).', 'wiki-icp'); ?></small>
    </p>
    <?php
}

function wiki_icp_save_page_portal_meta($post_id) {
    if (!isset($_POST['wiki_icp_page_portal_nonce']) || !wp_verify_nonce($_POST['wiki_icp_page_portal_nonce'], 'wiki_icp_save_page_portal_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_page', $post_id)) {
        return;
    }

    $template = get_page_template_slug($post_id);
    if ($template !== 'page-tutorial-videos.php') {
        delete_post_meta($post_id, 'tutorial_portal_slug');
        return;
    }

    if (isset($_POST['wiki_icp_page_portal_slug'])) {
        $slug = sanitize_title(wp_unslash($_POST['wiki_icp_page_portal_slug']));
        update_post_meta($post_id, 'tutorial_portal_slug', $slug);
    }
}
add_action('save_post_page', 'wiki_icp_save_page_portal_meta');

/**
 * Utilities for knowledge search.
 */
function wiki_icp_get_plain_excerpt_from_post($post_id, $length = 200) {
    $post = get_post($post_id);
    if (!$post) {
        return '';
    }

    $source = $post->post_excerpt ?: $post->post_content;
    $text   = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($source)));

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . '…';
}

function wiki_icp_render_text_with_icons($text) {
    return do_shortcode($text ?? '');
}

function wiki_icp_icon_shortcode($atts) {
    $atts = shortcode_atts([
        'name'   => '',
        'prefix' => 'fa-light',
        'class'  => '',
        'label'  => '',
    ], $atts, 'icon');

    $name = sanitize_title($atts['name']);
    if (!$name) {
        return '';
    }

    $prefix = preg_replace('/[^a-z0-9\- ]/i', '', $atts['prefix'] ?: 'fa-light');
    $extra  = preg_replace('/[^a-z0-9\- ]/i', '', $atts['class'] ?? '');
    $label  = sanitize_text_field($atts['label']);

    $classes = trim(sprintf('%s fa-%s %s', $prefix, $name, $extra));

    return sprintf(
        '<i class="%s" aria-hidden="true"%s></i>',
        esc_attr($classes),
        $label ? sprintf(' title="%s"', esc_attr($label)) : ''
    );
}
add_shortcode('icon', 'wiki_icp_icon_shortcode');

function wiki_icp_extract_help_topic_sections($post_id, $max_sections = 8) {
    $post = get_post($post_id);
    if (!$post) {
        return [];
    }

    $content = $post->post_content;
    if (!$content) {
        return [];
    }

    $content = preg_replace('/\[icon[^\]]+\]/i', '', $content);
    $pattern = '/(<h[2-3][^>]*>.*?<\/h[2-3]>)/i';
    $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $sections = [];
    $current_heading = __('Overview', 'wiki-icp');
    $buffer = '';

    $append_section = function ($heading, $body) use (&$sections, $max_sections) {
        $clean_body = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($body)));
        if ($clean_body === '') {
            return;
        }
        $sections[] = [
            'heading' => $heading ?: __('Details', 'wiki-icp'),
            'body'    => $clean_body,
        ];
        if (count($sections) >= $max_sections) {
            return true;
        }
        return false;
    };

    foreach ($parts as $part) {
        if (preg_match('/^<h[2-3][^>]*>.*<\/h[2-3]>$/i', $part)) {
            if ($buffer !== '') {
                $stop = $append_section($current_heading, $buffer);
                if ($stop === true) {
                    break;
                }
            }
            $current_heading = trim(wp_strip_all_tags($part)) ?: __('Details', 'wiki-icp');
            $buffer = '';
            continue;
        }

        $buffer .= ' ' . $part;
    }

    if ($buffer !== '' && count($sections) < $max_sections) {
        $append_section($current_heading, $buffer);
    }

    return array_slice($sections, 0, $max_sections);
}

function wiki_icp_format_help_topic_result($post_id) {
    $portal_terms = get_the_terms($post_id, WIKI_ICP_PORTAL_TAXONOMY);
    $portal       = (!empty($portal_terms) && !is_wp_error($portal_terms)) ? $portal_terms[0] : null;

    return [
        'id'         => $post_id,
        'type'       => 'article',
        'typeLabel'  => __('Help Topic', 'wiki-icp'),
        'portal'     => $portal ? $portal->name : '',
        'portalSlug' => $portal ? $portal->slug : '',
        'title'      => get_the_title($post_id),
        'link'       => get_permalink($post_id),
        'excerpt'    => wiki_icp_get_plain_excerpt_from_post($post_id),
        'meta'       => '',
        'cta'        => __('Read article', 'wiki-icp'),
    ];
}

function wiki_icp_format_tutorial_result($post_id) {
    $portal_terms = get_the_terms($post_id, 'tutorial_video_portal');
    $portal       = (!empty($portal_terms) && !is_wp_error($portal_terms)) ? $portal_terms[0] : null;
    $duration     = get_post_meta($post_id, 'video_time', true);
    $post_type    = get_post_type($post_id);
    $is_install   = ($post_type === 'installation_video');

    return [
        'id'         => $post_id,
        'type'       => 'tutorial',
        'subtype'    => $is_install ? 'installation' : 'tutorial',
        'typeLabel'  => $is_install ? __('Installation Video', 'wiki-icp') : __('Tutorial Video', 'wiki-icp'),
        'portal'     => $portal ? $portal->name : '',
        'portalSlug' => $portal ? $portal->slug : '',
        'title'      => get_the_title($post_id),
        'link'       => get_permalink($post_id),
        'excerpt'    => wiki_icp_get_plain_excerpt_from_post($post_id),
        'meta'       => $duration ? sprintf(__('Duration %s', 'wiki-icp'), $duration) : '',
        'cta'        => $is_install ? __('Watch installation', 'wiki-icp') : __('Watch video', 'wiki-icp'),
    ];
}

function wiki_icp_collect_help_topics($query, $portal_term = null, $limit = 20) {
    $results = [];
    $seen    = [];

    if ($query) {
        $args = [
            'post_type'      => 'help_topic',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => $limit,
            's'              => $query,
        ];
        if ($portal_term) {
            $args['tax_query'] = [
                [
                    'taxonomy' => WIKI_ICP_PORTAL_TAXONOMY,
                    'field'    => 'term_id',
                    'terms'    => $portal_term->term_id,
                ],
            ];
        }
        $ids = get_posts($args);
        foreach ($ids as $id) {
            $seen[]    = $id;
            $results[] = wiki_icp_format_help_topic_result($id);
            if (count($results) >= $limit) {
                return $results;
            }
        }
    }

    if (!$query) {
        return $results;
    }

    $taxonomies = [
        'help_topic_type',
        'help_topic_style',
        'glass_style',
        'help_topic_grade',
        WIKI_ICP_PORTAL_TAXONOMY,
    ];
    $search_slug = sanitize_title($query);

    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'search'     => $query,
            'name__like' => $query,
            'hide_empty' => false,
            'number'     => 0,
        ]);

        if (is_wp_error($terms)) {
            continue;
        }

        $term_ids = [];
        if ($terms) {
            foreach ($terms as $term) {
                if (
                    stripos($term->name, $query) !== false ||
                    ($search_slug && stripos($term->slug, $search_slug) !== false)
                ) {
                    $term_ids[] = $term->term_id;
                }
            }
        }

        if (empty($term_ids)) {
            continue;
        }

        $tax_query = [
            [
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term_ids,
            ],
        ];

        if ($portal_term && $taxonomy !== WIKI_ICP_PORTAL_TAXONOMY) {
            $tax_query[] = [
                'taxonomy' => WIKI_ICP_PORTAL_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $portal_term->term_id,
            ];
            $tax_query = array_merge(['relation' => 'AND'], $tax_query);
        }

        $ids = get_posts([
            'post_type'      => 'help_topic',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => max(0, $limit - count($results)),
            'post__not_in'   => $seen,
            'tax_query'      => $tax_query,
        ]);

        foreach ($ids as $id) {
            $seen[]    = $id;
            $results[] = wiki_icp_format_help_topic_result($id);
            if (count($results) >= $limit) {
                break 2;
            }
        }
    }

    return $results;
}

function wiki_icp_collect_tutorial_videos($query, $portal_term = null, $limit = 6) {
    $results = [];
    $seen    = [];

    if ($query) {
        $tax_query = [];
        if ($portal_term) {
            $tax_query[] = [
                'taxonomy' => 'tutorial_video_portal',
                'field'    => 'term_id',
                'terms'    => $portal_term->term_id,
            ];
        }

        $args = [
            'post_type'      => wiki_icp_get_video_post_types(),
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => $limit,
            's'              => $query,
        ];
        if ($tax_query) {
            $args['tax_query'] = $tax_query;
        }

        $ids = get_posts($args);
        foreach ($ids as $id) {
            $seen[]    = $id;
            $results[] = wiki_icp_format_tutorial_result($id);
            if (count($results) >= $limit) {
                return $results;
            }
        }
    }

    if (!$query) {
        return $results;
    }

    $taxonomies = ['category_tutorial_video'];
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'search'     => $query,
            'hide_empty' => false,
            'number'     => 10,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            continue;
        }

        $term_ids = wp_list_pluck($terms, 'term_id');
        $tax_query = [
            [
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term_ids,
            ],
        ];
        if ($portal_term) {
            $tax_query[] = [
                'taxonomy' => 'tutorial_video_portal',
                'field'    => 'term_id',
                'terms'    => $portal_term->term_id,
            ];
            $tax_query = array_merge(['relation' => 'AND'], $tax_query);
        }

        $ids = get_posts([
            'post_type'      => wiki_icp_get_video_post_types(),
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => max(0, $limit - count($results)),
            'post__not_in'   => $seen,
            'tax_query'      => $tax_query,
        ]);

        foreach ($ids as $id) {
            $seen[]    = $id;
            $results[] = wiki_icp_format_tutorial_result($id);
            if (count($results) >= $limit) {
                break 2;
            }
        }
    }

    return $results;
}

add_action('rest_api_init', function () {
    register_rest_route('wiki-icp/v1', '/search', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'args'                => [
            'query' => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'portal' => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'types' => [
                'type'     => 'array',
                'required' => false,
            ],
        ],
        'callback'            => 'wiki_icp_rest_search',
    ]);
});

function wiki_icp_rest_search(WP_REST_Request $request) {
    $query       = sanitize_text_field($request->get_param('query'));
    $portal_slug = sanitize_title($request->get_param('portal'));
    $types       = (array) $request->get_param('types');
    if (empty($types)) {
        $types = ['articles', 'tutorials'];
    }

    $types = array_map('sanitize_text_field', $types);

    $portal_term = $portal_slug ? get_term_by('slug', $portal_slug, WIKI_ICP_PORTAL_TAXONOMY) : null;
    $tutorial_portal_term = $portal_slug ? get_term_by('slug', $portal_slug, 'tutorial_video_portal') : null;

    $article_limit  = (int) apply_filters('wiki_icp_search_article_limit', 60);
    $tutorial_limit = (int) apply_filters('wiki_icp_search_tutorial_limit', 40);

    $articles  = in_array('articles', $types, true) ? wiki_icp_collect_help_topics($query, $portal_term, $article_limit) : [];
    $tutorials = in_array('tutorials', $types, true) ? wiki_icp_collect_tutorial_videos($query, $tutorial_portal_term, $tutorial_limit) : [];

    return rest_ensure_response([
        'articles' => $articles,
        'tutorials' => $tutorials,
        'counts'   => [
            'articles'  => count($articles),
            'tutorials' => count($tutorials),
        ],
    ]);
}

function wiki_icp_prepare_video_embed($raw) {
    if (empty($raw)) {
        return '';
    }

    $raw = trim($raw);

    if (stripos($raw, '<iframe') !== false || stripos($raw, '<video') !== false) {
        return wp_kses_post($raw);
    }

    if (!filter_var($raw, FILTER_VALIDATE_URL)) {
        return '';
    }

    $embed = wp_oembed_get($raw);
    if ($embed) {
        return $embed;
    }

    $video_id = '';
    $host = wp_parse_url($raw, PHP_URL_HOST);
    if (strpos($host, 'youtu.be') !== false) {
        $path = trim(wp_parse_url($raw, PHP_URL_PATH), '/');
        $video_id = $path;
    } elseif (strpos($host, 'youtube.com') !== false) {
        parse_str(wp_parse_url($raw, PHP_URL_QUERY) ?? '', $query);
        if (!empty($query['v'])) {
            $video_id = $query['v'];
        }
    }

    if ($video_id) {
        $src = sprintf('https://www.youtube.com/embed/%s', esc_attr($video_id));
        return sprintf(
            '<iframe src="%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
            esc_url($src)
        );
    }

    return sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url($raw), esc_html($raw));
}

function wiki_icp_register_tutorial_rest_fields() {
    register_rest_field(wiki_icp_get_video_post_types(), 'video_embed', [
        'get_callback' => function ($object) {
            $raw = get_post_meta($object['id'], 'youtube_video', true);
            return wiki_icp_prepare_video_embed($raw);
        },
        'schema' => [
            'description' => __('Prepared video embed markup', 'wiki-icp'),
            'type'        => 'string',
            'context'     => ['view', 'edit'],
        ],
    ]);

    register_rest_field(wiki_icp_get_video_post_types(), 'video_duration', [
        'get_callback' => function ($object) {
            return get_post_meta($object['id'], 'video_time', true);
        },
        'schema' => [
            'description' => __('Video duration label', 'wiki-icp'),
            'type'        => 'string',
            'context'     => ['view', 'edit'],
        ],
    ]);
}
add_action('rest_api_init', 'wiki_icp_register_tutorial_rest_fields');

function wiki_icp_tutorial_editor_notice($post) {
    if (!in_array($post->post_type, wiki_icp_get_video_post_types(), true)) {
        return;
    }
    echo '<div class="notice notice-info inline"><p>' .
        esc_html__('Tip: Use the Video Details panel to paste your YouTube URL, set the duration, and control the display order within each category.', 'wiki-icp') .
        '</p></div>';
}
add_action('edit_form_after_title', 'wiki_icp_tutorial_editor_notice');

function wiki_icp_tutorial_admin_css() {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, wiki_icp_get_video_post_types(), true)) {
        return;
    }
    echo '<style>
    #wiki-icp-tutorial-video-meta label{font-weight:600;margin-bottom:4px;display:block;}
    #pageparentdiv .inside{background:#f8fafc;border-radius:6px;padding:12px;}
    #pageparentdiv label{font-weight:600;}
    </style>';
}
add_action('admin_head', 'wiki_icp_tutorial_admin_css');

function wiki_icp_installation_video_single_template($template) {
    if (is_singular('installation_video')) {
        $shared = locate_template('single-tutorial_video.php');
        if ($shared) {
            return $shared;
        }
    }

    return $template;
}
add_filter('single_template', 'wiki_icp_installation_video_single_template');
