<?php

/**
 * Calcula o valor da disponibilidade financeira livre na data-base.
 * 
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('DISPONIBILIDADE FINANCEIRA LIVRE');
printnl('Saldo disponível das fontes de recursos livres na data-base.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();

notice("Remessa selecionada: {$remessa->remessa}.");

// Saldo financeiro bruto
$sql = "SELECT
    sum(saldo_atual)::numeric AS saldo_financeiro
    FROM pad.bal_ver
  WHERE escrituracao like 'S' AND entidade like 'pm' and fonte_recurso in (500, 501, 502, 869) and conta_contabil like '111%%' and indicador_superavit_financeiro like 'F'
  AND remessa = $1;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$saldo_financeiro = pg_fetch_assoc($result, 0)['saldo_financeiro'];


// Saldo de restos a pagar
$sql = "SELECT
    sum(rp_saldo_final)::numeric AS rp
    FROM pad.restos_pagar
  WHERE entidade like 'pm' and fonte_recurso in (500, 501, 502, 869)
  AND remessa = $1;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$rp = pg_fetch_assoc($result, 0)['rp'];

// Saldo de empenhado a pagar
$sql = "SELECT
    sum(empenhado_a_pagar)::numeric AS empenhado_a_pagar
    FROM pad.bal_desp
  WHERE entidade like 'pm' and fonte_recurso in (500, 501, 502, 869)
  AND remessa = $1;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$empenhado_a_pagar = pg_fetch_assoc($result, 0)['empenhado_a_pagar'];

// Duodécimo a repassar
$sql = "SELECT
    sum(saldo_atual)::numeric AS duodecimo
    FROM pad.bal_ver
  WHERE escrituracao like 'S' AND entidade like 'pm' and conta_contabil like '2189202%%'
  AND remessa = $1;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$duodecimo = pg_fetch_assoc($result, 0)['duodecimo'];

// Outras obrigações
$sql = "SELECT
    sum(saldo_atual)::numeric AS outras
    FROM pad.bal_ver
  WHERE escrituracao like 'S' AND entidade like 'pm' and fonte_recurso in (500, 501, 502, 869) and conta_contabil like '2188%%' and indicador_superavit_financeiro like 'F'
  AND remessa = $1;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$outras_passivo = pg_fetch_assoc($result, 0)['outras'];
$sql = "SELECT
    sum(saldo_atual)::numeric AS outras
    FROM pad.bal_ver
  WHERE escrituracao like 'S' AND entidade like 'pm' and fonte_recurso in (500, 501, 502, 869) and conta_contabil like '113%%' and indicador_superavit_financeiro like 'F'
  AND remessa = $1;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$outras_ativo= pg_fetch_assoc($result, 0)['outras'];

$outras = $outras_passivo - $outras_ativo;

printnl('');
printnl('');

// Resumo
printnl('=======================================================================');
printnl('RESULTADO');
printnl('-----------------------------------------------------------------------');
printnl(sprintf("Saldo financeiro:\t%s", fmt_currency($saldo_financeiro)));
printnl(sprintf("Saldo de RP:\t%s", fmt_currency($rp)));
printnl(sprintf("Empenhado a pagar:\t%s", fmt_currency($empenhado_a_pagar)));
printnl(sprintf("Duodécimo a repassar:\t%s", fmt_currency($duodecimo)));
printnl(sprintf("Outras obrigações:\t%s", fmt_currency($outras)));
printnl('-----------------------------------------------------------------------');


printnl('=======================================================================');

notice('Processo terminado!');