<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Calcula o valor do Fundeb 70% e 30%
 * 
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('SIOPE: Fundeb 70% e 30%');
printnl('Valores liquidados no mês.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();
$data_final = $remessa->dataBase->format('Y-m-d');
$data_inicial = $remessa->inicioDoMes->format('Y-m-d');

notice("Remessa selecionada: {$remessa->remessa}.");

// Calcula o Fundeb 70%
$sql = "SELECT
	SUM(VALOR_LIQUIDACAO)::decimal AS FUNDEB70
FROM
	PAD.LIQUIDACAO
WHERE
	REMESSA = $1
	AND DATA_LIQUIDACAO BETWEEN $2 AND $3
	AND FONTE_RECURSO = 540
	AND RUBRICA LIKE '31%%'
	AND CODIGO_ACOMPANHAMENTO_ORCAMENTARIO = 1070;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $data_inicial, $data_final]);


if($result === false) {
    throw new Exception("Falha ao executar a query [$sql] com a remessa [{$remessa->remessa}]". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}

$num_rows = pg_num_rows($result);

if($num_rows === 0) {
    alert("Nenhum registro retornado para a remessa [{$remessa->remessa}].");
    notice('Saindo...');
    exit();
}

$fundeb70= round(pg_fetch_result($result, 0, 0), 2);

notice(sprintf('Fundeb 70%% liquidado em %s: %s', $remessa->dataBase->format('m/Y'), fmt_currency($fundeb70)));



// Calcula o Fundeb 30%
$sql = "SELECT
	SUM(VALOR_LIQUIDACAO)::decimal AS FUNDEB70
FROM
	PAD.LIQUIDACAO
WHERE
	REMESSA = $1
	AND DATA_LIQUIDACAO BETWEEN $2 AND $3
	AND FONTE_RECURSO = 540
	AND RUBRICA LIKE '31%%'
	AND CODIGO_ACOMPANHAMENTO_ORCAMENTARIO <> 1070;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $data_inicial, $data_final]);


if($result === false) {
    throw new Exception("Falha ao executar a query [$sql] com a remessa [{$remessa->remessa}]". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}

$num_rows = pg_num_rows($result);

if($num_rows === 0) {
    alert("Nenhum registro retornado para a remessa [{$remessa->remessa}].");
    notice('Saindo...');
    exit();
}

$fundeb30 = pg_fetch_result($result, 0, 0);
if(is_null($fundeb30)) $fundeb30 = 0.0;
$fundeb30 = round($fundeb30, 2);

notice(sprintf('Fundeb 30%% liquidado em %s: %s', $remessa->dataBase->format('m/Y'), fmt_currency($fundeb30)));

notice('Processo terminado!');