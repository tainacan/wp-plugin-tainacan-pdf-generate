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

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'classes/src/class-tainacan-exposer-pdf.php';

add_action('wp_enqueue_scripts', 'get_static_files');
function get_static_files() {
	$main_css = plugins_url('statics/css/main.css',__FILE__ );
	wp_register_style( 'tainacan_pdf_main', $main_css );
	wp_enqueue_style( 'tainacan_pdf_main' );
}

add_action("admin_menu", "add_theme_menu_item");
add_action("admin_init", "display_theme_panel_fields");

function add_theme_menu_item() {
	add_menu_page("PDF Configuração", "PDF Configuração", "manage_options", "tainacan-pdf-generte", "tainacan_pdf_generate_settings_page", null, 99);
}

function tainacan_pdf_generate_settings_page() {
	?>
	<div class="wrap">
		<h1>Tainacan PDF Configuração</h1>
		<form method="post" action="options.php">
			<?php
				settings_fields("section");
				do_settings_sections("tainacan-pdf-generate-options");
				submit_button();
			?>
		</form>
	</div>
	<?php
}

function display_theme_panel_fields() {
	add_settings_section("section", "Cabeçalho do PDF", null, "tainacan-pdf-generate-options");
	
	add_settings_field("tainacan_pdf_nome_instituicao", "Nome Instituição", "display_pdf_instituicao", "tainacan-pdf-generate-options", "section");
	add_settings_field("tainacan_pdf_logo_url", "URL imagem logo", "display_pdf_logo_url", "tainacan-pdf-generate-options", "section");
	add_settings_field("tainacan_pdf_use_html", "gerar em", "display_gerar_html", "tainacan-pdf-generate-options", "section");

	register_setting("section", "tainacan_pdf_nome_instituicao");
	register_setting("section", "tainacan_pdf_logo_url");
	register_setting("section", "tainacan_pdf_use_html");
}

function display_pdf_instituicao() {
	?> <input type="text" name="tainacan_pdf_nome_instituicao" id="tainacan_pdf_nome_instituicao" value="<?php echo get_option('tainacan_pdf_nome_instituicao'); ?>" /> <?php
}

function display_pdf_logo_url() {
	?> <input type="text" name="tainacan_pdf_logo_url" id="tainacan_pdf_logo_url" value="<?php echo get_option('tainacan_pdf_logo_url'); ?>" /> <?php
}

function display_gerar_html() {
	?>
		<label for="_html">HTML</label>
		<input type="radio" name="tainacan_pdf_use_html" id="_html" value="html" <?php  echo get_option('tainacan_pdf_use_html') == 'html' ? 'checked' : '' ?> > <br>
		<label for="_pdf">PDF</label>
		<input type="radio" name="tainacan_pdf_use_html" id="_pdf" value="pdf" <?php  echo get_option('tainacan_pdf_use_html') == 'pdf' ? 'checked' : '' ?> >
	<?php
}


