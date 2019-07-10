<?php
/*
Plugin Name: PDF generate
Plugin URI: tainacan.org
Description: Plugin for exporser tainacan collections as PDF
Author: Media Lab / UFG
Version: 0.0.1
Text Domain: tainacan-pdf-exposer
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

//require_once plugin_dir_path(__FILE__) . 'classes/custom-form-rdf.php';
require_once plugin_dir_path(__FILE__) . 'classes/src/class-tainacan-exposer-pdf.php';

add_action('wp_enqueue_scripts', 'get_static_files');
function get_static_files() {
		 $main_css = plugins_url('statics/css/main.css',__FILE__ );
    wp_register_style( 'tainacan_pdf_main', $main_css );
    wp_enqueue_style( 'tainacan_pdf_main' );
}