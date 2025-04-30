<?php
/**
 * Shortcode to output the total pledged hours from a Gravity Form.
 * Shortcode usage: [gaad_pledge_hour_total gf_id="1"]
 */

defined( 'ABSPATH' ) || exit;

function gaad_register_hour_total_shortcode() {
    add_shortcode( 'gaad_pledge_hour_total', 'gaad_hour_total_shortcode_handler' );
}
add_action( 'init', 'gaad_register_hour_total_shortcode' );

/**
 * Shortcode handler for [gaad_pledge_hour_total gf_id="1"]
 *
 * @param array $atts Shortcode attributes.
 * @return string Total hours pledged.
 */
function gaad_hour_total_shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'gf_id' => '',
    ), $atts, 'gaad_pledge_hour_total' );

    $form_id = absint( $atts['gf_id'] );
    if ( ! $form_id ) {
        return '';
    }

    if ( ! class_exists( 'GFAPI' ) ) {
        return '';
    }

    $search_criteria = array(
        'field_filters' => array(
            array(
                'key'   => '31', // Status field
                'value' => 'Approved',
            ),
        ),
    );

    $paging = array( 'offset' => 0, 'page_size' => 1000 );
    $total_hours = 0;

    do {
        $entries = GFAPI::get_entries( $form_id, $search_criteria, null, $paging );
        if ( is_wp_error( $entries ) ) {
            return '';
        }

        foreach ( $entries as $entry ) {
            $hours = isset( $entry[8] ) ? floatval( $entry[8] ) : 0;
            $total_hours += $hours;
        }

        $paging['offset'] += $paging['page_size'];
    } while ( count( $entries ) === $paging['page_size'] );

    return esc_html( $total_hours );
}
