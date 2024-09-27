<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @description: Calcula o valor das transferências da saúde.
 * 
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('SIOPE: RENDIMENTOS');
printnl('Valores de rendimentos por fontes de recursos.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();

notice("Remessa selecionada: {$remessa->remessa}.");

// Calcula os rendimentos
$sql = "SELECT
    'Recursos Próprios'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 500 AND bal_rec.codigo_acompanhamento_orcamentario = 1001
  AND bal_rec.remessa = $1
  GROUP BY 'Recursos Próprios'::text
UNION
 SELECT
    'Fundeb - Impostos'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 540
  AND bal_rec.remessa = $1
  GROUP BY 'Fundeb - Impostos'::text
UNION
 SELECT
    'Fundeb - VAAF'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 541
  AND bal_rec.remessa = $1
  GROUP BY 'Fundeb - VAAF'::text
UNION
 SELECT
    'Fundeb - VAAT'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 542
  AND bal_rec.remessa = $1
  GROUP BY 'Fundeb - VAAT'::text
UNION
 SELECT
    'Fundeb - VAAR'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 543
  AND bal_rec.remessa = $1
  GROUP BY 'Fundeb - VAAR'::text
UNION
 SELECT
    'Salário Educação'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 550
  AND bal_rec.remessa = $1
  GROUP BY 'Salário Educação'::text
UNION
 SELECT
    'PDDE'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 551
  AND bal_rec.remessa = $1
  GROUP BY 'PDDE'::text
UNION
 SELECT
    'PNAE'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 552
  AND bal_rec.remessa = $1
  GROUP BY 'PNAE'::text
UNION
 SELECT
    'PNATE'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 553
  AND bal_rec.remessa = $1
  GROUP BY 'PNATE'::text
UNION
 SELECT
    'Outras Transferências do FNDE'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 569
  AND bal_rec.remessa = $1
  GROUP BY 'Outras Transferências do FNDE'::text
UNION
 SELECT
    'Convênios destinados à Educação'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND (bal_rec.fonte_recurso >= 570 AND bal_rec.fonte_recurso <= 572 OR bal_rec.fonte_recurso = 575)
  AND bal_rec.remessa = $1
  GROUP BY 'Convênios destinados à Educação'::text
UNION
 SELECT
    'Royalties do Petróleo e Gás Natural'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 573
  AND bal_rec.remessa = $1
  GROUP BY 'Royalties do Petróleo e Gás Natural'::text
UNION
 SELECT
    'Operações de crédito'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 574
  AND bal_rec.remessa = $1
  GROUP BY 'Operações de crédito'::text
UNION
 SELECT
    'Fundeb - Impostos'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 540
  AND bal_rec.remessa = $1
  GROUP BY 'Fundeb - Impostos'::text
UNION
 SELECT
    'Outros recurso vinculados à educação'::text AS recurso,
    sum(bal_rec.receita_realizada)::numeric AS arrecadado,
    sum(bal_rec.previsao_atualizada)::numeric AS previsao
   FROM pad.bal_rec
  WHERE bal_rec.tipo_nivel_receita ~~ 'A'::text AND bal_rec.natureza_receita::text ~~ '1321%'::text AND bal_rec.fonte_recurso = 599
  AND bal_rec.remessa = $1
  GROUP BY 'Outros recurso vinculados à educação'::text;";
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
$data_sheet->getStyle("B2:C$last_line")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
$data_sheet->getColumnDimension('A')->setAutoSize(true);
$data_sheet->getColumnDimension('B')->setAutoSize(true);
$data_sheet->getColumnDimension('C')->setAutoSize(true);

$data_sheet->getStyle('A1:C1')->getFont()->setBold(true);

$borderOptions = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => '00000000']
        ]
    ]
];
$data_sheet->getStyle("A1:C$last_line")->applyFromArray($borderOptions);

// Configurando a impressão
$data_sheet->getPageSetup()
        ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
        ->setPaperSize(PageSetup::PAPERSIZE_A4)
        ->setFitToPage(true)
        ->setFitToWidth(1)
        ->setFitToHeight(0);
$page_header = '&L&16&BRendimentos da Educação para preenchimento do SIOPE';
$page_footer = '&LEmitido em &D &T&RPágina &P de &N';
$data_sheet->getHeaderFooter()
        ->setOddHeader($page_header)
        ->setEvenHeader($page_header)
        ->setOddFooter($page_footer)
        ->setEvenFooter($page_footer);
$data_sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

// Gravando a planilha
$writer = new Xlsx($spreadsheet);
$output_file = $cfg['desktop_path'].'rendimentos-siope-'.$remessa->remessa.'.xlsx';
$writer->save($output_file);

notice("Dados gravados em $output_file");

notice('Processo terminado!');