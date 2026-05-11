=== GAAD Pledge Shortcodes ===
Contributors: equalizedigital, alh0319
Tags: gravity forms, shortcodes
Requires at least: 6.7.0
Tested up to: 6.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds shortcodes to our website so we can display GAAD pledges on our landing page. Requires Gravity Forms and specific forms to function. Not suitable for distribution.

== Description ==

GAAD Pledge Shortcodes is a simple plugin that allows us to list Gravity Forms entries on the front end of the website. 

There are no settings. Simply activate the plugin and start using the shortcodes. 

This plugin does expect specific fields to exist in a specific form. Field IDs are defined as constants in the main plugin file, so it's not really suitable for use outside of the Equalize Digital Website. This repo has been made public for educational purposes, to show others how we created shortcodes to display data from Gravity Forms entries. There is an example form JSON file in the plugin root for reference.


== Installation ==

This section isn't really necessary because the plugin isn't intended to be distributed, but for posterity's sake:

1. Download the plugin from Equalize Digital's GitHub account.
2. Upload the zip file to your site.
3. (Optional) Import the example form included in the plugin root using Gravity Forms import functionality. The file name is `gravityforms-gaad-pledge-example-export.json` 

==  Included Shortcodes ==
1. `[gaad_pledge_people_count]`: outputs the number of approved entries
2. `[gaad_pledge_hour_total]`: outputs the total number of hours pledged from approved entries
3. `[gaad_pledge_participant_grid]`: outputs grid of participants from approved entries

Each shortcode accepts an attribute for the Gravity Form form ID number to make is easier when testing on a local install where the ID of the form does not match the ID of the form on production. 

Example shortcode with attribute: `[gaad_pledge_people_count gf_id="1"]`

== Changelog ==

= 1.1.0 =
* Replaced hardcoded Gravity Forms field IDs with named constants defined in the main plugin file.
* Improved Gravatar handling: existence checks are now cached in a single transient per form and batched before rendering, removing HTTP calls from the render loop.
* Fixed float pluralization bug where 1.0 hours displayed as "1 hours".
* Fixed double-encoding of participant names used in image alt attributes.

= 1.0.0 =
* This is version 1 - all new and shiny.