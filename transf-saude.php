<?php

/**
 * Calcula o valor das transferências da saúde.
 * 
 * 
 */

require 'vendor/autoload.php';

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
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
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
printnl('-----------------------------------------------------------------------');



printnl('=======================================================================');

notice('Processo terminado!');