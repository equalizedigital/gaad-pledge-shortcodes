<?php
/**
 * Shortcode to output the count of "Approved" people from a Gravity Form.
 * Shortcode usage: [gaad_pledge_people_count gf_id="1"]
 */

defined( 'ABSPATH' ) || exit;

function gaad_register_people_count_shortcode() {
    add_shortcode( 'gaad_pledge_people_count', 'gaad_people_count_shortcode_handler' );
}
add_action( 'init', 'gaad_register_people_count_shortcode' );

/**
 * Shortcode handler for [gaad_pledge_people_count gf_id="1"]
 *
 * @param array $atts Shortcode attributes.
 * @return string Number of approved entries.
 */
function gaad_people_count_shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'gf_id' => '',
    ), $atts, 'gaad_pledge_people_count' );

    $form_id = absint( $atts['gf_id'] );
    if ( ! $form_id ) {
        return '';
    }

    // Check if cached count exists
    $transient_key = 'gaad_people_count_' . $form_id;
    $cached_count  = get_transient( $transient_key );

    if ( false !== $cached_count ) {
        return esc_html( $cached_count );
    }

    if ( ! class_exists( 'GFAPI' ) ) {
        return '';
    }

    $search_criteria = array(
        'field_filters' => array(
            array(
                'key'   => '31',
                'value' => 'Approved',
            ),
        ),
    );

    $entry_count = GFAPI::count_entries( $form_id, $search_criteria );

    // Cache result for 30 minutes
    set_transient( $transient_key, $entry_count, 30 * MINUTE_IN_SECONDS );

    return esc_html( $entry_count );
}