<?php
/**
* Plugin Name: GAAD Pledge Shortcodes
* Description: Adds shortcodes to our website so we can display GAAD pledges on our landing page. Requires Gravity Forms and specific forms to function. 
* Version:     1.0
* Author:      Equalize Digital
* Author URI:  https://equalizedigital.com
* 
* License:     GPL-2.0+
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*
* This program is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License version 2, as published by the
* Free Software Foundation.  You may NOT assume that you can use any other
* version of the GPL.
* 
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE.
*/

// Plugin directory
define( 'EDGPS_DIR' , plugin_dir_path( __FILE__ ) );

require_once( EDGPS_DIR . '/inc/shortcode-people-count.php' );
require_once( EDGPS_DIR . '/inc/shortcode-hour-total.php' );
require_once( EDGPS_DIR . '/inc/shortcode-participant-grid.php' );

function gaad_pledge_shortcodes_enqueue_styles() {
    wp_enqueue_style(
        'styles', // Handle name
        plugin_dir_url( __FILE__ ) . 'assets/styles.css', // Path to the CSS file
        array(), // Dependencies (if any)
        '1.0', // Version
        'all'  // Media type
    );
}
add_action( 'wp_enqueue_scripts', 'gaad_pledge_shortcodes_enqueue_styles' );
