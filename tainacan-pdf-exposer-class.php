<?php

namespace TainacanPDFExposer;

class Exposer extends \Tainacan\Exposers\Exposer
{
	public $slug = 'exposer-pdf';
	public $mappers = false;
	public $accept_no_mapper = true;
	public $one_item_per_page = false;
	public $images = array();

	function __construct()
	{
		wp_enqueue_style('tainacan_pdf_main');
		$this->set_name(__('PDF'));
		$this->set_description(__('Exposer items as PDF', 'pdf-exposer'));
		$this->pdf_cover_page = get_option('tainacan_pdf_cover_page') == 'sim';
		if (get_option('tainacan_one_item_per_page') == 'sim')
			$this->one_item_per_page = true;
		else
			$this->one_item_per_page = false;
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see \Tainacan\Exposers\Types\Type::rest_request_after_callbacks()
	 */
	public function rest_request_after_callbacks($response, $handler, $request)
	{
		try {
			$settings = [
				'docDef' => $this->get_doc_definition($response->get_data()['items'])
			];
			
			$head = $this->get_head($settings);
			$html = sprintf("
				<!doctype html>
					<html>
						<head> %s </head>
						<body>
							<div class='box-principal'>
								 <h1 class='box-principal__instituicao'>Tainacan</h1>
							<p>Este é um documento PDF gerado automaticamente.</p>
							<a href='#' onclick='pdfMake.createPdf(\$settings.docDef).download();'> Abrir </a>
						</div>
					</body>
				</html>", $head);

			$response->set_headers(['Content-Type: text/html; charset=' . get_option('blog_charset')]);
			$response->set_data(addslashes($html));
			return $response;
		} catch (\Exception $e) {
			$response->set_headers([
				'Content-Type: text/html; charset=' . get_option('blog_charset')
			]);

			$response->set_data("Falha ao gerar PDF");
			return $response;
		}
	}

	private function get_key_image($url_img)
	{
		$key = 'img-' . count($this->images);
		$this->images[$key] = ($url_img);
		return $key;
	}

	private function get_attachment($item)
	{
		if (get_option('tainacan_pdf_show_attachements') == 'nao')
			return [];

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

		$attachements = array(
			['text' => 'Anexos', 'noWrap' => true],
			[
				'columns' => [
					['stack' => array()],
					['stack' => array()],
					['stack' => array()]
				]
			]
		);

		if (!empty($attachment_list)) {
			$count = 0;
			foreach ($attachment_list as $attachment) {
				$id = $count % 3;
				$img = array(
					'image' => $this->get_key_image(wp_get_attachment_image_url($attachment->ID)),
					'width' => 100,
					'margin' => [10, 10, 10, 10],
					'alignment' => 'center'
				);
				$attachements[1]['columns'][$id]['stack'][] = $img;
				$count++;
			}
		}
		return $attachements;
	}

	protected function get_doc_definition($data)
	{
		$docDef = array(
			'pageSize' => 'A4',
			'styles' => [
				'header' => [
					'fontSize' => 18,
					'bold' => true
				],
				'subheader' => [
					'fontSize' => 15,
					'bold' => true
				],
				'quote' => [
					'italics' => true
				],
				'small' => [
					'fontSize' => 8
				],
				'tableHeader' => [
					'bold' => true,
					'fontSize' => 13,
					'color' => 'black'
				]
			],
			'content' => [
				'stack' => []
			]
		);
		if ($this->pdf_cover_page) {
			$docDef['content']['stack'][] = $this->get_cover_page();
		}

		foreach ($data as $item) {
			$metaBody = array(
				[
					['text' => 'Metadado', 'style' => 'tableHeader'],
					['text' => 'Valor', 'style' => 'tableHeader']
				]
			);
			foreach ($item['metadata'] as $metadata) {
				$metaTitle = array(
					"text"   => $metadata["name"],
					"noWrap" => true
				);
				$value = array(
					"text" => empty($metadata["value_as_string"])
						? 'Valor não informado'
						: $metadata["value_as_string"]
				);
				$metaBody[] = [$metaTitle, $value];
			}

			$attachements = $this->get_attachment($item);
			if (!empty($attachements)) $metaBody[] = $attachements;

			$tableMetadata = array(
				'style' => 'tableMetadata',
				'layout' => 'lightHorizontalLines',
				'table' => [
					'widths' => ['auto', '*'],
					'body' => $metaBody
				]
			);

			$logo = get_option('tainacan_pdf_logo_url');
			$name = get_option('tainacan_pdf_nome_instituicao');
			$collection_name = \Tainacan\Repositories\Collections::get_instance()->fetch($item['collection_id'], 'OBJECT')->get_name();
			$docDef['content']['stack'][] =
				[
					'pageBreak' => (!empty($docDef['content']['stack'])) && $this->one_item_per_page ? 'before' : false,
					'alignment' => 'justify',
					'columns' => [
						[
							'stack' => [
								['text' => $name, 'style' => 'header'],
								['text' => "Coleção: $collection_name", 'style' => 'subheader']
							]
						],
						[
							'maxWidth' => 50,
							'maxHeight' => 50,
							'image' => $this->get_key_image($logo),
							'alignment' => 'right'
						]
					]

				];

			$docDef['content']['stack'][] = ['text' => isset($item['title']) ? $item['title'] : '', 'style' => 'subheader'];

			if (!empty($item['_thumbnail_id']) || !empty($item['thumbnail'])) {
				$img_thumbnail = get_the_post_thumbnail_url($item['id'], 'tainacan-medium-full');
			}
			$docDef['content']['stack'][] = [
				'image' => $this->get_key_image($img_thumbnail),
				'maxWidth' => 300,
				'alignment' => 'center',
				'margin' => [20, 20, 20, 20]
			];
			$docDef['content']['stack'][] = $tableMetadata;
		}
		$docDef['images'] = $this->images;
		return $docDef;
	}

	private function get_cover_page()
	{
		$name = get_option('tainacan_pdf_nome_instituicao');
		$logo = get_option('tainacan_pdf_logo_url');
		if (empty($logo)) {
			$logo = ('./statics/img/lgo/thumbnail_placeholder.jpg');
		}
		return array(
			'pageBreak' => $this->one_item_per_page ? false : 'after',
			'stack' => [
				[
					'maxWidth' => 400,
					'alignment' => 'center',
					'image' => $this->get_key_image($logo),
					'margin' => [0, 200, 0, 20]
				],
				[
					'text' => $name,
					'style' => 'subheader',
					'alignment' => 'center'
				], [
					'text' => "Este é um documento PDF gerado automaticamente",
					'style' => 'quote',
					'alignment' => 'center'
				]
			]
		);
	}

	private function get_head($settings = [])
	{
		$settings = json_encode($settings);
		$main_css = plugins_url('/statics/css/main.css', __FILE__);
		$main_js = plugins_url('/statics/js/main.js', __FILE__);
		return '
			<title>PDF Tainacan</title>
			<link rel="stylesheet" type="text/css" href="' . $main_css . '">
			<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
			<script>
			 var $settings = ' . $settings . '
			</script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js" integrity="sha512-HLbtvcctT6uyv5bExN/qekjQvFIl46bwjEq6PBvFogNfZ0YGVE+N3w6L+hGaJsGPWnMcAQ2qK8Itt43mGzGy8Q==" crossorigin="anonymous"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.min.js" integrity="sha512-VIF8OqBWob/wmCvrcQs27IrQWwgr3g+iA4QQ4hH/YeuYBIoAUluiwr/NX5WQAQgUaVx39hs6l05cEOIGEB+dmA==" crossorigin="anonymous"></script>
			<script src="' . $main_js . '" crossorigin="anonymous"></script>
		';
	}
}
