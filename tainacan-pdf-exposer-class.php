<?php
namespace TainacanPDFExposer;

class Exposer extends \Tainacan\Exposers\Exposer {
	public $slug = 'exposer-pdf';
	public $mappers = false;
	public $accept_no_mapper = true;
	public $one_item_per_page = false;
	public $expose_html = false;

	function __construct() {
		$this->set_name( __('PDF') );
		$this->set_description( __('Exposer items as PDF', 'pdf-exposer') );
		
		$this->expose_html = get_option('tainacan_pdf_use_html') == 'html';
		
		if (get_option('tainacan_one_item_per_page') == 'sim')
			$this->one_item_per_page = true;
		else
			$this->one_item_per_page = false;
	}

	protected $contexts = [];

	/**
	 * 
	 * {@inheritDoc}
	 * @see \Tainacan\Exposers\Types\Type::rest_request_after_callbacks()
	 */
	public function rest_request_after_callbacks( $response, $handler, $request ) {
		
		require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

		$body = $this->array_to_html($response->get_data()['items']);
		$head = $this->get_head();
		$html = $this->get_html($head, $body);
		
		if ($this->expose_html) {
			$response->set_headers([
				'Content-Type: text/html; charset=' . get_option( 'blog_charset' )
			]);

			$response->set_data($html);
			return $response;
		} else  {
			$response->set_headers([
				'Content-Type: application/pdf; charset=' . get_option( 'blog_charset' )
			]);
			$mpdf = new \Mpdf\Mpdf(['tempDir' => wp_upload_dir()['basedir'], 'debug' => true]);
			$mpdf->defaultheaderline = 0;
			$mpdf->defaultfooterline = 0;
			if ($this->one_item_per_page) $mpdf->SetHeader("<div class='borda'></div>");
			$mpdf->SetFooter($this->create_footer());
			$mpdf->shrink_tables_to_fit = 1;
			$mpdf->WriteHTML($html);
			
			$response->set_data($mpdf->Output());;
		}
	}

	private function get_attachment($item) {
		if (get_option('tainacan_pdf_show_attachements') == 'nao') 
			return "";

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

				if ($this->expose_html) {
					$temp .= '<td><span class="lista-galeria__image"><span>Anexo ' . $count .  '</span><br>' . wp_get_attachment_image( $attachment->ID, 'tainacan-interface-item-attachments' ) . '</span></td>';
				} else {
					$file = get_attached_file($attachment->ID, true);
					$info = image_get_intermediate_size($attachment->ID, 'tainacan-interface-item-attachments');
					$paths = realpath(str_replace(wp_basename($file), $info['file'], $file));
					$wp_get_attachment_image = "<img src='$paths' class='attachment-tainacan-interface-item-attachments size-tainacan-interface-item-attachments' alt='' height='125' width='125'>";
					$temp .= '<td><span class="lista-galeria__image"><span>Anexo ' . $count .  '</span><br>' . $wp_get_attachment_image . '</span></td>';
				}

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

		$attachements = "
			<div class='lista-galeria__images'>
				<table class='lista-galeria__table'>
					$attachements
				</table>
			</div>";
		return $attachements;
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

			$attachements = $this->get_attachment($item);

			$item_title = $item['title'];
			$collection_name = \Tainacan\Repositories\Collections::get_instance()->fetch($item['collection_id'], 'OBJECT')->get_name();

			$item_thumbnail = "";
			if( !empty($item['_thumbnail_id']) || !empty($item['thumbnail']) ) {

				if ($this->expose_html) {
					$img_thumbnail = get_the_post_thumbnail($item['id'], 'tainacan-medium-full');
				} else {
					$id_attachment = get_post_thumbnail_id( $post_id );
					$file = get_attached_file($id_attachment, true);
					$info = image_get_intermediate_size($id_attachment, 'tainacan-medium-full');
					$paths = realpath(str_replace(wp_basename($file), $info['file'], $file));
					$img_thumbnail = "<img src='$paths' class='attachment-tainacan-medium-full size-tainacan-medium-full wp-post-image'>";
				}

				$item_thumbnail = "<div class='lista-galeria__thumb'> $img_thumbnail </div>";
			}

			$pagebreak = $this->one_item_per_page ? '<tocpagebreak>%s</tocpagebreak>':'<div class="border-bottom">%s</div>';

			$logo = get_option('tainacan_pdf_logo_url');
			if (empty($logo)) {
				$logo = plugins_url('/statics/img/lgo/thumbnail_placeholder.jpg',__FILE__ );
			}
			$name = get_option('tainacan_pdf_nome_instituicao');
			$items_list[] = sprintf($pagebreak, "
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
						$item_thumbnail
						<ul class='lista-galeria__dados'>
							$lis
						</ul>
						$attachements
					</div>
				");
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

	private function get_cover_page() {
		if(get_option('tainacan_pdf_cover_page') == 'sim') {
			$logo = get_option('tainacan_pdf_logo_url');
			if (empty($logo)) {
				$logo = plugins_url('/statics/img/lgo/thumbnail_placeholder.jpg',__FILE__ );
			}
			$name = get_option('tainacan_pdf_nome_instituicao');
			$cover_page = "
				<div class='box-principal'>
					<img class='box-principal__logo' src='$logo' alt='Museu' />
					<h1 class='box-principal__instituicao'>$name</h1>
					<p>Este é um documento PDF gerado automaticamente.</p>
				</div>";
			return $cover_page;
		}
		return "";
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
				  %s
					%s
				</body>
			</html>
		", $head, $this->get_cover_page(), $body);
	}

	private function get_head() {
		$main_css = plugins_url('/statics/css/main.css',__FILE__ );
		return '
			<title>PDF Tainacan</title>
			<link rel="stylesheet" type="text/css" href="' . $main_css . '">
		';
	}

	private function create_head() {}

	private function create_footer() {
		$now_date = date('m/d/Y');
		$logo_tainacan = plugins_url('/statics/img/lgo/tainacan.svg',__FILE__ );
		return "
		<table class='rodape'>
			<tr>
				<td class='logo'>
					<img class='tainacan-logo' src='$logo_tainacan' alt='Tainacan' />
				</td>
				<td class='paginacao col-center'>
					{PAGENO}/{nbpg}
				</td>
				<td class='data col-right'>
					$now_date
				</td>
			</tr>
		</table>
		";
		
	}
}
