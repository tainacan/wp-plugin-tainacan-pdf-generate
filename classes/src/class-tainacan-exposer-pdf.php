<?php
namespace Tainacan\PDF;

add_action('init', function( ) {

	if ( ! defined ( 'TAINACAN_PDF_HTML' ) ) {
		define ( 'TAINACAN_PDF_HTML', false );
	}
	class ExposerPDF extends \Tainacan\Exposers\Exposer {
		public $slug = 'exposer-pdf';
		public $mappers = false;
		public $accept_no_mapper = true;

		function __construct() {
			$this->set_name( __('PDF') );
			$this->set_description( __('Exposer items as PDF', 'pdf-exposer') );
		}

		protected $contexts = [];

		/**
		 * 
		 * {@inheritDoc}
		 * @see \Tainacan\Exposers\Types\Type::rest_request_after_callbacks()
		 */
		public function rest_request_after_callbacks( $response, $handler, $request ) {
			$response->set_headers([
				'Content-Type: text/html; charset=' . get_option( 'blog_charset' )
			]);
			
			$body = $this->array_to_html($response->get_data()['items']);
			$head = $this->get_head();
			$html = $this->get_html($head, $body);
			
			if (TAINACAN_PDF_HTML) {
				$response->set_data($html);
				return $response;
			} else  {
				$mpdf = new \Mpdf\Mpdf(['tempDir' => wp_upload_dir()['basedir']]);
				$mpdf->WriteHTML($html);
				$mpdf->Output();
			}
		}

		protected function array_to_html( $data) {
			$jsonld = '';
			$items_ul = [];
			foreach ($data as $item) {
				$li = "";
				$pattern_li = "<li><p><strong> %s :</strong> %s </p> </li>";
				foreach ($item['metadata'] as $metadata) {
					if( !is_array($metadata["value"]) )
						$li .= sprintf($pattern_li, $metadata["name"], $metadata["value"]);
					else 
						$li .= sprintf($pattern_li, $metadata["name"], \implode(" | ", $metadata["value"]) );
				}
				$attachment = array_values(
					get_children(
						array(
							'post_parent' => $item['id'],
							'post_type' => 'attachment',
							'post_mime_type' => 'image',
							'order' => 'ASC',
							'numberposts'  => -1,
						)
					)
				);
				$attachements = "";
				if ( ! empty( $attachment ) ) {
					foreach ( $attachment as $attachment ) {
						$attachements .= wp_get_attachment_image( $attachment->ID, 'tainacan-interface-item-attachments' );
					}
				}
				$item_title = $item['title'];
				//$item_description =  empty($item['description']) ? "" : "<span>" . $item['description'] . "</span>";
				$item_thumbnail = get_the_post_thumbnail($item['id'], 'tainacan-medium-full');
				$items_ul[] = "
					<div class='lista-galeria'>
						<h3>$item_title</h3>
						$item_thumbnail
						<ul class='lista-colecao'>
							$li
						</ul>
						<div class='lista-galeria__images'>
							<div class='wrapper-images'>
								$attachements
							</div>
						</div>
					</div>";
			}
			return \implode(" ", $items_ul);
		}
	
		public function get_locale($obj) {
				if(array_key_exists('ID', $obj) && function_exists('wpml_get_language_information')) {
						$lang_envs = wpml_get_language_information($obj['ID']);
						return $lang_envs['locale'];
				}
				return get_locale();
		}

		private function get_html($head, $body) {
			return sprintf("
			<!doctype html>
				<html>
					<head>
						%s
						<link href='https://fonts.googleapis.com/css?family=Roboto&display=swap' rel='stylesheet'>
					</head>
					
					<body>
						<h1 class='lista-titulo'>PDF Tainacan</h1>
						%s
					</body>
				</html>
			", $head, $body);
		}

		private function get_head() {
			$main_css = plugins_url('../../statics/css/main.css',__FILE__ );
			return '
				<title>PDF Tainacan</title>
				<link rel="stylesheet" type="text/css" href="' . $main_css . '">
			';
		}

		private function create_head() {}

		private function create_footer() {}

	}

	$exposers = \Tainacan\Exposers_Handler::get_instance();
	$exposers->register_exposer('Tainacan\PDF\ExposerPDF');
});