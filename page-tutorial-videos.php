<?php
/**
 * Template Name: Tutorial Videos
 */

get_header();

$portal_slug = get_post_meta(get_the_ID(), 'tutorial_portal_slug', true);
$portal_term = $portal_slug ? get_term_by('slug', $portal_slug, 'tutorial_video_portal') : null;

$terms = get_terms([
    'taxonomy'   => 'category_tutorial_video',
    'hide_empty' => true,
    'orderby'    => 'term_id',
    'order'      => 'ASC',
]);

$default_post_id = 0;
$sidebar_markup  = '';

if (!empty($terms) && !is_wp_error($terms)) {
    $sidebar_markup .= '<ul class="tutorial-sidebar__list">';
    $parent_index = 0;
    foreach ($terms as $term) {
        if ($term->parent) {
            continue;
        }
        if ($term->slug === 'featured') {
            continue;
        }

        $tax_query = [
            [
                'taxonomy' => 'category_tutorial_video',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
                'include_children' => true,
            ],
        ];

        if ($portal_term) {
            $tax_query[] = [
                'taxonomy' => 'tutorial_video_portal',
                'field'    => 'term_id',
                'terms'    => $portal_term->term_id,
            ];
        }

        $query = new WP_Query([
            'post_type'      => 'tutorial_video',
            'posts_per_page' => -1,
            'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'tax_query'      => $tax_query,
        ]);

        if (!$query->have_posts()) {
            wp_reset_postdata();
            continue;
        }

        $is_open = $parent_index === 0 ? ' is-open' : '';
        $sidebar_markup .= '<li class="tutorial-category' . $is_open . '">';
        $sidebar_markup .= '<button type="button" class="tutorial-category__toggle" data-tutorial-cat-toggle>';
        $sidebar_markup .= esc_html($term->name);
        $sidebar_markup .= '<span class="tutorial-category__chevron" aria-hidden="true"></span>';
        $sidebar_markup .= '</button>';

        $sidebar_markup .= '<ul class="tutorial-items">';
        while ($query->have_posts()) {
            $query->the_post();
            if (!$default_post_id) {
                $default_post_id = get_the_ID();
            }

            $duration = get_post_meta(get_the_ID(), 'video_time', true);

            $sidebar_markup .= sprintf(
                '<li><button type="button" class="tutorial-item %1$s" data-tutorial-trigger="%2$d"><span class="tutorial-item__title">%3$s</span>%4$s</button></li>',
                $default_post_id === get_the_ID() ? 'is-active' : '',
                get_the_ID(),
                esc_html(get_the_title()),
                $duration ? '<span class="tutorial-item__meta">' . esc_html($duration) . '</span>' : ''
            );
        }
        $sidebar_markup .= '</ul>';
        $sidebar_markup .= '</li>';
        wp_reset_postdata();
        $parent_index++;
    }
    $sidebar_markup .= '</ul>';
}

$default_video = '';
$default_content = '';
$default_title = '';
$default_time = '';

if ($default_post_id) {
    $default_video = get_post_meta($default_post_id, 'youtube_video', true);
    $default_content = apply_filters('the_content', get_post_field('post_content', $default_post_id));
    $default_title = get_the_title($default_post_id);
    $default_time  = get_post_meta($default_post_id, 'video_time', true);

    if (!$default_video && preg_match('/<iframe[\s\S]*?<\/iframe>/', $default_content, $matches)) {
        $default_video   = $matches[0];
        $default_content = str_replace($matches[0], '', $default_content);
    }

    $default_video = wiki_icp_prepare_video_embed($default_video);
}
?>

<main id="primary" class="site-main">
    <section class="tutorial-app" data-tutorial-app data-post-type="tutorial_video" data-default-id="<?php echo esc_attr($default_post_id); ?>">
        <button type="button" class="tutorial-mobile-toggle" data-tutorial-sidebar-toggle>
            <span><?php esc_html_e('Browse Tutorials', 'wiki-icp'); ?></span>
            <svg viewBox="0 0 320 512" aria-hidden="true" focusable="false"><path d="M192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32H320c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zM192 224c-17.7 0-32 14.3-32 32s14.3 32 32 32H320c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32H320c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zM32 104V72c0-13.3 10.7-24 24-24H88c13.3 0 24-10.7 24-24v32c0 13.3-10.7 24-24 24H56c-13.3 0-24-10.7-24-24zm0 160v-32c0-13.3 10.7-24 24-24H88c13.3 0 24-10.7 24-24v32c0 13.3-10.7 24-24 24H56c-13.3 0-24-10.7-24-24zm0 160v-32c0-13.3 10.7-24 24-24H88c13.3 0 24-10.7 24-24v32c0 13.3-10.7 24-24 24H56c-13.3 0-24-10.7-24-24z" /></svg>
        </button>
        <div class="tutorial-shell">
            <aside class="tutorial-sidebar" data-tutorial-sidebar>
                <div class="tutorial-sidebar__header">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400 mb-1"><?php esc_html_e('Tutorials', 'wiki-icp'); ?></p>
                        <h2 class="text-lg font-semibold text-white m-0">
                            <?php echo esc_html($portal_term ? sprintf(__('%s Video Library', 'wiki-icp'), $portal_term->name) : __('Video Library', 'wiki-icp')); ?>
                        </h2>
                    </div>
                    <button type="button" class="tutorial-sidebar__close" data-tutorial-sidebar-close aria-label="<?php esc_attr_e('Close tutorials', 'wiki-icp'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="tutorial-sidebar__body">
                    <?php
                    if ($sidebar_markup) {
                        echo $sidebar_markup;
                    } else {
                        echo '<p class="text-sm text-white/80">' . esc_html__('No tutorials available yet.', 'wiki-icp') . '</p>';
                    }
                    ?>
                </div>
            </aside>
            <div class="tutorial-content">
                <div class="tutorial-content__inner">
                    <div class="tutorial-video-wrapper">
                        <div class="tutorial-video" data-tutorial-video>
                            <?php
                            if ($default_video) {
                                echo $default_video; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                            ?>
                        </div>
                    </div>
                    <div class="tutorial-duration hidden" data-tutorial-duration-wrapper>
                        <span class="text-xs uppercase tracking-wide text-slate-500"><?php esc_html_e('Duration', 'wiki-icp'); ?></span>
                        <span class="text-sm font-semibold text-slate-900" data-tutorial-duration></span>
                    </div>
                    <div class="tutorial-body" data-tutorial-content>
                        <?php
                        if ($default_content) {
                            echo $default_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        } else {
                            echo '<p>' . esc_html__('Select a tutorial from the sidebar to begin.', 'wiki-icp') . '</p>';
                        }
                        ?>
                    </div>
                </div>
                <div class="tutorial-loader hidden" data-tutorial-loader>
                    <svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">
                        <circle cx="50" cy="50" r="35"></circle>
                    </svg>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
