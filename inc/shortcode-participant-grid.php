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
        $job_and_company = '';
        if ( $job_title && $company ) {
            $job_and_company = $job_title . ', ' . $company;
        } elseif ( $job_title ) {
            $job_and_company = $job_title;
        } elseif ( $company ) {
            $job_and_company = $company;
        }


        $city    = isset( $entry['6.3'] ) ? $entry['6.3'] : '';
        $state   = isset( $entry['6.4'] ) ? $entry['6.4'] : '';
        $country = isset( $entry['6.6'] ) ? $entry['6.6'] : '';
        $location_parts = array_filter( array( $city, $state, $country ) );
        $location = esc_html( implode( ', ', $location_parts ) );

        $website = isset( $entry[35] ) ? trim( $entry[35] ) : '';

        if ( ! empty( $website ) ) {
            $name_link = '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener noreferrer">' . $name . '</a>';
        } else {
            $name_link = $name;
        }

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
            $gravatar_url = 'https://www.gravatar.com/avatar/' . $hash . '?s=380&d=404';

            $headers = @get_headers( $gravatar_url );
            if ( is_array( $headers ) && strpos( $headers[0], '200' ) !== false ) {
                $image_url = esc_url( $gravatar_url );
                $alt = $name;
            }
        }

        // Fallback image if none found
        if ( empty( $image_url ) ) {
            $image_url = plugin_dir_url( __FILE__ ) . '../assets/emblem.svg';
            $alt = '';
        }
        $is_fallback = strpos( $image_url, 'emblem.svg' ) !== false;
        $image_class = 'gaad-grid__image' . ( $is_fallback ? ' is-fallback' : '' );


        echo '<li class="gaad-grid__item">';
        echo '<div class="gaad-grid__image-wrap">';
        echo '<img class="' . esc_attr( $image_class ) . '" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $alt ) . '" />';
        echo '</div>';

        echo '<div class="gaad-grid__text"><div class="gaad-grid__name-link">';

        echo '<h3 class="gaad-grid__name">' . $name . '</h3>';

        if ( ! empty( $website ) ) {
            $aria_label = 'Website for ' . trim( "$first_name $last_name" );
            echo ' <a href="' . esc_url( $website ) . '" rel="noopener noreferrer" aria-label="' . esc_attr( $aria_label ) . '" class="gaad-grid__link">';
            echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline-icon" aria-hidden="true">';
            echo '<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />';
            echo '</svg>';
            echo '</a>';
        }

        echo '</div>';


        echo '<p class="gaad-grid__meta">';

        if ( $job_and_company ) {
            echo '<span class="gaad-company-info">' . esc_html( $job_and_company ) . '</span>';
        }

        echo esc_html( $location ) . '</p>';

        echo '<p class="gaad-grid__hours"><strong>' . $hours . ' hour' . ( $hours === 1 ? '' : 's' ) . ' pledged:</strong><br>';
        echo '<span class="gaad-grid__contribution">' . $contribution . '</span></p>';
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';

    return ob_get_clean();
}
