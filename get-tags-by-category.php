<?php
/*
Plugin Name: Get Tags by Category
Version: 1.1.1
Description: Shortcode to provide a list of the tags used on posts in the specified category
Author: AndrewRMinion Design
Author URI: https://andrewrminion.com/
Plugin URI: http://code.andrewrminion.com/get-tags-by-category/
Text Domain: get-tags-by-category
Domain Path: /languages
GitHub Plugin URI: https://github.com/macbookandrew/get-tags-by-category
GitHub Branch: master
*/

/**
 * Prevent direct access to this file
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Set up shortcode
 * @param array $atts WP-supplied array of attributes as follows:
 *                    string $category Category name
 *                    string $class    Extra classes to add to the containing element
 */
function gtbc_query( $atts ) {
    // Attributes
    extract( shortcode_atts(
        array(
            'category'  => '',
            'class'     => ''
        ), $atts )
    );

    // Get the list of tags
    $tags = get_category_tags( $category );

    // Output the list
    $shortcode_content = '<div class="tags-by-category';
    if ( $class ) {
        $shortcode_content .= ' ' . $class;
    }
    $shortcode_content .= '">';
    foreach ( $tags as $tag ) {
        $shortcode_content .= '<a title="' . $tag->post_count . ' ';
        if ( $tag->post_count == 1 ) {
            $shortcode_content .= 'topic';
        } else {
            $shortcode_content .= 'topics';
        }
        $shortcode_content .= '" href="' . $tag->tag_link . '">' . $tag->tag_name . '</a>';
    }
    $shortcode_content .= '</div>';

    return $shortcode_content;
}
add_shortcode( 'tags_by_category', 'gtbc_query' );

/**
 * Query database for tags in the specified category/categories
 * Adapted from https://wordpress.org/support/topic/get-tags-specific-to-category#post-1238530
 *
 * @param  array $args Array with comma-separated category names
 * @return array Array of tag objects
 */
function get_category_tags( $category_name ) {
    global $wpdb;
    $table_prefix = $wpdb->prefix;

    // Query the database
    $tags = $wpdb->get_results
    ("
    SELECT DISTINCT terms2.term_id as tag_id, terms2.name as tag_name, null as tag_link, null as post_count
    FROM
        " . $table_prefix . "posts as p1
        LEFT JOIN " . $table_prefix . "term_relationships as r1 ON p1.ID = r1.object_ID
        LEFT JOIN " . $table_prefix . "term_taxonomy as t1 ON r1.term_taxonomy_id = t1.term_taxonomy_id
        LEFT JOIN " . $table_prefix . "terms as terms1 ON t1.term_id = terms1.term_id,

        " . $table_prefix . "posts as p2
        LEFT JOIN " . $table_prefix . "term_relationships as r2 ON p2.ID = r2.object_ID
        LEFT JOIN " . $table_prefix . "term_taxonomy as t2 ON r2.term_taxonomy_id = t2.term_taxonomy_id
        LEFT JOIN " . $table_prefix . "terms as terms2 ON t2.term_id = terms2.term_id
    WHERE
        t1.taxonomy = 'category' AND p1.post_status = 'publish' AND terms1.name LIKE '$category_name' AND
        t2.taxonomy = 'post_tag' AND p2.post_status = 'publish' AND
        p1.ID = p2.ID
    ORDER BY tag_name
    ");

    // loop over tags, setting the tag_link and post_count
    $count = 0;
    foreach ( $tags as $tag ) {
        $tags[$count]->tag_link = get_tag_link($tag->tag_id);

        // loop over tags, getting number of posts per tag
        $all_tags = get_tags( array( 'search' => $tags[$count]->tag_name ) );
        foreach ( $all_tags as $this_tag ) {
            if ( $tags[$count]->tag_name == $this_tag->name ) {
                $tags[$count]->post_count = $this_tag->count;
            }
        }

        // increment the array index counter
        $count++;
    }
    return $tags;
}
