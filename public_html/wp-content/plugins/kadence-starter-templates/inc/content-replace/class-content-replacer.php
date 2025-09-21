<?php
/**
 * Content Replacer class
 *
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates\ContentReplace;

/**
 * Class to handle content replacement
 */
class Content_Replacer {

    /**
     * Strip string for rendering
     *
     * @param string $string The string to strip.
     * @return string
     */
    private static function strip_string_render($string) {
        return strtolower(preg_replace('/[^0-9a-z-]/', '', $string));
    }

    /**
     * Replace content with AI generated content
     *
     * @param string  $content   The content to replace.
     * @param array   $ai_content The AI generated content.
     * @param array   $categories The categories.
     * @param string  $context   The context.
     * @param string  $variation The variation.
     * @param boolean $is_html   Whether the content is HTML.
     * @param array   $page_data The page data.
     * @return string
     */
    public static function replace_content($content, $ai_content, $categories, $context, $variation, $is_html = false, $page_data = []) {
        if (!$content) {
            return $content;
        }

        if ( !empty( $context ) && $context === 'product-loop' ) {
            $context = 'products-services';
        }

        if (!isset($ai_content[$context]['content'])) {
            return $content;
        }

        $current_category = $categories ? $categories[0] : '';
        $context_ai = $ai_content[$context]['content'];
        
        // Find specific content types
        $base_content = self::find_content_by_id($context_ai, $context);
        $columns_content = self::find_content_by_id($context_ai, $context . '-columns');
        $list_content = self::find_content_by_id($context_ai, $context . '-list');
        $video_content = self::find_content_by_id($context_ai, $context . '-videos');
        $tabs_content = self::find_content_by_id($context_ai, $context . '-tabs');
        $accordion_content = self::find_content_by_id($context_ai, $context . '-accordion');
        $base_testimonial_content = !empty($ai_content['testimonials']['content']) ? self::find_content_by_id($ai_content['testimonials']['content'], 'testimonials-testimonials') : [];

        // Process content based on category
        switch ($current_category) {
            case 'columns':
                $content = self::process_columns_content($content, $base_content, $columns_content);
                break;
            case 'text':
                $content = self::process_text_content($content, $base_content, $columns_content);
                break;
            case 'hero':
            case 'call-to-action':
                $content = self::process_hero_content($content, $base_content, $context_ai, $context);
                break;
            case 'image':
                $content = self::process_image_content($content, $base_content);
                break;
            case 'title-or-header':
                $content = self::process_title_content($content, $base_content, $context_ai, $context, $page_data);
                break;
            case 'media-text':
            case 'donation-form':
                $content = self::process_media_text_content($content, $base_content, $list_content, $columns_content, $is_html);
                break;
            case 'accordion':
                $content = self::process_accordion_content($content, $base_content, $accordion_content, $is_html);
                break;
            case 'tabs':
                $content = self::process_tabs_content($content, $base_content, $tabs_content, $is_html);
                break;
            case 'video':
                $content = self::process_video_content($content, $base_content, $video_content, $list_content, $is_html);
                break;
            case 'cards':
                $content = self::process_cards_content($content, $base_content, $columns_content, $context_ai, $context, $is_html);
                break;
            case 'testimonials':
                $content = self::process_testimonials_content($content, $base_content, $context_ai, $context, $is_html);
                break;
            case 'pricing-table':
                $content = self::process_pricing_table_content($content, $base_content, $context_ai, $context, $is_html);
                break;
            case 'post-loop':
                $content = self::process_post_loop_content($content, $base_content, $context_ai, $context);
                break;
            case 'team':
                $content = self::process_team_content($content, $base_content, $context_ai, $context);
                break;
            case 'logo-farm':
                $content = self::process_logo_farm_content($content, $base_content);
                break;
            case 'location':
                $content = self::process_location_content($content, $base_content, $columns_content);
                break;
            case 'gallery':
                $content = self::process_gallery_content($content, $base_content);
                break;
            case 'featured-products':
            case 'featured-product':
                $content = self::process_featured_products_content($content, $base_content, $context_ai, $context, $is_html);
                break;
            case 'product-loop':
                $content = self::process_product_loop_content($content, $base_content, $columns_content, $context_ai, $context);
                break;
            case 'form':
                $content = self::process_form_content($content, $base_content, $ai_content);
                break;
            case 'table-of-contents':
                $content = self::process_table_of_contents_content($content, $base_content, $context_ai, $context);
                break;
            case 'counter-or-stats':
                $content = self::process_counter_or_stats_content($content, $base_content, $context_ai, $base_testimonial_content, $context, $is_html);
                break;
            case 'list':
                $content = self::process_list_content($content, $base_content, $context_ai, $context, $is_html);
                break;
            case 'slider':
                $content = self::process_slider_content($content, $base_content, $context_ai, $context, $is_html);
                break;
        }

        return $content;
    }

    /**
     * Find content by ID in array
     *
     * @param array  $content_array The content array to search.
     * @param string $id           The ID to find.
     * @return array|null
     */
    private static function find_content_by_id($content_array, $id) {
        foreach ($content_array as $content) {
            if (isset($content['id']) && $content['id'] === $id) {
                return $content;
            }
        }
        return null;
    }

