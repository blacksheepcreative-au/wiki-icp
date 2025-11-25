<?php
if (!defined('WIKI_ICP_PORTAL_TAXONOMY')) {
    define('WIKI_ICP_PORTAL_TAXONOMY', 'category_help_topic');
}

function wiki_icp_get_primary_portal_slug($post_id) {
    $portal_terms = get_the_terms($post_id, WIKI_ICP_PORTAL_TAXONOMY);
    if ($portal_terms && !is_wp_error($portal_terms)) {
        $portal_term = array_shift($portal_terms);
        return $portal_term->slug;
    }
    return 'general';
}

add_action('init', function () {
    // Register CPT
    register_post_type('help_topic', [
        'labels' => [
            'name'          => 'Help Topics',
            'singular_name' => 'Help Topic',
        ],
        'public'       => true,
        'show_ui'      => true,
        'show_in_rest' => true,
        'supports'     => ['title', 'editor', 'excerpt', 'thumbnail'],
        'menu_icon'    => 'dashicons-editor-help',
        'rewrite'      => [
            'slug' => 'help-topics',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'has_archive'  => true,
    ]);

    // Register hierarchical Portal taxonomy (maps imported "Category" terms).
    register_taxonomy(WIKI_ICP_PORTAL_TAXONOMY, 'help_topic', [
        'labels' => [
            'name' => 'Portals',
            'singular_name' => 'Portal',
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => [
            'slug' => 'help-topics',
            'with_front' => false,
            'hierarchical' => true,
        ],
    ]);

    // Additional taxonomies from legacy site.
    register_taxonomy('help_topic_type', 'help_topic', [
        'labels' => [
            'name'          => 'Types',
            'singular_name' => 'Type',
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'help-topic-type', 'with_front' => false],
    ]);

    register_taxonomy('help_topic_style', 'help_topic', [
        'labels' => [
            'name'          => 'Styles',
            'singular_name' => 'Style',
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'help-topic-style', 'with_front' => false],
    ]);

    register_taxonomy('glass_style', 'help_topic', [
        'labels' => [
            'name'          => 'Glass Styles',
            'singular_name' => 'Glass Style',
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'glass-style', 'with_front' => false],
    ]);

    register_taxonomy('help_topic_grade', 'help_topic', [
        'labels' => [
            'name'          => 'Grades',
            'singular_name' => 'Grade',
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'help-topic-grade', 'with_front' => false],
    ]);

    register_taxonomy('page_category', 'page', [
        'labels' => [
            'name'          => __('Page Categories', 'wiki-icp'),
            'singular_name' => __('Page Category', 'wiki-icp'),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'page-category', 'with_front' => false],
    ]);

    // Rewrite rule: {portal}/topic-{ID}
    add_rewrite_rule(
        '^([^/]+)/topic-([0-9]+)/?$',
        'index.php?post_type=help_topic&p=$matches[2]&' . WIKI_ICP_PORTAL_TAXONOMY . '=$matches[1]',
        'top'
    );
});

function wiki_icp_register_tutorial_video_post_type() {
    $labels = [
        'name'                  => __('Tutorial Videos', 'wiki-icp'),
        'singular_name'         => __('Tutorial Video', 'wiki-icp'),
        'menu_name'             => __('Tutorial Videos', 'wiki-icp'),
        'name_admin_bar'        => __('Tutorial Video', 'wiki-icp'),
        'add_new'               => __('Add New', 'wiki-icp'),
        'add_new_item'          => __('Add New Tutorial Video', 'wiki-icp'),
        'new_item'              => __('New Tutorial Video', 'wiki-icp'),
        'edit_item'             => __('Edit Tutorial Video', 'wiki-icp'),
        'view_item'             => __('View Tutorial Video', 'wiki-icp'),
        'all_items'             => __('All Tutorial Videos', 'wiki-icp'),
        'search_items'          => __('Search Tutorial Videos', 'wiki-icp'),
        'not_found'             => __('No tutorial videos found.', 'wiki-icp'),
        'not_found_in_trash'    => __('No tutorial videos found in Trash.', 'wiki-icp'),
        'parent_item_colon'     => __('Parent Tutorial Videos:', 'wiki-icp'),
        'archives'              => __('Tutorial Video Archives', 'wiki-icp'),
        'attributes'            => __('Tutorial Video Attributes', 'wiki-icp'),
    ];

    register_post_type('tutorial_video', [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'rest_base'          => 'tutorial_video',
        'rest_namespace'     => 'wp/v2',
        'query_var'          => true,
        'rewrite'            => [
            'slug'       => 'tutorial-video',
            'with_front' => false,
        ],
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-video-alt3',
        'supports'           => ['title', 'editor', 'thumbnail', 'page-attributes', 'excerpt'],
    ]);

    register_post_type('installation_video', [
        'labels'             => [
            'name'                  => __('Installation Videos', 'wiki-icp'),
            'singular_name'         => __('Installation Video', 'wiki-icp'),
            'menu_name'             => __('Installation Videos', 'wiki-icp'),
            'name_admin_bar'        => __('Installation Video', 'wiki-icp'),
            'add_new'               => __('Add New', 'wiki-icp'),
            'add_new_item'          => __('Add New Installation Video', 'wiki-icp'),
            'new_item'              => __('New Installation Video', 'wiki-icp'),
            'edit_item'             => __('Edit Installation Video', 'wiki-icp'),
            'view_item'             => __('View Installation Video', 'wiki-icp'),
            'all_items'             => __('All Installation Videos', 'wiki-icp'),
            'search_items'          => __('Search Installation Videos', 'wiki-icp'),
            'not_found'             => __('No installation videos found.', 'wiki-icp'),
            'not_found_in_trash'    => __('No installation videos found in Trash.', 'wiki-icp'),
            'parent_item_colon'     => __('Parent Installation Videos:', 'wiki-icp'),
            'archives'              => __('Installation Video Archives', 'wiki-icp'),
            'attributes'            => __('Installation Video Attributes', 'wiki-icp'),
        ],
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'rest_base'          => 'installation_video',
        'rest_namespace'     => 'wp/v2',
        'query_var'          => true,
        'rewrite'            => [
            'slug'       => 'installation-video',
            'with_front' => false,
        ],
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-hammer',
        'supports'           => ['title', 'editor', 'thumbnail', 'page-attributes', 'excerpt'],
    ]);

    $video_post_types = ['tutorial_video', 'installation_video'];

    register_taxonomy('category_tutorial_video', $video_post_types, [
        'labels' => [
            'name'              => __('Tutorial Categories', 'wiki-icp'),
            'singular_name'     => __('Tutorial Category', 'wiki-icp'),
            'search_items'      => __('Search Categories', 'wiki-icp'),
            'all_items'         => __('All Categories', 'wiki-icp'),
            'parent_item'       => __('Parent Category', 'wiki-icp'),
            'parent_item_colon' => __('Parent Category:', 'wiki-icp'),
            'edit_item'         => __('Edit Category', 'wiki-icp'),
            'update_item'       => __('Update Category', 'wiki-icp'),
            'add_new_item'      => __('Add New Category', 'wiki-icp'),
            'new_item_name'     => __('New Category Name', 'wiki-icp'),
            'menu_name'         => __('Categories', 'wiki-icp'),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'         => 'tutorial-video-category',
            'with_front'   => false,
            'hierarchical' => true,
        ],
    ]);

    register_taxonomy('tutorial_video_portal', $video_post_types, [
        'labels' => [
            'name'              => __('Tutorial Portals', 'wiki-icp'),
            'singular_name'     => __('Tutorial Portal', 'wiki-icp'),
            'search_items'      => __('Search Portals', 'wiki-icp'),
            'all_items'         => __('All Portals', 'wiki-icp'),
            'parent_item'       => __('Parent Portal', 'wiki-icp'),
            'parent_item_colon' => __('Parent Portal:', 'wiki-icp'),
            'edit_item'         => __('Edit Portal', 'wiki-icp'),
            'update_item'       => __('Update Portal', 'wiki-icp'),
            'add_new_item'      => __('Add New Portal', 'wiki-icp'),
            'new_item_name'     => __('New Portal Name', 'wiki-icp'),
            'menu_name'         => __('Portals', 'wiki-icp'),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'         => 'tutorial-video-portal',
            'with_front'   => false,
            'hierarchical' => true,
        ],
    ]);
}
add_action('init', 'wiki_icp_register_tutorial_video_post_type');

function wiki_icp_register_tutorial_video_meta() {
    $post_types = ['tutorial_video', 'installation_video'];

    foreach ($post_types as $post_type) {
        register_post_meta($post_type, 'youtube_video', [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => '__return_true',
        ]);

        register_post_meta($post_type, 'video_time', [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => '__return_true',
        ]);

        register_post_meta($post_type, 'tutorial_order', [
            'type'              => 'integer',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'intval',
            'auth_callback'     => '__return_true',
        ]);
    }
}
add_action('init', 'wiki_icp_register_tutorial_video_meta');

add_filter('post_type_link', function($post_link, $post) {
    if ($post->post_type !== 'help_topic')
        return $post_link;

    $portal_slug = wiki_icp_get_primary_portal_slug($post->ID ?? $post);
    $topic_id    = absint($post->ID ?? 0);
    if (!$topic_id) {
        return $post_link;
    }

    return home_url("{$portal_slug}/topic-{$topic_id}/");
}, 10, 2);

add_action('after_switch_theme', 'flush_rewrite_rules');

// Ensure canonical portal/topic-ID URLs.
add_action('template_redirect', function() {
    if (!is_singular('help_topic')) {
        return;
    }

    global $wp;
    $post        = get_queried_object();
    $portal_slug = wiki_icp_get_primary_portal_slug($post->ID ?? 0);
    $expected    = trailingslashit(home_url("{$portal_slug}/topic-" . $post->ID . '/'));
    $current     = trailingslashit(home_url($wp->request));

    if (trailingslashit($expected) !== $current) {
        wp_safe_redirect($expected, 301);
        exit;
    }
});
