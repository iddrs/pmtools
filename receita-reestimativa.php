<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Calcula a reestimativa da receita orçamentária.
 * 
 * Método de cálculo:
 * 
 * Receita arrecadada até o mês de referência + receita prevista dos meses seguintes.
 */

require 'vendor/autoload.php';

$cfg = load_config();

$remessa = read_remessa();

notice("Remessa selecionada: {$remessa->remessa}.");

// Identifica quais campos são de arrecadação
$campos_arrecadacao = [
    'realizada_jan',
    'realizada_fev',
    'realizada_mar',
    'realizada_abr',
    'realizada_mai',
    'realizada_jun',
    'realizada_jul',
    'realizada_ago',
    'realizada_set',
    'realizada_out',
    'realizada_nov',
    'realizada_dez'
];

$meses_arrecadacao = array_slice($campos_arrecadacao, 0, $remessa->mes);

// Identifica quais campos são de previsão
$campos_previsao = [
    'meta_jan',
    'meta_fev',
    'meta_mar',
    'meta_abr',
    'meta_mai',
    'meta_jun',
    'meta_jul',
    'meta_ago',
    'meta_set',
    'meta_out',
    'meta_nov',
    'meta_dez'
];

$meses_previsao = array_slice($campos_previsao, $remessa->mes);

// Monta a string sql
$str_arrecadado = join(' + ', $meses_arrecadacao);
$str_previsto= join(' + ', $meses_previsao);
$str_previsao_total = join(' + ', $campos_previsao);
$sql = "select codigo_receita, natureza_receita, categoria_receita, tipo_receita, orgao, uniorcam, caracteristica_peculiar_receita, indicador_exercicio_fonte_recurso, fonte_recurso, codigo_acompanhamento_orcamentario, entidade, sum($str_arrecadado)::numeric as arrecadado, sum($str_previsto)::numeric as a_arrecadar, sum($str_arrecadado + $str_previsto)::numeric as reestimativa, sum($str_previsao_total)::numeric as previsao, sum($str_previsao_total - ($str_arrecadado + $str_previsto))::numeric as resultado from pad.receita where remessa = $1 group by codigo_receita, natureza_receita, categoria_receita, tipo_receita, orgao, uniorcam, caracteristica_peculiar_receita, indicador_exercicio_fonte_recurso, fonte_recurso, codigo_acompanhamento_orcamentario, entidade order by codigo_receita asc";

// Consulta os dados
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
        ->setTitle("Reestimativa da receita")
        ->setSubject("Reestimativa da receita")
        ->setDescription("Reestimativa da receita para o município de {$cfg['municipio']} na data-base de {$remessa->dataBase->format('d/m/Y')}")
        ->setKeywords('receita orçamentária reestimativa')
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

// Criando tabela com os dados
$last_line = $num_rows+1;
$table_range = "A1:P$last_line";
$table = new Table($table_range, 'reestimativa');
$data_sheet->addTable($table);

// Formatando colunas
$data_sheet->getStyle("A1:B$last_line")->getNumberFormat()->setFormatCode('###############');
$data_sheet->getStyle("L1:P$last_line")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
$data_sheet->getColumnDimension('A')->setAutoSize(true);
$data_sheet->getColumnDimension('B')->setAutoSize(true);
$data_sheet->getColumnDimension('L')->setAutoSize(true);
$data_sheet->getColumnDimension('M')->setAutoSize(true);
$data_sheet->getColumnDimension('N')->setAutoSize(true);
$data_sheet->getColumnDimension('O')->setAutoSize(true);
$data_sheet->getColumnDimension('P')->setAutoSize(true);

// Gravando a planilha
$writer = new Xlsx($spreadsheet);
$output_file = $cfg['desktop_path'].'reestimativa-receita-'.$remessa->remessa.'.xlsx';
$writer->save($output_file);

notice("Dados gravados em $output_file");

notice('Processo terminado!');