    /**
     * Process columns content
     *
     * @param string $content        The content to process.
     * @param array  $base_content   The base content.
     * @param array  $columns_content The columns content.
     * @return string
     */
    private static function process_columns_content($content, $base_content, $columns_content) {
        // Replace headline
        if (isset($base_content['heading']['short'])) {
            $content = str_replace('Write a short headline', $base_content['heading']['short'], $content);
        }

        if (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Compose a captivating title for this section.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Replace paragraph
        if (isset($base_content['sentence']['short'])) {
            $content = str_replace(
                'Support your idea with a clear, descriptive sentence or phrase that has a consistent writing style.',
                $base_content['sentence']['short'],
                $content
            );
        }

        // Process columns if they exist
        if (isset($columns_content['columns'])) {
            foreach ($columns_content['columns'] as $column) {
                if (isset($column['title-medium'])) {
                    $replacement = 'Add a descriptive title for the column.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['title-medium'], $pos, strlen($replacement));
                    }
                }
                if (isset($column['sentence-short'])) {
                    $replacement = 'Add context to your column. Help visitors understand the value they can get from your products and services.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['sentence-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process text content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $columns_content The columns content.
     * @return string
     */
    private static function process_text_content($content, $base_content, $columns_content) {
        // Headline Short
        if (isset($base_content['heading']['short'])) {
            $content = str_replace('Type a short headline', $base_content['heading']['short'], $content);
        }

        // Headline
        if (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Briefly and concisely explain what you do for your audience.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Paragraph long
        if (isset($base_content['sentence']['long'])) {
            $content = str_replace(
                'Use this paragraph section to get your website visitors to know you. Write about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style. Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.',
                $base_content['sentence']['long'],
                $content
            );
        }

        // Paragraph Medium
        if (isset($base_content['sentence']['medium'])) {
            $content = str_replace(
                'Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.',
                $base_content['sentence']['medium'],
                $content
            );
        }

        // Paragraph Short
        if (isset($base_content['sentence']['short'])) {
            $content = str_replace(
                'Consider using this if you need to provide more context on why you do what you do.',
                $base_content['sentence']['short'],
                $content
            );
            $content = str_replace(
                'Consider using this if you need to provide more context on why you do what you do. Be engaging.',
                $base_content['sentence']['short'],
                $content
            );
        }

        // Overline
        if (isset($base_content['overline']['short'])) {
            $replacements = [
                '2018 - Current',
                'Add an overline text',
                'Overline'
            ];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $base_content['overline']['short'], $content);
            }
        }

        // Button
        if (isset($base_content['button']['short'])) {
            $content = str_replace('Call To Action', $base_content['button']['short'], $content);
            $content = str_replace('Call to Action', $base_content['button']['short'], $content);
        }

        // Process columns if they exist
        if (isset($columns_content['columns'])) {
            foreach ($columns_content['columns'] as $column) {
                // Title short
                if (isset($column['title-short'])) {
                    $replacement = 'Add a short title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['title-short'], $pos, strlen($replacement));
                    }
                }
                // Sentence short
                if (isset($column['sentence-short'])) {
                    $replacement = 'Use this space to add a short description.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['sentence-short'], $pos, strlen($replacement));
                    }
                }
                // Sentence medium
                if (isset($column['sentence-medium'])) {
                    $replacement = 'Use this space to add a medium length description. Be brief and give enough information to earn their attention.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['sentence-medium'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process hero content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_hero_content($content, $base_content, $context_ai, $context) {
        $hero_content = self::find_content_by_id($context_ai, $context . '-hero');

        // Headline
        if (isset($hero_content['heading']['medium'])) {
            $content = str_replace(
                'Briefly and concisely explain what you do for your audience.',
                $hero_content['heading']['medium'],
                $content
            );
        } elseif (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Briefly and concisely explain what you do for your audience.',
                $base_content['heading']['medium'],
                $content
            );
        } elseif (isset($context_ai[0]['id']) && $context_ai[0]['id'] === 'contact-form') {
            $content = str_replace(
                'Briefly and concisely explain what you do for your audience.',
                'Contact Us',
                $content
            );
        }
        // Short Headline
        if (isset($page_data['title']) && in_array($page_data['title'], ['Contact', 'About', 'Services', 'Reviews', 'Pricing', 'FAQ', 'Courses', 'Our Mission', 'Gallery', 'Schedule'])) {
			$content = str_replace('Write a brief title', $page_data['title'], $content);
         } else {
            if (isset($hero_content['heading']['short'])) {
                $content = str_replace('Write a brief title', $hero_content['heading']['short'], $content);
            } elseif (isset($base_content['heading']['short'])) {
                $content = str_replace('Write a brief title', $base_content['heading']['short'], $content);
            } elseif (isset($context_ai[0]['id']) && $context_ai[0]['id'] === 'contact-form') {
                $content = str_replace('Write a brief title', 'Contact Us', $content);
            }
        }

        // overline
        if ( isset( $hero_content['overline']['short'] ) ) {
            $content = str_replace( 'ADD AN OVERLINE TEXT', $hero_content['overline']['short'], $content );
            $content = str_replace( 'Add an overline text', $hero_content['overline']['short'], $content );
            $content = str_replace( 'Overline', $hero_content['overline']['short'], $content );
        } elseif ( isset( $base_content['overline']['short'] ) ) {
            $content = str_replace( 'ADD AN OVERLINE TEXT', $base_content['overline']['short'], $content );
            $content = str_replace( 'Add an overline text', $base_content['overline']['short'], $content );
            $content = str_replace( 'Overline', $base_content['overline']['short'], $content );
        } elseif ( isset( $context_ai[0]['id']) && $context_ai[0]['id'] === 'contact-form' && isset( $context_ai[0]['heading']['short'] ) ) {
            $content = str_replace( 'Overline', $context_ai[0]['heading']['short'], $content );
        }
        // Paragraph
        if ( isset( $hero_content['sentence']['short'] ) ) {
            $content = str_replace( 'Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.', $hero_content['sentence']['short'], $content );
        } elseif ( isset( $base_content['sentence']['short'] ) ) {
            $content = str_replace( 'Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.', $base_content['sentence']['short'], $content );
        } elseif ( isset( $context_ai[0]['id']) && $context_ai[0]['id'] === 'contact-form' && isset( $context_ai[0]['sentence']['short'] ) ) {
            $content = str_replace( 'Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.', $context_ai[0]['sentence']['short'], $content );
        }
        // Button
        if ( isset( $hero_content['button']['short'] ) ) {
            $content = str_replace( 'Call To Action', $hero_content['button']['short'], $content );
            $content = str_replace( 'Call to Action', $hero_content['button']['short'], $content );
        } elseif ( isset( $base_content['button']['short'] ) ) {
            $content = str_replace( 'Call To Action', $base_content['button']['short'], $content );
            $content = str_replace( 'Call to Action', $base_content['button']['short'], $content );
        }
        // Secondary Button
        if ( isset( $hero_content['secondary-button']['short'] ) ) {
            $content = str_replace( 'Secondary Button', $hero_content['secondary-button']['short'], $content );
        } elseif ( isset( $base_content['secondary-button']['short'] ) ) {
            $content = str_replace( 'Secondary Button', $base_content['secondary-button']['short'], $content );
        }

        // Process cards if they exist
        if (isset($hero_content['cards'])) {
            foreach ($hero_content['cards'] as $card) {
                if (isset($card['title-short'])) {
                    $replacement = 'Add a Title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $card['title-short'], $pos, strlen($replacement));
                    }
                }
                if (isset($card['sentence-short'])) {
                    $replacement = 'Use this space to add a short description.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $card['sentence-short'], $pos, strlen($replacement));
                    }
                }
                if (isset($card['sentence-medium'])) {
                    $replacement = 'Use this space to add a medium length description. Be brief and give enough information to earn a click.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $card['sentence-medium'], $pos, strlen($replacement));
                    }
                }
                if (isset($card['button-short'])) {
                    $replacement = 'CTA';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $card['button-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process image content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @return string
     */
    private static function process_image_content($content, $base_content) {
        // Headline
        if (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Add a short, consistent heading for your image.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Short Headline
        if (isset($base_content['heading']['short'])) {
            $content = str_replace('Add a short headline', $base_content['heading']['short'], $content);
        }

        // Paragraph
        if (isset($base_content['sentence']['short'])) {
            $content = str_replace(
                'Use this paragraph to add supporting context. Consider your audience and what matters to them, and provide insights that support your topic.',
                $base_content['sentence']['short'],
                $content
            );
        }

        // Button
        if (isset($base_content['button']['short'])) {
            $content = str_replace('Call To Action', $base_content['button']['short'], $content);
            $content = str_replace('Call to Action', $base_content['button']['short'], $content);
        }

        return $content;
    }

    /**
     * Process title content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @param array  $page_data The page data.
     * @return string
     */
    private static function process_title_content($content, $base_content, $context_ai, $context, $page_data) {
        $title_content = self::find_content_by_id($context_ai, $context . '-hero');

        // Headline
        if (isset($title_content['heading']['medium'])) {
            $content = str_replace(
                'Craft a captivating title for this section to attract your audience.',
                $title_content['heading']['medium'],
                $content
            );
            $content = str_replace(
                'Craft a captivating title for the upcoming section to attract your audience.',
                $title_content['heading']['medium'],
                $content
            );
        } elseif (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Craft a captivating title for this section to attract your audience.',
                $base_content['heading']['medium'],
                $content
            );
            $content = str_replace(
                'Craft a captivating title for the upcoming section to attract your audience.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Short Headline
		if (isset($page_data['title']) && 
                in_array($page_data['title'], ['Contact', 'About', 'Services', 'Reviews', 'Pricing', 'Courses', 'Our Mission', 'Gallery', 'Schedule'])) {
			$content = str_replace('Add a short & sweet headline', $page_data['title'], $content);
			$content = str_replace('Add a short &amp; sweet headline', $page_data['title'], $content);
		} else if (isset($title_content['heading']['short'])) {
			$content = str_replace('Add a short & sweet headline', $title_content['heading']['short'], $content);
			$content = str_replace('Add a short &amp; sweet headline', $title_content['heading']['short'], $content);
        } elseif (isset($base_content['heading']['short'])) {
            $content = str_replace('Add a short & sweet headline', $base_content['heading']['short'], $content);
            $content = str_replace('Add a short &amp; sweet headline', $base_content['heading']['short'], $content);
        }

        // Overline
        if (isset($title_content['overline']['short'])) {
            $replacements = [
                'ADD AN OVERLINE TEXT',
                'Add an overline text',
                'Overline'
            ];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $title_content['overline']['short'], $content);
            }
        } elseif (isset($base_content['overline']['short'])) {
            $replacements = [
                'ADD AN OVERLINE TEXT',
                'Add an overline text',
                'Overline'
            ];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $base_content['overline']['short'], $content);
            }
        }

        // Button
        if (isset($title_content['button']['short'])) {
            $content = str_replace('Call To Action', $title_content['button']['short'], $content);
            $content = str_replace('Call to Action', $title_content['button']['short'], $content);
        } elseif (isset($base_content['button']['short'])) {
            $content = str_replace('Call To Action', $base_content['button']['short'], $content);
            $content = str_replace('Call to Action', $base_content['button']['short'], $content);
        }

        return $content;
    }

    /**
     * Process media text content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $list_content The list content.
     * @param array  $columns_content The columns content.
     * @param boolean $is_html Whether content is HTML.
     * @return string
     */
    private static function process_media_text_content($content, $base_content, $list_content, $columns_content, $is_html) {
        // Headline short
        if (isset($base_content['heading']['short'])) {
            $content = str_replace('Write a short headline', $base_content['heading']['short'], $content);
        }

        // Headline medium
        if (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Add a compelling title for your section to engage your audience.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Headline long
        if (isset($base_content['heading']['long'])) {
            $content = str_replace(
                'Write a compelling and inviting headline to re-hook your visitors through your content.',
                $base_content['heading']['long'],
                $content
            );
        }
         // Paragraph
         if (isset($base_content['sentence']['short'])) {
            $content = str_replace(
                'Use this paragraph to provide more insights writing with clear and concise language that is easy to understand. Edit and proofread your content.',
                $base_content['sentence']['short'],
                $content
            );
        }
        // Paragraph
        if (isset($base_content['sentence']['medium'])) {
            $content = str_replace(
                'Use this paragraph section to get your website visitors to know you. Consider writing about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style.',
                $base_content['sentence']['medium'],
                $content
            );
        }
        // Paragraph
        if (isset($base_content['sentence']['long'])) {
            $content = str_replace(
                'Use this paragraph section to get your website visitors to know you. Write about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style. Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.',
                $base_content['sentence']['long'],
                $content
            );
        }
        

        // Overline
        if (isset($base_content['overline']['short'])) {
            $content = str_replace('ADD AN OVERLINE', $base_content['overline']['short'], $content);
            $content = str_replace('Add an overline', $base_content['overline']['short'], $content);
            $content = str_replace('Overline', $base_content['overline']['short'], $content);
        }

        // Button
        if (isset($base_content['button']['short'])) {
            $content = str_replace('Call To Action', $base_content['button']['short'], $content);
            $content = str_replace('Call to Action', $base_content['button']['short'], $content);
        }

        // Secondary Button
        if (isset($base_content['secondary-button']['short'])) {
            $content = str_replace('Secondary Button', $base_content['secondary-button']['short'], $content);
        }

        // Process list items if they exist
        if (isset($list_content['list'])) {
            foreach ($list_content['list'] as $item) {
                if (isset($item['list-item-short'])) {
                    if (!$is_html) {
                        $replacement = '"text":"Add a list item"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $item['list-item-short'] . '"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = 'Add a list item';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $item['list-item-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        // Process columns if they exist
        if (isset($columns_content['columns'])) {
            foreach ($columns_content['columns'] as $column) {
                if (isset($column['title-medium'])) {
                    $replacement = 'Add a descriptive title for the column.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['title-medium'], $pos, strlen($replacement));
                    }
                }
                if (isset($column['sentence-medium'])) {
                    $replacement = 'Use this space to add a medium length description. Be brief and give enough information to earn their attention.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['sentence-medium'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process accordion content
     *
     * @param string  $content The content to process.
     * @param array   $base_content The base content.
     * @param array   $accordion_content The accordion content.
     * @param boolean $is_html Whether content is HTML.
     * @return string
     */
    private static function process_accordion_content($content, $base_content, $accordion_content, $is_html) {
        // Headline short
        if (isset($accordion_content['heading']['short'])) {
            $content = str_replace('Add a short headline', $accordion_content['heading']['short'], $content);
        } elseif (isset($base_content['heading']['short'])) {
            $content = str_replace('Add a short headline', $base_content['heading']['short'], $content);
        }

        // Headline medium
        if (isset($accordion_content['heading']['medium'])) {
            $content = str_replace(
                'A brief headline here will add context for the section',
                $accordion_content['heading']['medium'],
                $content
            );
        } elseif (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'A brief headline here will add context for the section',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Paragraph
        if (isset($accordion_content['sentence']['medium'])) {
            $content = str_replace(
                'Use this space to provide your website visitors with a brief description on what to expect before clicking on a section title.',
                $accordion_content['sentence']['medium'],
                $content
            );
        } elseif (isset($base_content['sentence']['medium'])) {
            $content = str_replace(
                'Use this space to provide your website visitors with a brief description on what to expect before clicking on a section title.',
                $base_content['sentence']['medium'],
                $content
            );
        }

        // Process accordion items
        if (isset($accordion_content['accordion'])) {
            foreach ($accordion_content['accordion'] as $item) {
                // Title
                if (isset($item['title-medium'])) {
                    if (!$is_html) {
                        $replacement = '"text":"Add a section title that is relevant for your readers."';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $item['title-medium'] . '"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = 'Add a section title that is relevant for your readers.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $item['title-medium'], $pos, strlen($replacement));
                    }
                    $replacement = 'tab-addasectiontitlethatisrelevantforyourreaders';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, 'tab-' . self::strip_string_render($item['title-medium']), $pos, strlen($replacement));
                    }
                }

                // Paragraph
                if (isset($item['paragraph-medium'])) {
                    $replacement = 'By default, this panel is concealed and appears when the user clicks on the section title. Input relevant information about its title using paragraphs or bullet points. Accordions can enhance the user experience when utilized effectively. They allow users to choose what they want to read and disregard the rest. Accordions are often utilized for frequently asked questions (FAQs).';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $item['paragraph-medium'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process tabs content
     *
     * @param string  $content The content to process.
     * @param array   $base_content The base content.
     * @param array   $tabs_content The tabs content.
     * @param boolean $is_html Whether content is HTML.
     * @return string
     */
    private static function process_tabs_content($content, $base_content, $tabs_content, $is_html) {
        // Headline short
        if (isset($base_content['heading']['short'])) {
            $content = str_replace('Add a short headline', $base_content['heading']['short'], $content);
        }

        // Sentence medium
        if (isset($base_content['sentence']['medium'])) {
            $content = str_replace(
                'Tabs are a helpful way that allow users to view a group of related data one at a time. Add a brief description of what your tabbed section is about.',
                $base_content['sentence']['medium'],
                $content
            );
        }

        // Overline
        if (isset($base_content['overline']['short'])) {
            $replacements = ['ADD AN OVERLINE TEXT', 'Add an overline text', 'Overline'];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $base_content['overline']['short'], $content);
            }
        }

        // Button
        if (isset($base_content['button']['short'])) {
            $content = str_replace('Call To Action', $base_content['button']['short'], $content);
            $content = str_replace('Call to Action', $base_content['button']['short'], $content);
        }

        // Process tabs
        if (isset($tabs_content['tabs'])) {
            foreach ($tabs_content['tabs'] as $tab) {
                // Title short
                if (isset($tab['title-short'])) {
                    if (!$is_html) {
                        $replacement = '"text":"Tab name"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $tab['title-short'] . '"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = 'Tab name';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $tab['title-short'], $pos, strlen($replacement));
                    }
                    $replacement = 'tab-tabname';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, 'tab-' . self::strip_string_render($tab['title-short']), $pos, strlen($replacement));
                    }
                }

                $replacements = [
                    ['title-medium', 'Give this tab a concise name'],
                    ['overline-short', 'Overline'],
                    ['button-short', 'Call To Action'],
                    ['list-title', 'Featured subhead']
                ];

                foreach ($replacements as [$key, $search]) {
                    if (isset($tab[$key])) {
                        $pos = strpos($content, $search);
                        if (false !== $pos) {
                            $content = substr_replace($content, $tab[$key], $pos, strlen($search));
                        }
                    }
                }

                // Process list items
                for ($i = 1; $i <= 3; $i++) {
                    if (isset($tab['list-item-' . $i])) {
                        $replacement = 'Add a single and succinct list item';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, $tab['list-item-' . $i], $pos, strlen($replacement));
                        }
                    }
                }

                // Process descriptions
                for ($i = 1; $i <= 3; $i++) {
                    if (isset($tab['description-' . $i])) {
                        $replacement = 'Add context to your column. Help visitors understand the value they can get from your products and services.';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, $tab['description-' . $i], $pos, strlen($replacement));
                        }
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process video content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $video_content The video content.
     * @param array  $list_content The list content.
     * @param boolean $is_html Whether content is HTML.
     * @return string
     */
    private static function process_video_content($content, $base_content, $video_content, $list_content, $is_html) {
        // Headline medium
        if (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Add a brief headline for impact and / or context here',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Headline short
        if (isset($base_content['heading']['short'])) {
            $content = str_replace('Write a succinct headline here', $base_content['heading']['short'], $content);
        }

        // Headline long
        if (isset($base_content['heading']['long'])) {
            $content = str_replace(
                'Write a compelling and inviting headline to re-hook your visitors through your content.',
                $base_content['heading']['long'],
                $content
            );
        }

        // Process sentences
        $sentences = [
            'medium' => 'Use this paragraph to provide more insights writing with clear and concise language that is easy to understand. Edit and proofread your content.',
            'short' => 'Support your idea with a clear, descriptive sentence or phrase that has a consistent writing style.'
        ];

        foreach ($sentences as $type => $search) {
            if (isset($base_content['sentence'][$type])) {
                $content = str_replace($search, $base_content['sentence'][$type], $content);
            }
        }

        // Process video titles
        if (isset($video_content['videos'])) {
            foreach ($video_content['videos'] as $video) {
                if (isset($video['title-short'])) {
                    $replacement = 'Short title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $video['title-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        // Process list items
        if (isset($list_content['list'])) {
            foreach ($list_content['list'] as $item) {
                if (isset($item['list-item-short'])) {
                    if (!$is_html) {
                        $replacement = '"text":"Add a list item"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $item['list-item-short'] . '"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = 'Add a list item';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $item['list-item-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process cards content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $columns_content The columns content.
     * @param boolean $is_html Whether content is HTML.
     * @return string
     */
    private static function process_cards_content($content, $base_content, $columns_content, $context_ai, $context, $is_html) {
        // Headline medium
        if (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Craft a captivating title for this section to attract your audience.',
                $base_content['heading']['medium'],
                $content
            );
            $content = str_replace(
                'A short and sweet title for this section.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Paragraph medium
        if (isset($base_content['sentence']['medium'])) {
            $content = str_replace(
                'Use a clear and attention-grabbing short paragraph to engage your audience and draw them into reading the rest of your content.',
                $base_content['sentence']['medium'],
                $content
            );
        }

        // Overline
        if (isset($base_content['overline']['short'])) {
            $replacements = ['ADD AN OVERLINE', 'Add an overline', 'Overline'];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $base_content['overline']['short'], $content);
            }
        }

        // Button
        if (isset($base_content['button']['short'])) {
            $content = str_replace('Call To Action', $base_content['button']['short'], $content);
            $content = str_replace('Call to Action', $base_content['button']['short'], $content);
        }

        // Secondary Button
        if (isset($base_content['secondary-button']['short'])) {
            $content = str_replace('Secondary Button', $base_content['secondary-button']['short'], $content);
        }

        // Process cards/columns
        if (isset($columns_content['columns'])) {
            foreach ($columns_content['columns'] as $column) {
                // Process various card content types
                $replacements = [
                    ['title-short', 'Add a Title'],
                    ['title-medium', 'Add a Short Title Here'],
                    ['sentence-short', ['Use this space to add a short description. It gives enough info to earn a click.', 'Add a brief description to your card.']],
                    ['sentence-medium', 'Use this space to add a medium length description. Be brief and give enough information to earn a click.'],
                    ['overline-short', 'Overline'],
                    ['button-short', ['Call To Action', 'Call to Action']]
                ];

                foreach ($replacements as [$key, $search]) {
                    if (isset($column[$key])) {
                        if (is_array($search)) {
                            foreach ($search as $s) {
                                $pos = strpos($content, $s);
                                if (false !== $pos) {
                                    $content = substr_replace($content, $column[$key], $pos, strlen($s));
                                }
                            }
                        } else {
                            $pos = strpos($content, $search);
                            if (false !== $pos) {
                                $content = substr_replace($content, $column[$key], $pos, strlen($search));
                            }
                        }
                    }
                }
            }
        }
        $counter_content = self::find_content_by_id($context_ai, $context . '-counter-stats');
         // Metrics
        if ( isset($counter_content['metrics']) ) {
            foreach ($counter_content['metrics'] as $index => $metric) {
                // Title.
                if ( isset($counter_content['metrics'][$index]['title-short']) ) {
                    if ( ! $is_html ) {
                        $replacement = '"title":"Stat title"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"title":"'. $counter_content['metrics'][$index]['title-short'] .'"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = 'Stat title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $counter_content['metrics'][$index]['title-short'], $pos, strlen($replacement));
                    }
                }
                // Price.
                if ( isset($counter_content['metrics'][$index]['value-short']) ) {
                    if ( 0 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "50%", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":50', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="50', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 1 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "98%", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( "98", absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( '"end":98', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="98', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 2 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "100,110", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":100110', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="100110', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 3 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "8/mo", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":8', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="8', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 4 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "20yr", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":20', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="20', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 5 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "18,110", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":18110', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="18110', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Process testimonials content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_testimonials_content($content, $base_content, $context_ai, $context, $is_html) {
        $testimonial_content = self::find_content_by_id($context_ai, $context . '-testimonials');

        // Overline
        if (isset($testimonial_content['overline']['short'])) {
            $replacements = ['ADD AN OVERLINE', 'Add an overline', 'Overline'];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $testimonial_content['overline']['short'], $content);
            }
        } elseif (isset($base_content['overline']['short'])) {
            $replacements = ['ADD AN OVERLINE', 'Add an overline', 'Overline'];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $base_content['overline']['short'], $content);
            }
        }
        // Button
        if (isset($testimonial_content['button']['short'])) {
            $replacements = ['Call To Action', 'Call to Action'];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $testimonial_content['button']['short'], $content);
            }
        } elseif (isset($base_content['button']['short'])) {
            $replacements = ['Call To Action', 'Call to Action'];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $base_content['button']['short'], $content);
            }
        }
        // Headline short
        if (isset($testimonial_content['heading']['short'])) {
            $content = str_replace(
                'Type a short headline',
                $testimonial_content['heading']['short'],
                $content
            );
        } elseif (isset($base_content['heading']['short'])) {
            $content = str_replace(
                'Type a short headline',
                $base_content['heading']['short'],
                $content
            );
        }
        // Headline medium
        if (isset($testimonial_content['heading']['medium'])) {
            $content = str_replace(
                'Add a compelling title for your section to engage your audience.',
                $testimonial_content['heading']['medium'],
                $content
            );
        } elseif (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Add a compelling title for your section to engage your audience.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Paragraph long
        $long_text = 'Use this paragraph section to get your website visitors to know you. Consider writing about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style.';
        if (isset($testimonial_content['sentence']['long'])) {
            $content = str_replace($long_text, $testimonial_content['sentence']['long'], $content);
        } elseif (isset($base_content['sentence']['long'])) {
            $content = str_replace($long_text, $base_content['sentence']['long'], $content);
        }

        // Process testimonials
        if (isset($testimonial_content['testimonials'])) {
            foreach ($testimonial_content['testimonials'] as $testimonial) {
                if (isset($testimonial['customer'])) {
                    $replacement = 'Customer Name';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $testimonial['customer'], $pos, strlen($replacement));
                    }
                }
                if (isset($testimonial['customer-name'])) {
                    $replacement = 'Customer Name';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $testimonial['customer-name'], $pos, strlen($replacement));
                    }
                }
                if (isset($testimonial['customer-occupation'])) {
                    $replacement = 'Customer Title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $testimonial['customer-occupation'], $pos, strlen($replacement));
                    }
                }
                if (isset($testimonial['testimonial'])) {
                    $replacements = [
                        'Testimonials are a social proof, a powerful way to inspire trust.',
                        'Testimonials, as authentic endorsements from satisfied customers, serve as potent social proof, significantly inspiring trust in potential consumers.'
                    ];
                    foreach ($replacements as $replacement) {
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, $testimonial['testimonial'], $pos, strlen($replacement));
                        }
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process pricing table content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @param boolean $is_html Whether content is HTML.
     * @return string
     */
    private static function process_pricing_table_content($content, $base_content, $context_ai, $context, $is_html) {
        $pricing_table_content = self::find_content_by_id($context_ai, 'pricing-' . $context);

        // Headline short
        if (isset($pricing_table_content['heading']['short'])) {
            $content = str_replace('Write a short headline', $pricing_table_content['heading']['short'], $content);
            $content = str_replace(
                'Add a compelling title for your section to engage your audience.',
                $pricing_table_content['heading']['short'],
                $content
            );
        }

        // Paragraph short/medium/long
        $paragraphs = [
            'short' => 'Add a gripping description for this featured plan',
            'medium' => 'A pricing table assists users in selecting a suitable plan by simply and clearly differentiating product/service features and prices. Use this as supporting text for your plans.',
            'long' => 'A pricing table assists users in selecting a suitable plan by simply and clearly differentiating product/service features and prices. Use this as supporting text for your plans.'
        ];

        foreach ($paragraphs as $type => $search) {
            if (isset($pricing_table_content['sentence'][$type])) {
                $content = str_replace($search, $pricing_table_content['sentence'][$type], $content);
            }
        }

        // Overline
        if (isset($pricing_table_content['overline']['short'])) {
            $content = str_replace('add an overline text', $pricing_table_content['overline']['short'], $content);
            $content = str_replace('Add an overline text', $pricing_table_content['overline']['short'], $content);
        }

        // Process plans
        if (isset($pricing_table_content['plans'])) {
            foreach ($pricing_table_content['plans'] as $index => $plan) {
                // Title
                if (isset($plan['title-short'])) {
                    $replacement = 'Tab Title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $plan['title-short'], $pos, strlen($replacement));
                    }
                }
                if (isset($plan['title-medium'])) {
                    $replacement = 'Name your plan';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $plan['title-medium'], $pos, strlen($replacement));
                    }
                    $replacement = 'Add a descriptive title for your plan.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $plan['title-medium'], $pos, strlen($replacement));
                    }
                }

                // Price
                if (isset($plan['price'])) {
                    $price = preg_replace('/\/(?:month|year)/', '', $plan['price']);
                    $price_map = ['$60', '$80', '$120', '$200'];
                    if (isset($price_map[$index]) && strlen($price) < 8) {
                        $replacement = $price_map[$index];
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, $price, $pos, strlen($replacement));
                        }
                    }
                }

                // Features
                for ($i = 1; $i <= 3; $i++) {
                    if (isset($plan['feature-' . $i])) {
                        $feature_map = [
                            1 => 'Focus on the differences',
                            2 => 'Use a consistent language',
                            3 => 'Transmit benefits clearly'
                        ];
                        if (!$is_html) {
                            $replacement = $feature_map[$i];
                            $pos = strpos($content, $replacement);
                            if (false !== $pos) {
                                $content = substr_replace($content, $plan['feature-' . $i], $pos, strlen($replacement));
                            }
                        }
                        $replacement = $feature_map[$i];
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, $plan['feature-' . $i], $pos, strlen($replacement));
                        }
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process post loop content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_post_loop_content($content, $base_content, $context_ai, $context) {
        $post_loop_content = self::find_content_by_id($context_ai, $context . '-post-loop');

        // Headline short
        if (isset($post_loop_content['heading']['short'])) {
            $content = str_replace('Selected posts title', $post_loop_content['heading']['short'], $content);
        } elseif (isset($base_content['heading']['short'])) {
            $content = str_replace('Selected posts title', $base_content['heading']['short'], $content);
        }

        // Headline medium
        if (isset($post_loop_content['heading']['medium'])) {
            $content = str_replace(
                'Craft a captivating title for this section to attract your audience.',
                $post_loop_content['heading']['medium'],
                $content
            );
        } elseif (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Craft a captivating title for this section to attract your audience.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Process other content types with fallbacks
        $replacements = [
            ['sentence', 'short', 'Use a clear and attention-grabbing short paragraph to engage your audience and draw them into reading the rest of your content.'],
            ['overline', 'short', ['ADD AN OVERLINE', 'Add an overline', 'Overline']],
            ['button', 'short', ['Call To Action', 'Call to Action']]
        ];

        foreach ($replacements as [$type, $length, $search]) {
            $content_value = isset($post_loop_content[$type][$length]) ? 
                $post_loop_content[$type][$length] : 
                (isset($base_content[$type][$length]) ? $base_content[$type][$length] : null);

            if ($content_value) {
                if (is_array($search)) {
                    foreach ($search as $s) {
                        $content = str_replace($s, $content_value, $content);
                    }
                } else {
                    $content = str_replace($search, $content_value, $content);
                }
            }
        }

        return $content;
    }

    /**
     * Process team content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_team_content($content, $base_content, $context_ai, $context) {
        $people_content = self::find_content_by_id($context_ai, $context . '-people');

        // Process headings and sentences with fallbacks
        $replacements = [
            ['heading', 'medium', [
                'A short and sweet title for this section.',
                'Craft a captivating title for this section to attract your audience.'
            ]],
            ['sentence', 'short', 'Use this space to write about your company, employee profiles and organizational culture; share your story and connect with customers.'],
            ['overline', 'short', ['ADD AN OVERLINE', 'Add an overline', 'Overline']],
            ['button', 'short', ['Call To Action', 'Call to Action']]
        ];

        foreach ($replacements as [$type, $length, $search]) {
            $content_value = isset($people_content[$type][$length]) ? 
                $people_content[$type][$length] : 
                (isset($base_content[$type][$length]) ? $base_content[$type][$length] : null);

            if ($content_value) {
                if (is_array($search)) {
                    foreach ($search as $s) {
                        $content = str_replace($s, $content_value, $content);
                    }
                } else {
                    $content = str_replace($search, $content_value, $content);
                }
            }
        }

        // Process people
        if (isset($people_content['people'])) {
            foreach ($people_content['people'] as $person) {
                if (isset($person['name'])) {
                    $replacement = 'Name Lastname';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $person['name'], $pos, strlen($replacement));
                    }
                }
                if (isset($person['position'])) {
                    $replacement = 'Position or title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $person['position'], $pos, strlen($replacement));
                    }
                }
                if (isset($person['sentence-short'])) {
                    $replacement = "Brief profile bio for this person will live here. Add an overview of this person's role or any key information.";
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $person['sentence-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process logo farm content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @return string
     */
    private static function process_logo_farm_content($content, $base_content) {
        // Headline medium
        if (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Tell your audience about your achievements, partners or customers.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Overline
        if (isset($base_content['overline']['short'])) {
            $replacements = ['ADD AN OVERLINE', 'Add an overline', 'Overline'];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $base_content['overline']['short'], $content);
            }
        }

        // Button
        if (isset($base_content['button']['short'])) {
            $content = str_replace('Call To Action', $base_content['button']['short'], $content);
            $content = str_replace('Call to Action', $base_content['button']['short'], $content);
        }

        return $content;
    }

    /**
     * Process location content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $columns_content The columns content.
     * @return string
     */
    private static function process_location_content($content, $base_content, $columns_content) {
        // Headline short
        if (isset($base_content['heading']['short'])) {
            $replacements = [
                'Find us',
                'Write a short headline'
            ];
            foreach ($replacements as $replacement) {
                $content = str_replace($replacement, $base_content['heading']['short'], $content);
            }
        }

        // Headline medium
        if (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Compose a captivating title for this section.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Process sentences
        $sentences = [
            'short' => [
                'Support your idea with a clear, descriptive sentence or phrase that has a consistent writing style.',
                'Use a brief and inviting sentence to encourage visitors to get in touch.'
            ],
            'medium' => 'Use this paragraph to provide more insights writing with clear and concise language that is easy to understand. Edit and proofread your content.'
        ];

        foreach ($sentences as $type => $searches) {
            if (isset($base_content['sentence'][$type])) {
                if (is_array($searches)) {
                    foreach ($searches as $search) {
                        $content = str_replace($search, $base_content['sentence'][$type], $content);
                    }
                } else {
                    $content = str_replace($searches, $base_content['sentence'][$type], $content);
                }
            }
        }

        // Process columns
        if (isset($columns_content['columns'])) {
            foreach ($columns_content['columns'] as $column) {
                if (isset($column['title-medium'])) {
                    $replacement = 'Add a descriptive title for the column.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['title-medium'], $pos, strlen($replacement));
                    }
                }
                if (isset($column['sentence-short'])) {
                    $replacement = 'Add context to your column. Help visitors understand the value they can get from your products and services.';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['sentence-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process gallery content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @return string
     */
    private static function process_gallery_content($content, $base_content) {
        // Headline short
        if (isset($base_content['heading']['short'])) {
            $content = str_replace('Add a succinct headline', $base_content['heading']['short'], $content);
        }
        if (isset($base_content['heading']['medium'])) {
            $content = str_replace('Briefly and concisely explain what you do for your audience.', $base_content['heading']['medium'], $content);
        }
         // Sentence long
         if (isset($base_content['sentence']['long'])) {
            $content = str_replace(
                'Use this paragraph section to get your website visitors to know you. Write about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style. Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.',
                $base_content['sentence']['long'],
                $content
            );
        }
         // Sentence medium
         if (isset($base_content['sentence']['medium'])) {
            $content = str_replace(
                'Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.',
                $base_content['sentence']['medium'],
                $content
            );
        }
        // Sentence short
        if (isset($base_content['sentence']['short'])) {
            $content = str_replace(
                'Write with clear, concise language to inform and engage your audience. Consider what matters to them and provide valuable insights.',
                $base_content['sentence']['short'],
                $content
            );
        }

        return $content;
    }

    /**
     * Process featured products content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_featured_products_content($content, $base_content, $context_ai, $context, $is_html) {
        $featured_content = self::find_content_by_id($context_ai, $context . '-single');

        // Headline medium
        if (isset($featured_content['heading']['medium'])) {
            $content = str_replace(
                'An engaging product or feature headline here',
                $featured_content['heading']['medium'],
                $content
            );
        } elseif (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'An engaging product or feature headline here',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Sentence medium
        if (isset($featured_content['sentence']['medium'])) {
            $content = str_replace(
                'Write a short descriptive paragraph about your product. Focus on your ideal buyer. Entice with benefits of using your product.',
                $featured_content['sentence']['medium'],
                $content
            );
        } elseif (isset($base_content['sentence']['medium'])) {
            $content = str_replace(
                'Write a short descriptive paragraph about your product. Focus on your ideal buyer. Entice with benefits of using your product.',
                $base_content['sentence']['medium'],
                $content
            );
        }

        // Price
        if (isset($featured_content['price'])) {
            $content = str_replace('$19.99', $featured_content['price'], $content);
        }

        // Button
        if (isset($featured_content['button']['short'])) {
            $content = str_replace('Call To Action', $featured_content['button']['short'], $content);
            $content = str_replace('Call to Action', $featured_content['button']['short'], $content);
        } elseif (isset($base_content['button']['short'])) {
            $content = str_replace('Call To Action', $base_content['button']['short'], $content);
            $content = str_replace('Call to Action', $base_content['button']['short'], $content);
        }

        // Process benefits
        if (isset($featured_content['product-features-and-benefits'])) {
            foreach ($featured_content['product-features-and-benefits'] as $index => $benefit) {
                if (isset($benefit['list-item-short'])) {
                    $replacement = ($index % 2 !== 0) ? 
                        'Another short feature description' : 
                        'Short feature description';
                    if (!$is_html) {
                        $temp_replacement = '"text":"' . $replacement . '"';
                        $pos = strpos($content, $temp_replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $benefit['list-item-short'] . '"', $pos, strlen($temp_replacement));
                        }
                    }
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $benefit['list-item-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process product loop content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $columns_content The columns content.
     * @return string
     */
    private static function process_product_loop_content($content, $base_content, $columns_content, $context_ai, $context) {
        $hero_content = self::find_content_by_id($context_ai, $context . '-hero');
        // Process various content types
        $replacements = [
            ['heading', 'short', 'Type a short headline'],
            ['heading', 'medium', 'Briefly and concisely explain what you do for your audience.'],
            ['sentence', 'long', 'Use this paragraph section to get your website visitors to know you. Write about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style. Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.'],
            ['sentence', 'medium', 'Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.'],
            ['sentence', 'short', [
                'Consider using this if you need to provide more context on why you do what you do.',
                'Consider using this if you need to provide more context on why you do what you do. Be engaging.'
            ]],
            ['overline', 'short', ['2018 - Current', 'Add an overline text', 'Overline']],
            ['button', 'short', ['Call To Action', 'Call to Action']]
        ];

        foreach ($replacements as [$type, $length, $search]) {
            if (isset($hero_content[$type][$length])) {
                if (is_array($search)) {
                    foreach ($search as $s) {
                        $content = str_replace($s, $hero_content[$type][$length], $content);
                    }
                } else {
                    $content = str_replace($search, $hero_content[$type][$length], $content);
                }
            }
            if (isset($base_content[$type][$length])) {
                if (is_array($search)) {
                    foreach ($search as $s) {
                        $content = str_replace($s, $base_content[$type][$length], $content);
                    }
                } else {
                    $content = str_replace($search, $base_content[$type][$length], $content);
                }
            }
        }

        // Process columns
        if (isset($columns_content['columns'])) {
            foreach ($columns_content['columns'] as $column) {
                $column_replacements = [
                    ['title-short', 'Add a short title'],
                    ['sentence-short', 'Use this space to add a short description.'],
                    ['sentence-medium', 'Use this space to add a medium length description. Be brief and give enough information to earn their attention.']
                ];

                foreach ($column_replacements as [$key, $search]) {
                    if (isset($column[$key])) {
                        $pos = strpos($content, $search);
                        if (false !== $pos) {
                            $content = substr_replace($content, $column[$key], $pos, strlen($search));
                        }
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process form content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $ai_content The AI content.
     * @return string
     */
    private static function process_form_content($content, $base_content, $ai_content) {
        $text_content = '';
        $about_content = isset($ai_content['about']['content']) ? $ai_content['about']['content'] : null;
        
        if ($about_content) {
            $text_content = self::find_content_by_id($about_content, 'about');
        }

        // Headline short
        if (isset($base_content['heading']['short'])) {
            $content = str_replace('Add A Title For Your Form', $base_content['heading']['short'], $content);
        }

        // Sentence short
        if (isset($base_content['sentence']['short'])) {
            $content = str_replace(
                'Briefly describe what the form is for or provide additional context if required. Use inviting language.',
                $base_content['sentence']['short'],
                $content
            );
        }

        // Paragraph long from about content
        if (isset($text_content['sentence']['long'])) {
            $content = str_replace(
                'Use this paragraph section to get your website visitors to know you. Write about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style. Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.',
                $text_content['sentence']['long'],
                $content
            );
        }

        return $content;
    }

    /**
     * Process table of contents content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_table_of_contents_content($content, $base_content, $context_ai, $context) {
        $toc_content = self::find_content_by_id($context_ai, $context . '-table-contents');

        // Headline medium
        if (isset($toc_content['heading']['medium'])) {
            $content = str_replace(
                'Craft a captivating title for this section to attract your audience.',
                $toc_content['heading']['medium'],
                $content
            );
        } elseif (isset($base_content['heading']['medium'])) {
            $content = str_replace(
                'Craft a captivating title for this section to attract your audience.',
                $base_content['heading']['medium'],
                $content
            );
        }

        // Process other content types with fallbacks
        $replacements = [
            ['overline', 'short', ['ADD AN OVERLINE', 'Add an overline', 'Overline']],
            ['button', 'short', ['Call To Action', 'Call to Action']]
        ];

        foreach ($replacements as [$type, $length, $search]) {
            $content_value = isset($toc_content[$type][$length]) ? 
                $toc_content[$type][$length] : 
                (isset($base_content[$type][$length]) ? $base_content[$type][$length] : null);

            if ($content_value) {
                if (is_array($search)) {
                    foreach ($search as $s) {
                        $content = str_replace($s, $content_value, $content);
                    }
                } else {
                    $content = str_replace($search, $content_value, $content);
                }
            }
        }

        // Process subtitles
        if (isset($toc_content['subtitles'])) {
            foreach ($toc_content['subtitles'] as $subtitle) {
                if (isset($subtitle['title-short'])) {
                    $replacement = 'Write a title for your section or related content here';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $subtitle['title-short'], $pos, strlen($replacement));
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Process counter or stats content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_counter_or_stats_content($content, $base_content, $context_ai, $base_testimonial_content, $context, $is_html) {
        $counter_content = self::find_content_by_id($context_ai, $context . '-counter-stats');
        // Headline.
        if ( isset($counter_content['heading']['medium']) ) {
            $content = str_replace(
                'Tell your story in numbers, and give your visitors useful insights.',
                $counter_content['heading']['medium'],
                $content
            );
        } elseif ( isset($base_content['heading']['medium']) ) {
            $content = str_replace(
                'Tell your story in numbers, and give your visitors useful insights.',
                $base_content['heading']['medium'],
                $content
            );
        }
        // Paragraph medium
        if ( isset($counter_content['sentence']['medium']) ) {
            $content = str_replace(
                'Make an impact, and share your organization\'s stats or achievements to interest your website visitors into learning more about you.',
                $counter_content['sentence']['medium'],
                $content
            );
        } elseif ( isset($base_content['sentence']['medium']) ) {
            $content = str_replace(
                'Make an impact, and share your organization\'s stats or achievements to interest your website visitors into learning more about you.',
                $base_content['sentence']['medium'],
                $content
            );
        }
        // overline
        if ( isset($counter_content['overline']['short']) ) {
            $content = str_replace( 'ADD AN OVERLINE TEXT', $counter_content['overline']['short'], $content );
            $content = str_replace( 'Add an overline text', $counter_content['overline']['short'], $content );
            $content = str_replace( 'Overline', $counter_content['overline']['short'], $content );
        } elseif ( isset($base_content['overline']['short']) ) {
            $content = str_replace( 'ADD AN OVERLINE TEXT', $base_content['overline']['short'], $content );
            $content = str_replace( 'Add an overline text', $base_content['overline']['short'], $content );
            $content = str_replace( 'Overline', $base_content['overline']['short'], $content );
        }
        // Button
        if ( isset($counter_content['button']['short']) ) {
            $content = str_replace( 'Call To Action', $counter_content['button']['short'], $content );
            $content = str_replace( 'Call to Action', $counter_content['button']['short'], $content );
        } elseif ( isset($base_content['button']['short']) ) {
            $content = str_replace( 'Call To Action', $base_content['button']['short'], $content );
            $content = str_replace( 'Call to Action', $base_content['button']['short'], $content );
        }
        // Metrics
        if ( isset($counter_content['metrics']) ) {
            foreach ($counter_content['metrics'] as $index => $metric) {
                // Title.
                if ( isset($counter_content['metrics'][$index]['title-short']) ) {
                    if ( ! $is_html ) {
                        $replacement = '"title":"Stat title"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"title":"'. $counter_content['metrics'][$index]['title-short'] .'"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = 'Stat title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $counter_content['metrics'][$index]['title-short'], $pos, strlen($replacement));
                    }
                }
                // Price.
                if ( isset($counter_content['metrics'][$index]['value-short']) ) {
                    if ( 0 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "50%", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":50', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="50', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 1 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "98%", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( "98", absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( '"end":98', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="98', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 2 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "100,110", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":100110', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="100110', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 3 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "8/mo", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":8', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="8', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 4 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "20yr", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":20', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="20', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    } else if ( 5 === $index ) {
                        if ( strlen($counter_content['metrics'][$index]['value-short']) < 8 ) {
                            $content = str_replace( "18,110", $counter_content['metrics'][$index]['value-short'], $content );
                            $content = str_replace( '"end":18110', '"end":'. absint($counter_content['metrics'][$index]['value-short']), $content );
                            $content = str_replace( 'data-end="18110', 'data-end="'. absint($counter_content['metrics'][$index]['value-short']), $content );
                        }
                    }
                }
            }
        }
        // List
        if ( isset($counter_content['list']) ) {
            foreach ($counter_content['list'] as $index => $item) {
                // list item.
                if ( isset($item['list-item-short']) ) {
                    if (!$is_html) {
                        $replacement = '"text":"Add a single and succinct list item"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $item['list-item-short'] . '"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = "Add a single and succinct list item";
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $item['list-item-short'], $pos, strlen($replacement));
                    }
                }
                // list item long.
                if ( isset($item['list-item-long']) ) {
                    if (!$is_html) {
                        $replacement = '"text":"Add unique list items while keeping a consistent phrasing style and similar line lengths"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $item['list-item-long'] . '"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = "Add unique list items while keeping a consistent phrasing style and similar line lengths";
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $item['list-item-long'], $pos, strlen($replacement));
                    }
                }
            }
        }

        // Process testimonials
        if (isset($base_testimonial_content['testimonials'])) {
            foreach ($base_testimonial_content['testimonials'] as $testimonial) {
                if (isset($testimonial['customer'])) {
                    $replacement = 'Customer Name';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $testimonial['customer'], $pos, strlen($replacement));
                    }
                }
                if (isset($testimonial['customer-name'])) {
                    $replacement = 'Customer Name';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $testimonial['customer-name'], $pos, strlen($replacement));
                    }
                }
                if (isset($testimonial['customer-occupation'])) {
                    $replacement = 'Customer Title';
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $testimonial['customer-occupation'], $pos, strlen($replacement));
                    }
                }
                if (isset($testimonial['testimonial'])) {
                    $replacements = [
                        'Testimonials are a social proof, a powerful way to inspire trust.',
                        'Testimonials, as authentic endorsements from satisfied customers, serve as potent social proof, significantly inspiring trust in potential consumers.'
                    ];
                    foreach ($replacements as $replacement) {
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, $testimonial['testimonial'], $pos, strlen($replacement));
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Process list content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_list_content($content, $base_content, $context_ai, $context, $is_html) {
        $list_content = self::find_content_by_id($context_ai, $context . '-list');
        $columns_content = self::find_content_by_id($context_ai, $context . '-columns');
        // Headline Short.
        if ( isset($base_content['heading']['short']) ) {
            $content = str_replace( 'Write a short and relevant headline', $base_content['heading']['short'], $content );
        }
        // Headline.
        if ( isset($base_content['heading']['medium']) ) {
            $content = str_replace( 'Write a clear and relevant header to keep your visitors engaged', $base_content['heading']['medium'], $content );
        }
        // Paragraph medium
        if ( isset($base_content['sentence']['medium']) ) {
            $content = str_replace( 'Use this paragraph section to get your website visitors to know you. Consider writing about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style.', $base_content['sentence']['medium'], $content );
        }
        // overline
        if ( isset($base_content['overline']['short']) ) {
            $content = str_replace( 'ADD AN OVERLINE TEXT', $base_content['overline']['short'], $content );
            $content = str_replace( 'Add an overline text', $base_content['overline']['short'], $content );
            $content = str_replace( 'Overline', $base_content['overline']['short'], $content );
        }
        // Button
        if ( isset($base_content['button']['short']) ) {
            $content = str_replace( 'Call To Action', $base_content['button']['short'], $content );
            $content = str_replace( 'Call to Action', $base_content['button']['short'], $content );
        }
        // Columns
        if ( isset($columns_content['columns']) ) {
            foreach ($columns_content['columns'] as $index => $column) {
                // Title.
                if ( isset($column['title-medium']) ) {
                    $replacement = "Give your list item a title";
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['title-medium'], $pos, strlen($replacement));
                    }
                }
                // Sentence Short.
                if ( isset($column['sentence-short']) ) {
                    $replacement = "Use this short paragraph to write a supporting description of your list item. Remember to let your readers know why this list item is essential.";
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $column['sentence-short'], $pos, strlen($replacement));
                    }
                }
            }
        }
        // List
        if ( isset($list_content['list']) ) {
            foreach ($list_content['list'] as $index => $item) {
                // list item.
                if ( isset($item['list-item-short']) ) {
                    if (!$is_html) {
                        $replacement = '"text":"Add a single and succinct list item"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $item['list-item-short'] . '"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = "Add a single and succinct list item";
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $item['list-item-short'], $pos, strlen($replacement));
                    }
                }
                // list item long.
                if ( isset($item['list-item-long']) ) {
                    if (!$is_html) {
                        $replacement = '"text":"Add unique list items while keeping a consistent phrasing style and similar line lengths"';
                        $pos = strpos($content, $replacement);
                        if (false !== $pos) {
                            $content = substr_replace($content, '"text":"' . $item['list-item-long'] . '"', $pos, strlen($replacement));
                        }
                    }
                    $replacement = "Add unique list items while keeping a consistent phrasing style and similar line lengths";
                    $pos = strpos($content, $replacement);
                    if (false !== $pos) {
                        $content = substr_replace($content, $item['list-item-long'], $pos, strlen($replacement));
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Process slider content
     *
     * @param string $content The content to process.
     * @param array  $base_content The base content.
     * @param array  $context_ai The context AI content.
     * @param string $context The context.
     * @return string
     */
    private static function process_slider_content($content, $base_content, $context_ai, $context, $is_html) {
        // Headline.
        if ( isset($base_content['heading']['short']) ) {
            $content = str_replace( 'Short Headline', $base_content['heading']['short'], $content );
        }
        // Headline.
        if ( isset($base_content['heading']['medium']) ) {
            $content = str_replace( 'Craft a captivating title to attract your audience.', $base_content['heading']['medium'], $content );
        }
        // Headline.
        if ( isset($base_content['sentence']['short']) ) {
            $content = str_replace( 'Use a clear and attention-grabbing short paragraph to engage your audience and draw them into reading the rest of your content.', $base_content['sentence']['short'], $content );
        }
        // overline
        if ( isset($base_content['overline']['short']) ) {
            $content = str_replace( 'ADD AN OVERLINE', $base_content['overline']['short'], $content );
            $content = str_replace( 'Add an overline', $base_content['overline']['short'], $content );
            $content = str_replace( 'Overline', $base_content['overline']['short'], $content );
        }
        // Button
        if ( isset($base_content['button']['short']) ) {
            $content = str_replace( 'Call To Action', $base_content['button']['short'], $content );
            $content = str_replace( 'Call to Action', $base_content['button']['short'], $content );
        }
        return $content;
    }
        
        
} 