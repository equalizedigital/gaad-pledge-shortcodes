<?php
/**
 * Shortcode to display an accessible, responsive grid of participants.
 * Usage: [gaad_pledge_participant_grid gf_id="1"]
 */

defined( 'ABSPATH' ) || exit;

function gaad_register_participant_grid_shortcode() {
    add_shortcode( 'gaad_pledge_participant_grid', 'gaad_participant_grid_shortcode_handler' );
}
add_action( 'init', 'gaad_register_participant_grid_shortcode' );

/**
 * Fetch Gravatar profile data including alt text, if available.
 *
 * @param string $email
 * @return string Alt text or empty string.
 */
function gaad_get_gravatar_alt_text( $email ) {
    $hash = md5( strtolower( trim( $email ) ) );
    $url = 'https://www.gravatar.com/' . $hash . '.json';

    $response = wp_remote_get( $url );

    if ( is_wp_error( $response ) ) {
        return '';
    }

    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) {
        return '';
    }

    $data = json_decode( $body, true );
    if ( isset( $data['entry'][0]['avatar_alt_text'] ) ) {
        return sanitize_text_field( $data['entry'][0]['avatar_alt_text'] );
    }

    return '';
}

/**
 * Shortcode handler for [gaad_pledge_participant_grid gf_id="1"]
 */
function gaad_participant_grid_shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'gf_id' => '',
    ), $atts, 'gaad_pledge_participant_grid' );

    $form_id = absint( $atts['gf_id'] );
    if ( ! $form_id || ! class_exists( 'GFAPI' ) ) {
        return '';
    }

    wp_enqueue_style( 'gaad-participant-grid', plugin_dir_url( __FILE__ ) . '../assets/participant-grid.css', array(), '1.0' );

    $search_criteria = array(
        'field_filters' => array(
            array(
                'key'   => '31',
                'value' => 'Approved',
            ),
        ),
    );

    $paging = array( 'offset' => 0, 'page_size' => 1000 );
    $entries = GFAPI::get_entries( $form_id, $search_criteria, null, $paging );

    if ( is_wp_error( $entries ) || empty( $entries ) ) {
        return '<p>No participants found.</p>';
    }

    // Sort by name alphabetically
    usort( $entries, function( $a, $b ) {
        $a_first = isset( $a['1.3'] ) ? strtolower( $a['1.3'] ) : '';
        $a_last  = isset( $a['1.6'] ) ? strtolower( $a['1.6'] ) : '';
        $b_first = isset( $b['1.3'] ) ? strtolower( $b['1.3'] ) : '';
        $b_last  = isset( $b['1.6'] ) ? strtolower( $b['1.6'] ) : '';

        $a_full = trim( "$a_first $a_last" );
        $b_full = trim( "$b_first $b_last" );

        return strcmp( $a_full, $b_full );
    });


    ob_start();
    echo '<ul class="gaad-grid">';
    foreach ( $entries as $entry ) {
        $first_name = isset( $entry['1.3'] ) ? $entry['1.3'] : '';
        $last_name  = isset( $entry['1.6'] ) ? $entry['1.6'] : '';
        $name = esc_html( trim( "$first_name $last_name" ) );
        $email        = isset( $entry[3] ) ? sanitize_email( $entry[3] ) : '';
        $job_title    = isset( $entry[4] ) ? esc_html( $entry[4] ) : '';
        $company      = isset( $entry[7] ) ? esc_html( $entry[7] ) : '';
        $city    = isset( $entry['6.3'] ) ? $entry['6.3'] : '';
        $state   = isset( $entry['6.4'] ) ? $entry['6.4'] : '';
        $country = isset( $entry['6.6'] ) ? $entry['6.6'] : '';

        $location_parts = array_filter( array( $city, $state, $country ) );
        $location = esc_html( implode( ', ', $location_parts ) );
        $hours        = isset( $entry[8] ) ? absint( $entry[8] ) : 0;
        $contribution = isset( $entry[11] ) ? esc_html( $entry[11] ) : '';
        $image_choice = isset( $entry[33] ) ? $entry[33] : '';
        $image_url    = '';
        $alt          = '';

        if ( $image_choice === 'Let me upload an image' && ! empty( $entry[30] ) ) {
            $image_url = esc_url( $entry[30] );
            $alt = esc_attr( $entry[32] );
        } elseif ( $image_choice === 'Gravatar' && ! empty( $email ) ) {
            $hash = md5( strtolower( trim( $email ) ) );
            $gravatar_url = 'https://www.gravatar.com/avatar/' . $hash . '?s=200&d=404';

            $headers = @get_headers( $gravatar_url );
            if ( is_array( $headers ) && strpos( $headers[0], '200' ) !== false ) {
                $image_url = esc_url( $gravatar_url );
                $alt = gaad_get_gravatar_alt_text( $email );
            }
        }

        // Fallback image if none found
        if ( empty( $image_url ) ) {
            $image_url = plugin_dir_url( __FILE__ ) . '../assets/emblem.svg';
            $alt = '';
        }

        echo '<li class="gaad-grid__item">';
        echo '<div class="gaad-grid__image-wrap">';
        echo '<img class="gaad-grid__image" src="' . $image_url . '" alt="' . esc_attr( $alt ) . '" />';
        echo '</div>';
        echo '<div class="gaad-grid__text">';
        echo '<h3 class="gaad-grid__name">' . $name . '</h3>';
        echo '<p class="gaad-grid__meta">' . $job_title . ( $company ? ', ' . $company : '' ) . '</p>';
        echo '<p class="gaad-grid__location">' . $location . '</p>';
        echo '<p class="gaad-grid__hours"><strong>' . $hours . ' hour' . ( $hours === 1 ? '' : 's' ) . ' pledged</strong></p>';
        echo '<p class="gaad-grid__contribution">' . $contribution . '</p>';
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';

    return ob_get_clean();
}
