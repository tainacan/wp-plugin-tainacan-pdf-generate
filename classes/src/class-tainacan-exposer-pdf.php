<?php
namespace Tainacan\PDF;

add_action('init', function( ) {

	if ( ! defined ( 'TAINACAN_PDF_HTML' ) ) {
		define ( 'TAINACAN_PDF_HTML', get_option('tainacan_pdf_use_html') == 'html' );
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
				$mpdf->defaultheaderline = 0;
				$mpdf->defaultfooterline = 0;
				$logoTainacan = plugins_url('../../statics/img/lgo/tainacan.svg',__FILE__ );
				$mpdf->SetHeader("<div class='borda'></div>");
				$mpdf->SetFooter("<table class='rodape'><tr><td class='logo'><img class='tainacan-logo' src='$logoTainacan' alt='Tainacan' /></td><td class='paginacao col-center'>{PAGENO}/{nbpg}</td><td class='data col-right'>04/10/1990</td></tr></table>");
				$mpdf->shrink_tables_to_fit = 1;
				$mpdf->WriteHTML($html);
				$mpdf->Output();
			}
		}

		protected function array_to_html( $data) {
			$jsonld = '';
			$items_list = [];
			foreach ($data as $item) {
				$temp = "";
				$pattern_li = "<li><strong> %s:</strong><p> %s </p></li>";
				$lis = "";
				foreach ($item['metadata'] as $metadata) {
					$lis .= sprintf($pattern_li, $metadata["name"], empty($metadata["value_as_string"]) ? '<span>Valor não informado</span>' : $metadata["value_as_string"]);
				}
				$attachment_list = array_values(
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
				$temp = "";
				if ( ! empty( $attachment_list ) ) {
					$count = 1;
					foreach ( $attachment_list as $attachment ) {
						$temp .= '<td><span class="lista-galeria__image"><span>Anexo ' . $count .  '</span><br>' . wp_get_attachment_image( $attachment->ID, 'tainacan-interface-item-attachments' ) . '</span></td>';

						if( $count % 3 == 0)  {
							$attachements .= "<tr class='lista-galeria__row'>$temp</tr>";
							$temp = "";
						} elseif ( count($attachment_list) == $count ) {
							$attachements .= "<tr class='lista-galeria__row'>$temp</tr>";
							$temp = "";
						}
						$count++;
					}
				}
				$item_title = $item['title'];
				$collection_name = \Tainacan\Repositories\Collections::get_instance()->fetch($item['collection_id'], 'OBJECT')->get_name();

				$item_thumbnail = get_the_post_thumbnail($item['id'], 'tainacan-medium-full');
				$logo = get_option('tainacan_pdf_logo_url');
				if (empty($logo)) {
					$logo = plugins_url('../../statics/img/lgo/thumbnail_placeholder.jpg',__FILE__ );
				}
				$name = get_option('tainacan_pdf_nome_instituicao');
				$items_list[] = "
					<tocpagebreak>
						<table class='topo'>
							<tr>
								<td><h2 class='lista-galeria__instituicao'>$name</h2></td>
								<td rowspan='2' class='logo-instituicao col-right'>
									<img class='museu-logo' src='$logo' alt='Museu' />
								</td>
							</tr>
							<tr>
								<td><span class='lista-galeria__colecao'>Coleção: $collection_name</span></td>
							</tr>
						</table>
						<div class='lista-galeria'>
							<h3 class='lista-galeria__title'>$item_title</h3>

							<div class='lista-galeria__thumb'>
								$item_thumbnail
							</div>

							<ul class='lista-galeria__dados'>
								$lis
							</ul>

							<div class='lista-galeria__images'>
								<table class='lista-galeria__table'>
									$attachements
								</table>
							</div>
						</div>
					</tocpagebreak>
					";
			}
			return \implode(" ", $items_list);
		}
	
		public function get_locale($obj) {
				if(array_key_exists('ID', $obj) && function_exists('wpml_get_language_information')) {
						$lang_envs = wpml_get_language_information($obj['ID']);
						return $lang_envs['locale'];
				}
				return get_locale();
		}

		private function get_html($head, $body) {
			$logo = get_option('tainacan_pdf_logo_url');
			if (empty($logo)) {
				$logo = plugins_url('../../statics/img/lgo/thumbnail_placeholder.jpg',__FILE__ );
			}
			$name = get_option('tainacan_pdf_nome_instituicao');
			return sprintf("
			<!doctype html>
				<html>
					<head>
						%s
						<link href='https://fonts.googleapis.com/css?family=Roboto&display=swap' rel='stylesheet'>
					</head>
					
					<body>
						<div class='box-principal'>
							<img class='box-principal__logo' src='$logo' alt='Museu' />
							<h1 class='box-principal__instituicao'>$name</h1>

							<p>Este é um documento PDF gerado automaticamente.</p>
						</div>
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