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

    $search_criteria = array(
        'field_filters' => array(
            array(
                'key'   => EDGPS_FIELD_STATUS,
                'value' => EDGPS_STATUS_APPROVED,
            ),
        ),
    );

    $paging = array( 'offset' => 0, 'page_size' => EDGPS_PAGE_SIZE );
    $entries = GFAPI::get_entries( $form_id, $search_criteria, null, $paging );

    if ( is_wp_error( $entries ) || empty( $entries ) ) {
        return '<p>No participants found.</p>';
    }

    // Sort by name alphabetically
    usort( $entries, function( $a, $b ) {
        $a_first = isset( $a[ EDGPS_FIELD_FIRST_NAME ] ) ? strtolower( $a[ EDGPS_FIELD_FIRST_NAME ] ) : '';
        $a_last  = isset( $a[ EDGPS_FIELD_LAST_NAME ] )  ? strtolower( $a[ EDGPS_FIELD_LAST_NAME ] )  : '';
        $b_first = isset( $b[ EDGPS_FIELD_FIRST_NAME ] ) ? strtolower( $b[ EDGPS_FIELD_FIRST_NAME ] ) : '';
        $b_last  = isset( $b[ EDGPS_FIELD_LAST_NAME ] )  ? strtolower( $b[ EDGPS_FIELD_LAST_NAME ] )  : '';

        $a_full = trim( "$a_first $a_last" );
        $b_full = trim( "$b_first $b_last" );

        return strcmp( $a_full, $b_full );
    });


    ob_start();
    echo '<ul class="gaad-grid">';
    foreach ( $entries as $entry ) {
        $first_name = isset( $entry[ EDGPS_FIELD_FIRST_NAME ] ) ? $entry[ EDGPS_FIELD_FIRST_NAME ] : '';
        $last_name  = isset( $entry[ EDGPS_FIELD_LAST_NAME ] )  ? $entry[ EDGPS_FIELD_LAST_NAME ]  : '';
        $name = esc_html( trim( "$first_name $last_name" ) );
        $email        = isset( $entry[ EDGPS_FIELD_EMAIL ] ) ? sanitize_email( $entry[ EDGPS_FIELD_EMAIL ] ) : '';

        $job_title    = isset( $entry[ EDGPS_FIELD_JOB_TITLE ] ) ? esc_html( $entry[ EDGPS_FIELD_JOB_TITLE ] ) : '';
        $company      = isset( $entry[ EDGPS_FIELD_COMPANY ] )   ? esc_html( $entry[ EDGPS_FIELD_COMPANY ] )   : '';
        $job_and_company = '';
        if ( $job_title && $company ) {
            $job_and_company = $job_title . ', ' . $company;
        } elseif ( $job_title ) {
            $job_and_company = $job_title;
        } elseif ( $company ) {
            $job_and_company = $company;
        }


        $city    = isset( $entry[ EDGPS_FIELD_CITY ] )    ? $entry[ EDGPS_FIELD_CITY ]    : '';
        $state   = isset( $entry[ EDGPS_FIELD_STATE ] )   ? $entry[ EDGPS_FIELD_STATE ]   : '';
        $country = isset( $entry[ EDGPS_FIELD_COUNTRY ] ) ? $entry[ EDGPS_FIELD_COUNTRY ] : '';
        $location_parts = array_filter( array( $city, $state, $country ) );
        $location = esc_html( implode( ', ', $location_parts ) );

        $website = isset( $entry[ EDGPS_FIELD_WEBSITE ] ) ? trim( $entry[ EDGPS_FIELD_WEBSITE ] ) : '';

        if ( ! empty( $website ) ) {
            $name_link = '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener noreferrer">' . $name . '</a>';
        } else {
            $name_link = $name;
        }

        $hours        = isset( $entry[ EDGPS_FIELD_HOURS ] )        ? floatval( $entry[ EDGPS_FIELD_HOURS ] )        : 0;
        $contribution = isset( $entry[ EDGPS_FIELD_CONTRIBUTION ] ) ? esc_html( $entry[ EDGPS_FIELD_CONTRIBUTION ] ) : '';
        $image_choice = isset( $entry[ EDGPS_FIELD_IMAGE_CHOICE ] ) ? $entry[ EDGPS_FIELD_IMAGE_CHOICE ]             : '';
        $image_url    = '';
        $alt          = '';

        if ( $image_choice === 'Let me upload an image' && ! empty( $entry[ EDGPS_FIELD_IMAGE_UPLOAD ] ) ) {
            $image_url = esc_url( $entry[ EDGPS_FIELD_IMAGE_UPLOAD ] );
            $alt = isset( $entry[ EDGPS_FIELD_IMAGE_ALT ] ) ? esc_attr( $entry[ EDGPS_FIELD_IMAGE_ALT ] ) : '';
        } elseif ( $image_choice === 'Gravatar' && ! empty( $email ) ) {
            $hash         = md5( strtolower( trim( $email ) ) );
            $gravatar_url = 'https://www.gravatar.com/avatar/' . $hash . '?s=' . EDGPS_GRAVATAR_SIZE . '&d=' . EDGPS_GRAVATAR_DEFAULT;

            $transient_key   = 'edgps_gravatar_' . $hash;
            $gravatar_exists = get_transient( $transient_key );

            if ( false === $gravatar_exists ) {
                $response        = wp_remote_head( $gravatar_url );
                $gravatar_exists = ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) ? 'yes' : 'no';
                set_transient( $transient_key, $gravatar_exists, DAY_IN_SECONDS );
            }

            if ( 'yes' === $gravatar_exists ) {
                $image_url = esc_url( $gravatar_url );
                $alt       = $name;
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
