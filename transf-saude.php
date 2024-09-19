<?php

/**
 * Calcula o valor das transferências da saúde.
 * 
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('TRANSFERÊNCIAS DA SAÚDE');
printnl('Valor acumulado no ano da receita de transferências da saúde.');
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
  WHERE tipo_nivel_receita ~~ 'A'::text AND fonte_recurso >= 600 AND fonte_recurso <= 659 AND natureza_receita::text ~~ '17%'::text AND entidade::text ~~ 'pm'::text
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

/*$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$previsao_inicial = pg_fetch_assoc($result, 0)['previsao_inicial'];
$previsao_atualizada = pg_fetch_assoc($result, 0)['previsao_atualizada'];
$arrecadado = pg_fetch_assoc($result, 0)['arrecadado'];

printnl('');
printnl('');

// Resumo
printnl('=======================================================================');
printnl('RESULTADO');
printnl('-----------------------------------------------------------------------');
printnl("Previsão Inicial\tPrevisão Atualizada\tArrecadado");
printnl(sprintf("%s\t\t%s\t\t%s", fmt_currency($previsao_inicial), fmt_currency($previsao_atualizada), fmt_currency($arrecadado)));
printnl('-----------------------------------------------------------------------');*/



printnl('=======================================================================');

notice('Processo terminado!');