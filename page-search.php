<?php
/**
 * Template Name: Knowledge Search
 */

get_header();

$search_query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$help_portals = get_terms([
    'taxonomy'   => 'category_help_topic',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

$tutorial_portals = get_terms([
    'taxonomy'   => 'tutorial_video_portal',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

$combined_portals = [];
if (!is_wp_error($help_portals)) {
    foreach ($help_portals as $term) {
        $combined_portals[$term->slug] = [
            'slug'       => $term->slug,
            'name'       => $term->name,
            'help_id'    => (int) $term->term_id,
            'tutorial_id'=> null,
        ];
    }
}

if (!is_wp_error($tutorial_portals)) {
    foreach ($tutorial_portals as $term) {
        if (!isset($combined_portals[$term->slug])) {
            $combined_portals[$term->slug] = [
                'slug'        => $term->slug,
                'name'        => $term->name,
                'help_id'     => null,
                'tutorial_id' => (int) $term->term_id,
            ];
        } else {
            $combined_portals[$term->slug]['tutorial_id'] = (int) $term->term_id;
        }
    }
}

$portal_json = wp_json_encode(array_values($combined_portals));
?>

<main id="primary" class="site-main">
    <section
        class="search-app"
        data-search-app
        data-portals='<?php echo esc_attr($portal_json); ?>'
        data-search-query="<?php echo esc_attr($search_query); ?>"
    >
        <div class="search-hero">
            <div>
                <p class="search-eyebrow"><?php esc_html_e('Knowledge Base', 'wiki-icp'); ?></p>
                <h1 class="search-title"><?php esc_html_e('How can we help?', 'wiki-icp'); ?></h1>
                <p class="search-subtitle"><?php esc_html_e('Search across help topics and tutorial videos.', 'wiki-icp'); ?></p>
            </div>
            <form class="search-form-lg" data-search-form>
                <label class="screen-reader-text" for="search-query"><?php esc_html_e('Search', 'wiki-icp'); ?></label>
                <input
                    id="search-query"
                    type="search"
                    name="q"
                    value="<?php echo esc_attr($search_query); ?>"
                    placeholder="<?php esc_attr_e('Search topics, tutorials, videos…', 'wiki-icp'); ?>"
                    data-search-input
                >
                <button type="submit"><?php esc_html_e('Search', 'wiki-icp'); ?></button>
            </form>
        </div>

        <div class="search-layout">
            <div class="search-main">
                <section class="ai-card">
                    <header>
                        <p><?php esc_html_e('AI Summary', 'wiki-icp'); ?></p>
                        <span class="ai-status" data-ai-status><?php esc_html_e('Thinking…', 'wiki-icp'); ?></span>
                    </header>
                    <div class="ai-body" data-ai-answer>
                        <p><?php esc_html_e('Enter a question or search above to see an AI-generated answer powered by your wiki.', 'wiki-icp'); ?></p>
                    </div>
                    <div class="ai-actions" data-ai-actions></div>
                </section>

                <div class="results-header">
                    <div>
                        <p class="results-count" data-results-count><?php esc_html_e('0 results', 'wiki-icp'); ?></p>
                        <p class="results-meta" data-results-meta></p>
                    </div>
                </div>

                <div class="search-results" data-search-results>
                    <p class="muted"><?php esc_html_e('Results will appear here once you search.', 'wiki-icp'); ?></p>
                </div>
            </div>

            <aside class="search-sidebar">
                <section class="filter-card">
                    <h3><?php esc_html_e('Filter By', 'wiki-icp'); ?></h3>
                    <div class="filter-section">
                        <p class="filter-label"><?php esc_html_e('Portal', 'wiki-icp'); ?></p>
                        <div class="filter-options" data-portal-filters></div>
                    </div>
                    <div class="filter-section">
                        <p class="filter-label"><?php esc_html_e('Type', 'wiki-icp'); ?></p>
                        <div class="filter-options">
                            <label>
                                <input type="checkbox" name="search-type" value="articles" checked>
                                <span><?php esc_html_e('Help Topics', 'wiki-icp'); ?></span>
                            </label>
                            <label>
                                <input type="checkbox" name="search-type" value="tutorials" checked>
                                <span><?php esc_html_e('Tutorial Videos', 'wiki-icp'); ?></span>
                            </label>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </section>
</main>

<?php
get_footer();
