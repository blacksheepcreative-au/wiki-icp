<?php
/**
 * Placeholder REST endpoint for future AI/search integrations.
 *
 * This lets us wire UI components to /wp-json/wiki-icp/v1/assist while
 * you finish the ingestion/vector workflow. Swap the callback internals
 * once the external AI service is ready.
 */

add_action('rest_api_init', function () {
    register_rest_route('wiki-icp/v1', '/assist', [
        'methods'             => ['POST'],
        'permission_callback' => '__return_true',
        'args'                => [
            'query' => [
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
        'callback'            => 'wiki_icp_ai_assist',
    ]);
});

/**
 * Temporary AI handler that will later call your LLM/vector stack.
 */
function wiki_icp_ai_assist(WP_REST_Request $request) {
    $query       = sanitize_text_field($request->get_param('query'));
    $portal_slug = sanitize_title($request->get_param('portal'));

    $portal_term          = $portal_slug ? get_term_by('slug', $portal_slug, WIKI_ICP_PORTAL_TAXONOMY) : null;
    $tutorial_portal_term = $portal_slug ? get_term_by('slug', $portal_slug, 'tutorial_video_portal') : null;

    $articles  = wiki_icp_collect_help_topics($query, $portal_term, 3);
    $tutorials = wiki_icp_collect_tutorial_videos($query, $tutorial_portal_term, 2);

    $context_items = wiki_icp_prioritize_ai_context($articles, $tutorials, $query);
    $context_items = wiki_icp_enrich_ai_context_items($context_items);
    $suggestions   = array_map(function ($item) {
        return [
            'title' => $item['title'],
            'link'  => $item['link'],
            'type'  => $item['type'],
            'subtype' => isset($item['subtype']) ? $item['subtype'] : '',
        ];
    }, $context_items);

    $ai = wiki_icp_generate_ai_summary($query, $portal_term, $portal_slug, $context_items);

    return rest_ensure_response([
        'query'       => $query,
        'suggestions' => $suggestions,
        'message'     => $ai['message'] ?? '',
        'status'      => $ai['status'] ?? '',
    ]);
}

/**
 * Prepare the AI summary payload for the REST response.
 *
 * @param string       $query
 * @param WP_Term|null $portal_term
 * @param string       $portal_slug
 * @param array        $context_items
 *
 * @return array{message:string,status:string}
 */
function wiki_icp_generate_ai_summary($query, $portal_term, $portal_slug, $context_items) {
    $result = [
        'message' => '',
        'status'  => 'disabled',
    ];

    $api_key = wiki_icp_get_openai_api_key();
    if (empty($api_key)) {
        return $result;
    }

    if (empty($query)) {
        return [
            'message' => '',
            'status'  => 'empty',
        ];
    }

    $prompt = wiki_icp_build_ai_prompt($query, $portal_term, $portal_slug, $context_items);
    if (!$prompt) {
        return [
            'message' => '',
            'status'  => 'empty',
        ];
    }

    $response = wiki_icp_request_openai_completion($prompt, $api_key);
    if (is_wp_error($response)) {
        error_log(sprintf('wiki-icp AI assist error: %s', $response->get_error_message()));
        return [
            'message' => '',
            'status'  => 'error',
        ];
    }

    $message = wiki_icp_normalize_ai_message($response);
    if ($message === '') {
        return [
            'message' => '',
            'status'  => 'empty',
        ];
    }

    return [
        'message' => $message,
        'status'  => 'ok',
    ];
}

/**
 * Fetch the API key either from a constant, environment variable, or filter.
 */
function wiki_icp_get_openai_api_key() {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $key = '';
    if (defined('WIKI_ICP_OPENAI_KEY') && WIKI_ICP_OPENAI_KEY) {
        $key = WIKI_ICP_OPENAI_KEY;
    } elseif (getenv('WIKI_ICP_OPENAI_KEY')) {
        $key = getenv('WIKI_ICP_OPENAI_KEY');
    }

    $key    = trim((string) $key);
    $cached = apply_filters('wiki_icp_ai_openai_key', $key);

    return $cached;
}

/**
 * Combine videos + help topics in the desired priority order for the AI prompt.
 *
 * @param array $articles
 * @param array $tutorials
 */
function wiki_icp_prioritize_ai_context($articles, $tutorials, $query = '') {
    $installations = [];
    $videos        = [];
    foreach ($tutorials as $item) {
        $subtype = isset($item['subtype']) ? $item['subtype'] : '';
        if ($subtype === 'installation') {
            $installations[] = $item;
        } else {
            $videos[] = $item;
        }
    }

    $install_focus = wiki_icp_query_mentions_installation($query);
    if ($install_focus) {
        return array_values(array_merge($installations, $videos, $articles));
    }

    return array_values(array_merge($articles, $videos, $installations));
}

function wiki_icp_enrich_ai_context_items(array $items) {
    foreach ($items as &$item) {
        if (!empty($item['id']) && $item['type'] === 'article') {
            $item['sections'] = wiki_icp_extract_help_topic_sections($item['id']);
            $item['body']     = wiki_icp_get_plain_excerpt_from_post($item['id'], 1400);
        }
    }

    return $items;
}

/**
 * Build the prompt that will be sent to ChatGPT.
 *
 * @param string       $query
 * @param WP_Term|null $portal_term
 * @param string       $portal_slug
 * @param array        $context_items
 */
function wiki_icp_build_ai_prompt($query, $portal_term, $portal_slug, $context_items) {
    $clean_query = wiki_icp_trim_for_prompt($query, 320);
    if ($clean_query === '') {
        return '';
    }

    $portal_label = '';
    if ($portal_term instanceof WP_Term) {
        $portal_label = $portal_term->name;
    } elseif (!empty($portal_slug)) {
        $portal_label = $portal_slug;
    }

    $sections = [];
    $sections[] = sprintf('Question: %s', $clean_query);
    if ($portal_label) {
        $sections[] = sprintf('Portal or brand focus: %s', $portal_label);
    }

    $references = [];
    $counter    = 1;
    foreach ($context_items as $item) {
        $line = wiki_icp_format_ai_reference_line($item, $counter++);
        if ($line) {
            $sections_text = wiki_icp_render_item_sections_for_prompt($item, $clean_query);
            if ($sections_text) {
                $line .= "\n    Sections:\n" . $sections_text;
            }
            $references[] = $line;
        }
    }

    if ($references) {
        $sections[] = "Reference material:\n" . implode("\n", $references);
    } else {
        $sections[] = 'Reference material: no direct matches were found in the wiki.';
    }
    $install_focus = wiki_icp_query_mentions_installation($clean_query);
    $instruction = 'Use the reference material to craft a concise, factual answer (two short paragraphs or fewer). Mention resource titles with their URLs in parentheses, focus on actionable steps, and never invent information that is not provided.';
    if ($install_focus) {
        $instruction .= ' The user is asking about installation or replacement steps, so highlight installation videos first when available.';
    } else {
        $instruction .= ' The user did not explicitly mention installation, so rely on detailed help topics first and only bring in installation or tutorial videos when they directly answer the question.';
    }
    $sections[] = 'Instructions: ' . $instruction;

    return implode("\n\n", array_filter($sections));
}

function wiki_icp_query_mentions_installation($query) {
    if (!$query) {
        return false;
    }
    $keywords = ['install', 'installation', 'replace', 'mount', 'fit', 'setup', 'hinge', 'fasten', 'drill'];
    $normalized = strtolower($query);
    foreach ($keywords as $keyword) {
        if (strpos($normalized, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Format a single article/tutorial line for the prompt.
 *
 * @param array $item
 * @param int   $index
 */
function wiki_icp_format_ai_reference_line($item, $index) {
    if (empty($item['title']) || empty($item['link'])) {
        return '';
    }

    $type    = !empty($item['typeLabel']) ? $item['typeLabel'] : (!empty($item['type']) ? ucfirst($item['type']) : __('Entry', 'wiki-icp'));
    $portal  = !empty($item['portal']) ? $item['portal'] : '';
    $summary = '';
    if (!empty($item['body'])) {
        $summary = wiki_icp_trim_for_prompt($item['body'], 280);
    } elseif (!empty($item['excerpt'])) {
        $summary = wiki_icp_trim_for_prompt($item['excerpt'], 200);
    }

    $line = sprintf('%d. %s — %s', (int) $index, $type, $item['title']);
    if ($portal) {
        $line .= sprintf(' (Portal: %s)', $portal);
    }
    $line .= sprintf(' — URL: %s', $item['link']);
    if ($summary) {
        $line .= sprintf(' — Summary: %s', $summary);
    }

    return $line;
}

function wiki_icp_render_item_sections_for_prompt($item, $query, $max_sections = 3) {
    if (empty($item['sections']) || !is_array($item['sections'])) {
        return '';
    }

    $terms = array_filter(preg_split('/\s+/', strtolower($query)), function ($term) {
        return strlen($term) >= 4;
    });

    $sections = [];
    foreach ($item['sections'] as $index => $section) {
        $heading = isset($section['heading']) ? $section['heading'] : '';
        $body    = isset($section['body']) ? $section['body'] : '';
        if ($body === '') {
            continue;
        }
        $score = 0;
        foreach ($terms as $term) {
            if ($term && stripos($heading, $term) !== false) {
                $score += 3;
            }
            if ($term && stripos($body, $term) !== false) {
                $score += 2;
            }
        }
        $sections[] = [
            'heading' => $heading,
            'body'    => $body,
            'score'   => $score,
            'index'   => $index,
        ];
    }

    usort($sections, function ($a, $b) {
        if ($a['score'] === $b['score']) {
            return $a['index'] <=> $b['index'];
        }
        return $b['score'] <=> $a['score'];
    });

    $sections = array_slice($sections, 0, $max_sections);
    if (empty($sections)) {
        return '';
    }

    $lines = [];
    foreach ($sections as $section) {
        $heading = $section['heading'] ?: __('Details', 'wiki-icp');
        $body    = wiki_icp_trim_for_prompt($section['body'], 260);
        $lines[] = sprintf('      • %s: %s', $heading, $body);
    }

    return implode("\n", $lines);
}

/**
 * Condense text into a single line for prompt construction.
 */
function wiki_icp_trim_for_prompt($text, $limit = 220) {
    $text = is_string($text) ? $text : '';
    $text = strip_tags($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim($text);

    if ($text === '' || $limit <= 0) {
        return $text;
    }

    if (function_exists('mb_strlen')) {
        if (mb_strlen($text) > $limit) {
            return mb_substr($text, 0, $limit) . '…';
        }
        return $text;
    }

    if (strlen($text) > $limit) {
        return substr($text, 0, $limit) . '…';
    }

    return $text;
}

/**
 * Send the HTTP request to OpenAI and return the best completion or a WP_Error.
 */
function wiki_icp_request_openai_completion($prompt, $api_key) {
    if (empty($prompt) || empty($api_key)) {
        return new WP_Error('wiki_icp_ai_missing_data', __('AI configuration is incomplete.', 'wiki-icp'));
    }

    $endpoint = apply_filters('wiki_icp_ai_endpoint', 'https://api.openai.com/v1/chat/completions');
    $body     = [
        'model'       => apply_filters('wiki_icp_ai_model', 'gpt-4o-mini'),
        'messages'    => [
            [
                'role'    => 'system',
                'content' => apply_filters(
                    'wiki_icp_ai_system_prompt',
                    'You are a knowledgeable glazing and fenestration support assistant. Only answer with the supplied reference material, cite resource titles with their URLs in parentheses, and keep responses concise, structured, and actionable. When the user explicitly mentions installation or replacement tasks, highlight installation videos first; otherwise rely on the most detailed help topics.'
                ),
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ],
        'temperature' => apply_filters('wiki_icp_ai_temperature', 0.2),
        'max_tokens'  => apply_filters('wiki_icp_ai_max_tokens', 320),
    ];

    $body     = apply_filters('wiki_icp_ai_request_body', $body, $prompt);
    $response = wp_remote_post($endpoint, [
        'timeout'    => 12,
        'headers'    => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ],
        'body'       => wp_json_encode($body),
        'user-agent' => 'wiki-icp-ai',
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw  = wp_remote_retrieve_body($response);
    $data = json_decode($raw, true);

    if ($code < 200 || $code >= 300) {
        $message = '';
        if (is_array($data) && !empty($data['error']['message'])) {
            $message = $data['error']['message'];
        }
        if (!$message) {
            $message = __('Unexpected response from AI service.', 'wiki-icp');
        }
        return new WP_Error('wiki_icp_ai_http_error', $message, ['status' => $code]);
    }

    if (!is_array($data) || empty($data['choices'][0]['message']['content'])) {
        return new WP_Error('wiki_icp_ai_invalid_response', __('AI response was empty.', 'wiki-icp'));
    }

    return (string) $data['choices'][0]['message']['content'];
}

/**
 * Sanitize and clip the AI message before returning it to the browser.
 */
function wiki_icp_normalize_ai_message($message) {
    $message = is_string($message) ? $message : '';
    $message = strip_tags($message);
    $message = preg_replace("/\r\n?/", "\n", $message);
    $message = preg_replace("/[ \t]+\n/", "\n", $message);
    $message = preg_replace("/\n{3,}/", "\n\n", $message);
    $message = trim($message);

    if ($message === '') {
        return '';
    }

    $limit = (int) apply_filters('wiki_icp_ai_message_length', 900);
    if ($limit > 0) {
        if (function_exists('mb_strlen')) {
            if (mb_strlen($message) > $limit) {
                return mb_substr($message, 0, $limit) . '…';
            }
            return $message;
        }

        if (strlen($message) > $limit) {
            return substr($message, 0, $limit) . '…';
        }
    }

    return $message;
}
