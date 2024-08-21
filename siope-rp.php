<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Calcula o valor das transferências da saúde.
 * 
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('SIOPE: RESTOS A PAGAR');
printnl('Valores de restos a pagar por fontes de recursos.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();

notice("Remessa selecionada: {$remessa->remessa}.");

// Calcula os rendimentos
$sql = "SELECT
    'Recurso Próprio'::text AS recurso,
    sum(restos_pagar.rp_saldo_inicial) AS saldo_inicial,
    sum(restos_pagar.rp_cancelado) AS cancelado,
    sum(restos_pagar.rp_liquidado) AS liquidado,
    sum(restos_pagar.rp_pago) AS pago,
    sum(restos_pagar.rp_saldo_final) AS saldo_final
   FROM pad.restos_pagar
  WHERE restos_pagar.fonte_recurso = 500 AND restos_pagar.codigo_acompanhamento_orcamentario = 1001
  AND restos_pagar.remessa = $1
  GROUP BY 'Recurso Próprio'::text
UNION
 SELECT
    'Fundeb - Impostos'::text AS recurso,
    sum(restos_pagar.rp_saldo_inicial) AS saldo_inicial,
    sum(restos_pagar.rp_cancelado) AS cancelado,
    sum(restos_pagar.rp_liquidado) AS liquidado,
    sum(restos_pagar.rp_pago) AS pago,
    sum(restos_pagar.rp_saldo_final) AS saldo_final
   FROM pad.restos_pagar
  WHERE restos_pagar.fonte_recurso = 540
  AND restos_pagar.remessa = $1
  GROUP BY 'Fundeb - Impostos'::text
UNION
 SELECT
    'Fundeb - VAAF + VAAT'::text AS recurso,
    sum(restos_pagar.rp_saldo_inicial) AS saldo_inicial,
    sum(restos_pagar.rp_cancelado) AS cancelado,
    sum(restos_pagar.rp_liquidado) AS liquidado,
    sum(restos_pagar.rp_pago) AS pago,
    sum(restos_pagar.rp_saldo_final) AS saldo_final
   FROM pad.restos_pagar
  WHERE restos_pagar.fonte_recurso >= 541 AND restos_pagar.fonte_recurso <= 542
  AND restos_pagar.remessa = $1
  GROUP BY 'Fundeb - VAAF + VAAT'::text
UNION
 SELECT
    'Salário Educação'::text AS recurso,
    sum(restos_pagar.rp_saldo_inicial) AS saldo_inicial,
    sum(restos_pagar.rp_cancelado) AS cancelado,
    sum(restos_pagar.rp_liquidado) AS liquidado,
    sum(restos_pagar.rp_pago) AS pago,
    sum(restos_pagar.rp_saldo_final) AS saldo_final
   FROM pad.restos_pagar
  WHERE restos_pagar.fonte_recurso = 550
  AND restos_pagar.remessa = $1
  GROUP BY 'Salário Educação'::text;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);


if($result === false) {
    throw new Exception("Falha ao executar a query [$sql] com a remessa [{$remessa->remessa}]". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}

$num_rows = pg_num_rows($result);

if($num_rows === 0) {
    alert("Nenhum registro retornado para a remessa [{$remessa->remessa}].");
    notice('Saindo...');
    exit();
}

// Preparando a planilha de dados.
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
        ->setCreator($cfg['creator'])
        ->setCategory('Table');
$data_sheet = $spreadsheet->getActiveSheet();
$data_sheet->setTitle('Dados');

// Preparando planilha de metadados
$meta_sheet = $spreadsheet->createSheet(1);
$meta_sheet->setTitle('Meta');
$meta_sheet->setCellValue('A1', 'data_base');
$meta_sheet->setCellValue('A2', $remessa->dataBase->format('d/m/Y'));
$meta_sheet->setCellValue('B1', 'gerado_em');
$meta_sheet->setCellValue('B2', date('d/m/Y H:i:s'));

// Salvando os dados
$data = pg_fetch_all($result, PGSQL_ASSOC);
$fields = array_keys($data[array_key_first($data)]);

$data = array_merge([$fields], $data);

$data_sheet->fromArray($data);

$last_line = $num_rows+1;

// Formatando colunas
$data_sheet->getStyle("B2:F$last_line")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
$data_sheet->getColumnDimension('A')->setAutoSize(true);
$data_sheet->getColumnDimension('B')->setAutoSize(true);
$data_sheet->getColumnDimension('C')->setAutoSize(true);
$data_sheet->getColumnDimension('D')->setAutoSize(true);
$data_sheet->getColumnDimension('E')->setAutoSize(true);
$data_sheet->getColumnDimension('F')->setAutoSize(true);

$data_sheet->getStyle('A1:F1')->getFont()->setBold(true);

$borderOptions = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => '00000000']
        ]
    ]
];
$data_sheet->getStyle("A1:F$last_line")->applyFromArray($borderOptions);

// Configurando a impressão
$data_sheet->getPageSetup()
        ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
        ->setPaperSize(PageSetup::PAPERSIZE_A4)
        ->setFitToPage(true)
        ->setFitToWidth(1)
        ->setFitToHeight(0);
$page_header = '&L&16&BREstos a Pagar da Educação para preenchimento do SIOPE';
$page_footer = '&LEmitido em &D &T&RPágina &P de &N';
$data_sheet->getHeaderFooter()
        ->setOddHeader($page_header)
        ->setEvenHeader($page_header)
        ->setOddFooter($page_footer)
        ->setEvenFooter($page_footer);
$data_sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

// Gravando a planilha
$writer = new Xlsx($spreadsheet);
$output_file = $cfg['desktop_path'].'restos-a-pagar-siope-'.$remessa->remessa.'.xlsx';
$writer->save($output_file);

notice("Dados gravados em $output_file");

notice('Processo terminado!');