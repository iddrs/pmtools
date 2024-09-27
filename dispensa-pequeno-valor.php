<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @description: Calcula os valores empenhados por dispensa de pequeno valor para a última remessa disponível no banco de dados.
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('DISPENSA POR PEQUENO VALOR');
printnl('Gera um relatório do valor empenhado por objeto cuja modalidade de ');
printnl('contratação é dispensa por pequeno valor.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

//$remessa = read_remessa();
//
//notice("Remessa selecionada: {$remessa->remessa}.");



// Consulta os dados
$sql = "SELECT to_char((( SELECT DISTINCT empenho.data_empenho
           FROM pad.empenho
          ORDER BY empenho.data_empenho DESC
         LIMIT 1))::timestamp with time zone, 'MM/YYYY'::text) AS \"Período\",
        CASE e.base_legal_contratacao
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
    e.forma_contratacao AS \"Forma de contratação\",
    r.especificacao_rubrica AS \"Objeto\",
    sum(e.valor_empenho)::numeric AS \"Empenhado\"
   FROM pad.empenho e
     JOIN pad.rubrica r ON e.rubrica::text = r.rubrica::text AND e.remessa = r.remessa AND e.ano_empenho = r.exercicio AND r.cnpj::text ~~ '87612826000190'::text
  WHERE e.remessa = (( SELECT DISTINCT empenho.remessa
           FROM pad.empenho
          ORDER BY empenho.remessa DESC
         LIMIT 1)) AND (e.entidade::text = ANY (ARRAY['pm'::character varying, 'fpsm'::character varying]::text[])) AND e.forma_contratacao::text ~~ 'DPV'::text AND e.data_empenho >= (( SELECT DISTINCT empenho.data_empenho
           FROM pad.empenho
          WHERE empenho.remessa = (( SELECT DISTINCT empenho_1.remessa
                   FROM pad.empenho empenho_1
                  ORDER BY empenho_1.remessa DESC
                 LIMIT 1))
          ORDER BY empenho.data_empenho
         LIMIT 1)) AND e.data_empenho <= (( SELECT DISTINCT empenho.data_empenho
           FROM pad.empenho
          WHERE empenho.remessa = (( SELECT DISTINCT empenho_1.remessa
                   FROM pad.empenho empenho_1
                  ORDER BY empenho_1.remessa DESC
                 LIMIT 1))
          ORDER BY empenho.data_empenho DESC
         LIMIT 1)) AND e.ano_empenho::numeric = ((( SELECT substr(empenho.remessa::text, 1, 4) AS ano
           FROM pad.empenho
          ORDER BY empenho.remessa DESC
         LIMIT 1))::numeric) AND (e.rubrica::text <> ALL (ARRAY['335041080000000'::character varying, '339039990400000'::character varying, '339047100000000'::character varying, '319004140000000'::character varying, '337170010000000'::character varying, '339032030100000'::character varying, '339048010400000'::character varying, '339086010000000'::character varying, '339093010300000'::character varying, '339093050000000'::character varying, '339093080000000'::character varying, '339093140000000'::character varying, '333041390200000'::character varying, '339086020000000'::character varying, '339086030000000'::character varying]::text[]))
  GROUP BY e.base_legal_contratacao, e.forma_contratacao, r.especificacao_rubrica;";
$result = pg_query(connect(), $sql);

if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}

$num_rows = pg_num_rows($result);

if($num_rows === 0) {
    alert("Nenhum registro retornado.");
    notice('Saindo...');
    exit();
}

// Preparando a planilha de dados.
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
        ->setCreator($cfg['creator'])
        ->setTitle("Dispensas por pequeno valor")
        ->setSubject("Dispensas por pequeno valor")
        ->setDescription("Dispensas por pequeno valor para o município de {$cfg['municipio']}.")
        ->setKeywords('liciatação compras dispensa')
        ->setCategory('Table');
$data_sheet = $spreadsheet->getActiveSheet();
$data_sheet->setTitle('Dados');

// Preparando planilha de metadados
$meta_sheet = $spreadsheet->createSheet(1);
$meta_sheet->setTitle('Meta');
$meta_sheet->setCellValue('A1', 'gerado_em');
$meta_sheet->setCellValue('A2', date('d/m/Y H:i:s'));

// Salvando os dados
$data = pg_fetch_all($result, PGSQL_ASSOC);
$fields = array_keys($data[array_key_first($data)]);

$data = array_merge([$fields], $data);

$data_sheet->fromArray($data);


// Colocando linha de total
$last_line = $num_rows+1;
$total_line = $last_line + 1;
$data_sheet->getCell("A$total_line")->setValue('Total');
$data_sheet->getCell("E$total_line")->setValue("=SUM(E2:E$last_line)");

// Formatando colunas
$data_sheet->getColumnDimension('A')->setAutoSize(true);
$data_sheet->getColumnDimension('B')->setAutoSize(true);
$data_sheet->getColumnDimension('C')->setAutoSize(true);
$data_sheet->getColumnDimension('D')->setAutoSize(true);
$data_sheet->getColumnDimension('E')->setAutoSize(true);
$data_sheet->getStyle("E1:E$total_line")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

$data_sheet->getStyle('A1:E1')->getFont()->setBold(true);
$data_sheet->getStyle("A$total_line:E$total_line")->getFont()->setBold(true);

$borderOptions = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['argb' => '00000000']
        ]
    ]
];
$data_sheet->getStyle("A1:E$total_line")->applyFromArray($borderOptions);

// Configurando a impressão
$data_sheet->getPageSetup()
        ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
        ->setPaperSize(PageSetup::PAPERSIZE_A4)
        ->setFitToPage(true)
        ->setFitToWidth(1)
        ->setFitToHeight(0);
$page_header = '&L&16&BValores Empenhados como Dispensa de Pequeno Valor';
$page_footer = '&LEmitido em &D &T&RPágina &P de &N';
$data_sheet->getHeaderFooter()
        ->setOddHeader($page_header)
        ->setEvenHeader($page_header)
        ->setOddFooter($page_footer)
        ->setEvenFooter($page_footer);
$data_sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

// Gravando a planilha
$writer = new Xlsx($spreadsheet);
$output_file = $cfg['desktop_path'].'dispensa-pequeno-valor.xlsx';
$writer->save($output_file);

notice("Dados gravados em $output_file");

notice('Processo terminado!');