<?php
/*
Plugin Name: Tainacan PDF Exposer
Plugin URI: tainacan.org
Description: Plugin for exporser tainacan collections as PDF
Author: Media Lab / UFG
Version: 0.0.1
Text Domain: tainacan-pdf-exposer
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
namespace TainacanPDFExposer;

class Plugin {
	
	public function __construct() {
		add_action("admin_menu", [$this, "add_theme_menu_item"], 20);
		add_action("admin_init", [$this, "display_theme_panel_fields"]);
		add_action("init", [$this, "init"]);
		
		// TODO: include css only when for the pages where it is used
		add_action('wp_enqueue_scripts', [$this, 'get_static_files']);
		
		// TODO: This should be the right way. Waiting https://github.com/tainacan/tainacan/issues/293
		//add_action("tainacan-register-exposer", [$this, "register_exposer"]);
	}
	
	function init() {
		require_once( plugin_dir_path(__FILE__) . 'tainacan-pdf-exposer-class.php' );
		$exposers = \Tainacan\Exposers_Handler::get_instance();
		$exposers->register_exposer('TainacanPDFExposer\Exposer');
	}
	
	function get_static_files() {
		$main_css = plugins_url('statics/css/main.css',__FILE__ );
		wp_register_style( 'tainacan_pdf_main', $main_css );
		wp_enqueue_style( 'tainacan_pdf_main' );
	}
	
	function register_exposer($exposers) {
		require_once( plugin_dir_path(__FILE__) . 'tainacan-pdf-exposer-class.php' );
		$exposers->register_exposer('TainacanPDFExposer\Exposer');
	}
	
	function add_theme_menu_item() {
		add_submenu_page('tainacan_admin', "Expositor PDF", "Expositor PDF", 'manage_options', 'tainacan-pdf-exposer', [$this, "settings_page"]);
	}
	
	
	function settings_page() {
		?>
		<div class="wrap">
			<h1>Tainacan PDF</h1>
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
		add_settings_section("section", "Opções do expositor PDF", null, "tainacan-pdf-generate-options");
		
		add_settings_field("tainacan_pdf_nome_instituicao", "Nome Instituição", [$this, "display_pdf_institution"], "tainacan-pdf-generate-options", "section");
		add_settings_field("tainacan_one_item_per_page", "Um item por página", [$this, "display_one_item_per_page"], "tainacan-pdf-generate-options", "section");
		add_settings_field("tainacan_pdf_show_attachements", "Mostrar anexos", [$this, "display_show_attachements"], "tainacan-pdf-generate-options", "section");
		add_settings_field("tainacan_pdf_cover_page", "Gerar página de capa",[$this,  "display_cover_page"], "tainacan-pdf-generate-options", "section");
		add_settings_field("tainacan_pdf_logo_url", "URL imagem logo", [$this, "display_pdf_logo_url"], "tainacan-pdf-generate-options", "section");
		add_settings_field("tainacan_pdf_use_html", "Gerar em", [$this, "display_generate_html"], "tainacan-pdf-generate-options", "section");
		
		register_setting("section", "tainacan_pdf_show_attachements");
		register_setting("section", "tainacan_pdf_nome_instituicao");
		register_setting("section", "tainacan_one_item_per_page");
		register_setting("section", "tainacan_pdf_cover_page");
		register_setting("section", "tainacan_pdf_logo_url");
		register_setting("section", "tainacan_pdf_use_html");
		
	}

	function display_pdf_institution() {
		?> <input type="text" name="tainacan_pdf_nome_instituicao" id="tainacan_pdf_nome_instituicao" value="<?php echo get_option('tainacan_pdf_nome_instituicao'); ?>" /> <?php
	}

	function display_pdf_logo_url() {
		?> <input type="text" name="tainacan_pdf_logo_url" id="tainacan_pdf_logo_url" value="<?php echo get_option('tainacan_pdf_logo_url'); ?>" /> <?php
	}

	function display_generate_html() {
		?>
			<label for="_html">HTML</label>
			<input type="radio" name="tainacan_pdf_use_html" id="_html" value="html" <?php  echo get_option('tainacan_pdf_use_html') == 'html' ? 'checked' : '' ?> > <br>
			<label for="_pdf">PDF</label>
			<input type="radio" name="tainacan_pdf_use_html" id="_pdf" value="pdf" <?php  echo get_option('tainacan_pdf_use_html') == 'pdf' ? 'checked' : '' ?> >
		<?php
	}

	function display_cover_page() {
		?><select name="tainacan_pdf_cover_page">
			<option value="sim" <?php  echo get_option('tainacan_pdf_cover_page') == 'sim' ? 'selected' : '' ?>>Sim</option>
			<option value="nao" <?php  echo get_option('tainacan_pdf_cover_page') == 'nao' ? 'selected' : '' ?>>Não</option>
		</select><?php
	}

	function display_one_item_per_page() {
		?><select name="tainacan_one_item_per_page">
			<option value="sim" <?php  echo get_option('tainacan_one_item_per_page') == 'sim' ? 'selected' : '' ?>>Sim</option>
			<option value="nao" <?php  echo get_option('tainacan_one_item_per_page') == 'nao' ? 'selected' : '' ?>>Não</option>
		</select><?php
	}

	function display_show_attachements() {
		?><select name="tainacan_pdf_show_attachements">
			<option value="sim" <?php  echo get_option('tainacan_pdf_show_attachements') == 'sim' ? 'selected' : '' ?>>Sim</option>
			<option value="nao" <?php  echo get_option('tainacan_pdf_show_attachements') == 'nao' ? 'selected' : '' ?>>Não</option>
		</select><?php
	}
	
}

$TainacanPDFExposer = new \TainacanPDFExposer\Plugin();
