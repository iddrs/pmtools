<?php

/**
 * @description: Receita da Dívida Ativa
 * 
 * Calcula o valor das receitas de dívida ativa.
 * 
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('RECEITA DA DÍVIDA ATIVA');
printnl('Valor acumulado no ano da receita de dívida ativa.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();

notice("Remessa selecionada: {$remessa->remessa}.");

// Calcula a dívida tributária
$sql = "SELECT
    sum(receita_realizada)::numeric AS arrecadado
   FROM pad.bal_rec
  WHERE tipo_nivel_receita ~~ 'A'::text AND tipo_receita in (3, 4) AND natureza_receita::text ~~ '11%'::text AND entidade::text ~~ 'pm'::text
  AND remessa = $1;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$tributaria = pg_fetch_assoc($result, 0)['arrecadado'];


// Calcula a dívida não tributária
$sql = "SELECT
    sum(receita_realizada)::numeric AS arrecadado
   FROM pad.bal_rec
  WHERE tipo_nivel_receita ~~ 'A'::text AND tipo_receita in (3, 4) AND natureza_receita::text !~~ '11%'::text AND entidade::text ~~ 'pm'::text
  AND remessa = $1;";
$result = pg_query_params(connect(), $sql, [$remessa->remessa]);
$nao_tributaria = pg_fetch_assoc($result, 0)['arrecadado'];

printnl('');
printnl('');

// Resumo
printnl('=======================================================================');
printnl('RESULTADO');
printnl('-----------------------------------------------------------------------');
printnl("Dívida\t\t\tArrecadado");
printnl(sprintf("%s\t\t%s", 'Tributária', fmt_currency($tributaria)));
printnl(sprintf("%s\t\t%s", 'Não Tributária', fmt_currency($nao_tributaria)));
printnl(sprintf("%s\t\t\t%s", 'Total', fmt_currency($tributaria + $nao_tributaria)));
printnl('-----------------------------------------------------------------------');



printnl('=======================================================================');

notice('Processo terminado!');