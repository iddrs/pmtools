<?php


/**
 * Calcula o fluxo de caixa projetado para o encerramento do exercício.
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once 'vendor/autoload.php';

printnl('=======================================================================');
printnl('FLUXO DE CAIXA PROJETADO');
printnl('Calcula o fluxo de caixa projetado por fonte de recursos.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();
notice("Remessa selecionada: {$remessa->remessa}.");


$formula_saldo_final = '=SUM(fluxo_caixa[[#This Row],[saldo_bruto]:[a_arrecadar]])-SUM(fluxo_caixa[[#This Row],[empenhado_a_pagar]:[extra_a_pagar]])';

$data_final = $remessa->dataBase->format('Y-m-d');
$data_inicial = $remessa->inicioDoMes->format('Y-m-d');

$data = [];
$data[] = [
    'fonte_recurso',
    'nome_fonte_recurso',
    'saldo_bruto',
    'a_arrecadar',
    'empenhado_a_pagar',
    'a_empenhar',
    'rp_a_pagar',
    'duodecimo',
    'extra_a_pagar',
    'saldo_liquido',
];
$frs = pg_query_params(connect(), "select distinct recurso_vinculado as fonte_recurso, nome_recurso_vinculado as nome_fonte_recurso from pad.recurso where remessa = $1 and recurso_vinculado <= 899 order by recurso_vinculado asc", [$remessa->remessa]);

while($row = pg_fetch_assoc($frs)) {
    $incluir = false;
    $line = [];
    $fr = $row['fonte_recurso'];
    $nome_fr = $row['nome_fonte_recurso'];

    $line['fonte_recurso'] = $fr;
    $line['nome_fonte_recurso'] = $nome_fr;

    $saldo_bruto = round((float) pg_fetch_result(pg_query_params(connect(), "select sum(saldo_atual)::decimal as saldo_bruto from pad.bal_ver where remessa = $1 and conta_contabil like '1%%' and entidade like 'pm' and indicador_superavit_financeiro like 'F' and escrituracao like 'S' and fonte_recurso = $2", [$remessa->remessa, $fr]), 0, 0), 2);
    $line['saldo_bruto'] = $saldo_bruto;
    if($saldo_bruto != 0) $incluir = true;
    
    $a_arrecadar = round((float) pg_fetch_result(pg_query_params(connect(), "select sum(a_arrecadar_atualizado)::decimal as a_arrecadar from pad.bal_rec where remessa = $1 and entidade like 'pm' and fonte_recurso = $2", [$remessa->remessa, $fr]), 0, 0), 2);
    $line['a_arrecadar'] = $a_arrecadar = ($a_arrecadar < 0)? 0.0 : $a_arrecadar;
    if($a_arrecadar != 0) $incluir = true;
    
    $empenhado_a_pagar = round((float) pg_fetch_result(pg_query_params(connect(), "select sum(empenhado_a_pagar)::decimal as empenhado_a_pagar from pad.bal_desp where remessa = $1 and entidade like 'pm' and fonte_recurso = $2", [$remessa->remessa, $fr]), 0, 0), 2);
    $line['empenhado_a_pagar'] = $empenhado_a_pagar;
    if($empenhado_a_pagar != 0) $incluir = true;
    
    $a_empenhar = round((float) pg_fetch_result(pg_query_params(connect(), "select sum(saldo_a_empenhar)::decimal as a_empenhar from pad.bal_desp where remessa = $1 and entidade like 'pm' and fonte_recurso = $2", [$remessa->remessa, $fr]), 0, 0), 2);
    $line['a_empenhar'] = $a_empenhar;
    if($a_empenhar != 0) $incluir = true;
    
    $rp_a_pagar = round((float) pg_fetch_result(pg_query_params(connect(), "select sum(rp_saldo_final)::decimal as rp_a_pagar from pad.restos_pagar where remessa = $1 and entidade like 'pm' and fonte_recurso = $2", [$remessa->remessa, $fr]), 0, 0), 2);
    $line['rp_a_pagar'] = $rp_a_pagar;
    if($rp_a_pagar != 0) $incluir = true;

    $duodecimo = round((float) pg_fetch_result(pg_query_params(connect(), "select sum(saldo_atual)::decimal as duodecimo from pad.bal_ver where remessa = $1 and conta_contabil like '2189202%%' and entidade like 'pm' and escrituracao like 'S'", [$remessa->remessa]), 0, 0), 2);
    $line['duodecimo'] = $duodecimo = ($fr == 500)? $duodecimo : 0.0;
    if($duodecimo != 0) $incluir = true;
    
    $extra_a_pagar = round((float) pg_fetch_result(pg_query_params(connect(), "select sum(saldo_atual)::decimal as extra_a_pagar from pad.bal_ver where remessa = $1 and conta_contabil like '2188%%' and fonte_recurso = $2 and entidade like 'pm' and indicador_superavit_financeiro like 'F' and escrituracao like 'S'", [$remessa->remessa, $fr]), 0, 0), 2);
    $line['extra_a_pagar'] = $extra_a_pagar;
    if($extra_a_pagar != 0) $incluir = true;

    if($incluir) {
//        $line['saldo_liquido'] = round($saldo_bruto + $a_arrecadar - $empenhado_a_pagar - $a_empenhar - $rp_a_pagar - $duodecimo - $extra_a_pagar, 2);
        $line['saldo_liquido'] = $formula_saldo_final;
        $data[$fr] = $line;
    }

}

// Preparando a planilha de dados.
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
        ->setCreator($cfg['creator'])
        ->setTitle("Fluxo de Caixa Projetado")
        ->setSubject("Fluxo de Caixa Projetado")
        ->setDescription("Fluxo de Caixa Projetado para o município de {$cfg['municipio']} na data-base de {$remessa->dataBase->format('d/m/Y')}")
        ->setKeywords('fluxo de caixa')
        ->setCategory('Table');
$data_sheet = $spreadsheet->getActiveSheet();
$data_sheet->setTitle('FCP');

// Preparando planilha de metadados
$meta_sheet = $spreadsheet->createSheet(1);
$meta_sheet->setTitle('Meta');
$meta_sheet->setCellValue('A1', 'data_base');
$meta_sheet->setCellValue('A2', $remessa->dataBase->format('d/m/Y'));
$meta_sheet->setCellValue('B1', 'gerado_em');
$meta_sheet->setCellValue('B2', date('d/m/Y H:i:s'));

// Salvando os dados
$data_sheet->fromArray($data);

// Criando tabela com os dados
$last_line = sizeof($data);
$table = new Table();
$table->setName('fluxo_caixa');
$table->setRange("A1:J$last_line");
$data_sheet->addTable($table);

// Formatando colunas
$data_sheet->getStyle("C1:J$last_line")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
$data_sheet->getColumnDimension('A')->setAutoSize(true);
//$data_sheet->getColumnDimension('B')->setAutoSize(true);
$data_sheet->getColumnDimension('C')->setAutoSize(true);
$data_sheet->getColumnDimension('D')->setAutoSize(true);
$data_sheet->getColumnDimension('E')->setAutoSize(true);
$data_sheet->getColumnDimension('F')->setAutoSize(true);
$data_sheet->getColumnDimension('G')->setAutoSize(true);
$data_sheet->getColumnDimension('H')->setAutoSize(true);
$data_sheet->getColumnDimension('I')->setAutoSize(true);
$data_sheet->getColumnDimension('J')->setAutoSize(true);


// Gravando a planilha
$writer = new Xlsx($spreadsheet);
$output_file = $cfg['desktop_path'].'fluxo-caixa-'.$remessa->remessa.'.xlsx';
$writer->save($output_file);

notice("Dados gravados em $output_file");

notice('Processo terminado!');



echo PHP_EOL;
echo PHP_EOL;
