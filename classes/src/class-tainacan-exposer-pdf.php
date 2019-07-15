<?php
namespace Tainacan\PDF;

add_action('init', function( ) {

	class ExposerPDF extends \Tainacan\Exposers\Exposer {
		public $slug = 'exposer-pdf';
		public $mappers = true;
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
			
			// $mapper = \Tainacan\Mappers_Handler::get_instance()->get_mapper_from_request($request);
			// if($mapper && property_exists($mapper, 'XML_namespace') && !empty($mapper->XML_namespace)) {
			// 	foreach ($mapper->prefixes as $prefix => $schema) {
			// 		$this->contexts[$prefix] = $schema;
			// 	}
			// foreach ($response->get_data()['items'] as $item) {
			// 	foreach ($item['metadata'] as $meta_id => $meta_value) {
			// 		if ( !empty($meta_value['mapping']) ) {
			// 			foreach($meta_value['mapping'] as $map => $map_value) {
			// 				$this->contexts[$meta_value['name']] = ["@id" => $map_value]; //pode ter mais de um mapper usar o mapp passado pela URL?
			// 			}
			// 		}
			// 	}
			// }
			// } else {
			// 	foreach ($response->get_data()['items'] as $item) {
			// 		foreach ($item['metadata'] as $meta_id => $meta_value) {
			// 			$this->contexts[$meta_value['name']] = $meta_value['semantic_uri'];
			// 		}
			// 	}
			// }
			// $this->contexts['@language'] = $this->get_locale($response->get_data()['items']);

			// $contexts =  '"@context":' . \json_encode($this->contexts);
			$body = $this->array_to_html($response->get_data()['items']);
			$head = $this->get_head();
			$html = $this->get_html($head, $body);
			$response->set_data($html);
			return $response;
		}

		protected function array_to_html( $data) {
			$jsonld = '';
			$items_ul = [];
			foreach ($data as $item) {
				$li = "";
				$pattern_li = "<li> <b> %s :</b> <p> %s </p> </li>";
				foreach ($item['metadata'] as $metadata) {
					$li .= sprintf($pattern_li, $metadata["name"], $metadata["value"]);
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
				$items_ul[] = "<ul>$li</ul> <div> <h2>anexos</h2> $attachements </div>";
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
					</head>
					
					<body>
						%s
					</body>
				</html>
			", $head, $body);
		}

		private function get_head() {
			$main_css = plugins_url('../../statics/css/main.css',__FILE__ );
			return '
				<title>https://www.google.com/</title>
				<link rel="stylesheet" type="text/css" href="' . $main_css . '">
			';
		}

		private function create_head() {}

		private function create_footer() {}

	}

	$exposers = \Tainacan\Exposers_Handler::get_instance();
	$exposers->register_exposer('Tainacan\PDF\ExposerPDF');
});