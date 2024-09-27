<?php

/**
 * @description: Calcula o valor de arrecadação própria para a remessa.
 * 
 * Arrecadação própria corresponde ao valor dos recursos previstos/arrecadados pelo esforço direto do ente.
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('ARRECADAÇÃO PRÓPRIA');
printnl('Calcula o valor das receitas que dependem do próprio esforço de');
printnl('arrecadação.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();

notice("Remessa selecionada: {$remessa->remessa}.");

// Calcula os valores
$sql = "SELECT
    sum(receita_orcada)::numeric AS previsao_inicial,
    sum(previsao_atualizada)::numeric AS previsao_atualizada,
    sum(receita_realizada)::numeric AS arrecadado
   FROM pad.bal_rec
  WHERE tipo_nivel_receita ~~ 'A'::text
    AND (fonte_recurso = ANY (ARRAY[500, 501, 502]))
    AND natureza_receita::text !~~ '17%'::text
    AND entidade::text ~~ 'pm'::text
    AND remessa = $1;";

// Acumulado da remessa
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$previsao_inicial = pg_fetch_assoc($result, 0)['previsao_inicial'];
$previsao_atualizada = pg_fetch_assoc($result, 0)['previsao_atualizada'];
$arrecadado = pg_fetch_assoc($result, 0)['arrecadado'];

// Acumulado da remessa anterior
if($remessa->mes === 1){
    $arrecadado_anterior = 0.0;
}else{
    $remessa_anterior = sprintf('%s%02s', $remessa->ano, $remessa->mes - 1);
    $result = pg_query_params(connect(), $sql, [$remessa_anterior]);
    $arrecadado_anterior = pg_fetch_assoc($result, 0)['arrecadado'];
}
$arrecadado_mes = $arrecadado - $arrecadado_anterior;

printnl('');
printnl('');

// Resumo
printnl('=======================================================================');
printnl('RESULTADO');
printnl('-----------------------------------------------------------------------');
printnl("\t\tPrevisão Inicial\tPrevisão Atualizada\tArrecadado");
printnl(sprintf("No mês\t\t\t\t%s\t\t%s\t\t%s", '', '', fmt_currency($arrecadado_mes)));
printnl(sprintf("Acumulado\t%s\t\t%s\t\t%s", fmt_currency($previsao_inicial), fmt_currency($previsao_atualizada), fmt_currency($arrecadado)));
printnl('-----------------------------------------------------------------------');



printnl('=======================================================================');

notice('Processo terminado!');