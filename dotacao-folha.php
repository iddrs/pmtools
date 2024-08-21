<?php

/**
 * Calcula o superavit ou deficit da dotação para folha de pagamento.
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('DOTAÇÃO PARA FOLHA DE PAGAMENTO');
printnl('Verifica se há superávit/déficit da dotação para o ano.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();

notice("Remessa selecionada: {$remessa->remessa}.");

// Calcula a dotação atualizada
$sql = "select sum(dotacao_atualizada)::decimal as dotacao_atualizada from pad.bal_desp where remessa = $1 and (elemento like '31%%' or elemento like '319007%%' or elemento like '339008%%') and entidade like 'pm'";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$dotacao_atualizada = round(pg_fetch_result($result, 0, 0), 2);

notice(sprintf('Dotação atualizada: %s', fmt_currency($dotacao_atualizada)));


// Calcula o valor empenhado até a data-base
$sql = "select sum(valor_empenhado)::decimal as empenhado from pad.bal_desp where remessa = $1 and (elemento like '31%%' or elemento like '319007%%' or elemento like '339008%%') and entidade like 'pm'";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$empenhado = round(pg_fetch_result($result, 0, 0), 2);

notice(sprintf('Empenhado até %s: %s', $remessa->dataBase->format('d/m/Y'), fmt_currency($empenhado)));


// Calcula o valor empenhado no mês
$sql = "select sum(valor_empenho)::decimal as empenhado from pad.empenho where remessa = $1 and (rubrica like '31%%' or rubrica like '319007%%' or rubrica like '339008%%') and entidade like 'pm' and ano_empenho <= $2 and data_empenho between $3 and $4";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
$empenhado_mes = round(pg_fetch_result($result, 0, 0), 2);

notice(sprintf('Empenhado de %s até %s: %s', $remessa->inicioDoMes->format('d/m/Y'), $remessa->dataBase->format('d/m/Y'), fmt_currency($empenhado_mes)));


// Calcula o 1/3 de férias empenhado no mês
$sql = "select sum(valor_empenho)::decimal as empenhado from pad.empenho where remessa = $1 and (rubrica like '31901142%%' or rubrica like '31901145%%') and entidade like 'pm' and ano_empenho <= $2 and data_empenho between $3 and $4";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
$terco_ferias_empenhado_mes = round((float) pg_fetch_result($result, 0, 0), 2);

notice(sprintf('1/3 de férias empenhado de %s até %s: %s', $remessa->inicioDoMes->format('d/m/Y'), $remessa->dataBase->format('d/m/Y'), fmt_currency($terco_ferias_empenhado_mes)));


// Calcula o prêmio assiduidade empenhado no mês
$sql = "select sum(valor_empenho)::decimal as empenhado from pad.empenho where remessa = $1 and rubrica like '31901147%%' and entidade like 'pm' and ano_empenho <= $2 and data_empenho between $3 and $4";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
$premio_assiduidade_empenhado_mes = round((float) pg_fetch_result($result, 0, 0), 2);

notice(sprintf('Prêmio assiduidade empenhado de %s até %s: %s', $remessa->inicioDoMes->format('d/m/Y'), $remessa->dataBase->format('d/m/Y'), fmt_currency($premio_assiduidade_empenhado_mes)));


// Calcula o 13º salário empenhado no mês
$sql = "select sum(valor_empenho)::decimal as empenhado from pad.empenho where remessa = $1 and rubrica like '31901143%%' and entidade like 'pm' and ano_empenho <= $2 and data_empenho between $3 and $4";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
$decimo_empenhado_mes = round((float) pg_fetch_result($result, 0, 0), 2);

notice(sprintf('13º salário empenhado de %s até %s: %s', $remessa->inicioDoMes->format('d/m/Y'), $remessa->dataBase->format('d/m/Y'), fmt_currency($decimo_empenhado_mes)));


// Calcula as sentenças judiciais empenhadas no mês
$sql = "select sum(valor_empenho)::decimal as empenhado from pad.empenho where remessa = $1 and rubrica like '319091%%' and entidade like 'pm' and ano_empenho <= $2 and data_empenho between $3 and $4";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
$judicial_empenhado_mes = round((float) pg_fetch_result($result, 0, 0), 2);

notice(sprintf('Sentenças judiciais empenhadas de %s até %s: %s', $remessa->inicioDoMes->format('d/m/Y'), $remessa->dataBase->format('d/m/Y'), fmt_currency($judicial_empenhado_mes)));


// Calcula as despesas de exercícios anteriores empenhadas no mês
$sql = "select sum(valor_empenho)::decimal as empenhado from pad.empenho where remessa = $1 and rubrica like '319092%%' and entidade like 'pm' and ano_empenho <= $2 and data_empenho between $3 and $4";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
$dea_empenhado_mes = round((float) pg_fetch_result($result, 0, 0), 2);

notice(sprintf('Despesas de exercícios anteriores empenhadas de %s até %s: %s', $remessa->inicioDoMes->format('d/m/Y'), $remessa->dataBase->format('d/m/Y'), fmt_currency($dea_empenhado_mes)));


// Calcula as indenizações empenhadas no mês
$sql = "select sum(valor_empenho)::decimal as empenhado from pad.empenho where remessa = $1 and rubrica like '319094%%' and entidade like 'pm' and ano_empenho <= $2 and data_empenho between $3 and $4";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
$indenizacao_empenhado_mes = round((float) pg_fetch_result($result, 0, 0), 2);

notice(sprintf('Indenizações empenhadas de %s até %s: %s', $remessa->inicioDoMes->format('d/m/Y'), $remessa->dataBase->format('d/m/Y'), fmt_currency($indenizacao_empenhado_mes)));


// Calcula o valor mensal base para empenho
$empenhar_mensal = round($empenhado_mes - $terco_ferias_empenhado_mes - $premio_assiduidade_empenhado_mes - $decimo_empenhado_mes - $judicial_empenhado_mes - $dea_empenhado_mes - $indenizacao_empenhado_mes, 2);
notice(sprintf('Valor mensal a empenhar: %s', fmt_currency($empenhar_mensal)));


// Calcula os meses a empenhar
$meses_a_empenhar = 13 - $remessa->mes;
notice("Meses a empenhar: $meses_a_empenhar");


// Calcula o valor a empenhar até o fim do ano
$a_empenhar = round($empenhar_mensal * $meses_a_empenhar, 2);
notice(sprintf('Total a empenhar de %s até %s: %s:',
        $remessa->fimDoMes->modify('+1 day')->format('d/m/Y'),
        $remessa->fimDoAno->format('d/m/Y'),
        fmt_currency($a_empenhar)
        ));


// Calcula a dotação necessária
$dotacao_necessaria = round($empenhado + $a_empenhar, 2);
notice(sprintf('Dotação necessária: %s:',
        fmt_currency($a_empenhar)
        ));


// Calcula o resultado
$resultado = round($dotacao_atualizada - $dotacao_necessaria, 2);
notice(sprintf('Resultado: %s:',
        fmt_currency($a_empenhar)
        ));

printnl('');
printnl('');

// Resumo
printnl('=======================================================================');
printnl('RESUMO');
printnl('-----------------------------------------------------------------------');
printnl("Dotação Atualizada\tDotação Necessária\tResultado");
printnl(sprintf("%s\t\t%s\t\t%s", fmt_currency($dotacao_atualizada), fmt_currency($dotacao_necessaria), fmt_currency($resultado)));
printnl('-----------------------------------------------------------------------');



printnl('=======================================================================');

notice('Processo terminado!');