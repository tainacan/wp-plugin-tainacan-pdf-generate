console.log("Init PdfMake", $settings);
pdfMake.createPdf($settings.docDef).download();
