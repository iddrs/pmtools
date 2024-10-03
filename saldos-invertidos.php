<?php

/**
 * @description: Saldos invertidos.
 * 
 * Identifica contas contábeis, por entidade, com o saldo invertido em relação à 
 * natureza do saldo esperada pela conta contábil.
 * 
 */

// Contas contábeis com natureza de saldo híbrida.
$cc_hibridas = [
    '230000000000000',
    '237000000000000',
    '237100000000000',
    '237110000000000',
    '237110200000000',
    '237110300000000',
    '237110301000000',
    '237120000000000',
    '237120200000000',
    '237130000000000',
    '237130200000000',
    '237150200000000',
    '522139900000000',
    '821110105400000',
    '821110105500000',
    '821110105710000',
    '821110107010000',
    '821110107510000',
];

// Contas contábeis retificadoras (saldo invertido em relação ao normal).
$cc_invertidas = [
    '112900000000000',
    '112910000000000',
    '112910400000000',
    '112910401000000',
    '112910401050000',
    '112910401070000',
    '121119900000000',
    '121119904000000',
    '121119904050000',
    '121119904070000',
    '121119904090000',
    '121119905000000',
    '123800000000000',
    '123810000000000',
    '123810100000000',
    '123810101000000',
    '123810102000000',
    '123810103000000',
    '123810104000000',
    '123810105000000',
    '123810200000000',
    '123810201000000',
    '123810201010000',
    '227210303000000',
    '227210304000000',
    '227210305000000',
    '227210399000000',
    '227210402000000',
    '227210403000000',
    '227210404000000',
    '521120000000000',
    '521120100000000',
    '521120101000000',
    '521120200000000',
    '521120200000000',
    '522190000000000',
    '522190400000000',
    '522920103000000',
    '621300000000000',
    '621310000000000',
    '621310100000000',
    '621320000000000',
    '621390000000000',
    
];

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('SALDOS INVERTIDOS');
printnl('Identifica contas contábeis com possíveis saldos invertidos.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();

notice("Remessa selecionada: {$remessa->remessa}.");

// Identifica a entidade para processar
printnl('');
printnl('');
printnl('Entidade para processar');
printnl('');
printnl('[ 0 ] Prefeitura');
printnl('[ 1 ] RPPS');
printnl('[ 2 ] Câmara');
printnl('');
printnl('Selecione a entidade:');
$opcao = (int) trim(fgets(STDIN));
switch ($opcao) {
    case 0:
        $entidade = 'pm';
        break;
    case 1:
        $entidade = 'fpsm';
        break;
    case 2:
        $entidade = 'cm';
        break;
    default:
        error("Opção inválida: $opcao");
        exit();
}

notice("Entidade selecionada: $entidade.");

// Identifica se é para usar o balancete mensal ou o de encerramento.
if($remessa->mes == 12) {
    printnl('');
    printnl('');
    printnl('Usar balancete de encerramento? [S/N]');
    $encerramento = strtoupper(trim(fgets(STDIN)));
    switch ($encerramento) {
        case 'S':
            $tabela = 'bver_enc';
            break;
        case 'N':
            $tabela = 'bal_ver';
            break;
        default:
            error("Opção inválida: $encerramento");
            exit();
    }
} else {
    $tabela = 'bal_ver';
}
notice("Tabela selecionada: $tabela.");

// Monta a lista de contas contábeis
$sql = "SELECT distinct
    conta_contabil, sum(saldo_atual)::numeric as saldo_final
   FROM pad.$tabela
  WHERE
    entidade like $1
    AND remessa = $2
    group by conta_contabil
    order by conta_contabil asc;";
$result = pg_query_params(connect(), $sql, [$entidade, $remessa->remessa]);

// Processa as contas
$problemas = [];
while($row = pg_fetch_assoc($result)) {
    $cc = $row['conta_contabil'];
    $saldo_final = $row['saldo_final'];
    
    // Verifica se a conta é híbrida
    if(in_array($cc, $cc_hibridas)) {
        // Se a conta for híbrida, não tem porque testar o saldo.
        continue;
    }
    
    // Verifica se o saldo é igual zero
    if ($saldo_final == 0){
        // Se a conta não tem saldo final, não tem porque testar.
        continue;
    }
    
    // Identifica a natureza de saldo esperada
    $classe_cc = (int) $cc[0]; // Pega o primeiro dígito da conta contábil
    switch ($classe_cc) {
        case 1:
        case 3:
        case 5:
        case 7:
            $natureza_esperada = 'D';
            if($saldo_final > 0) {
                $natureza_saldo_final = 'D';
            }else {
                $natureza_saldo_final = 'C';
            }
            break;
        case 2:
        case 4:
        case 6:
        case 8:
            $natureza_esperada = 'C';
            if($saldo_final > 0) {
                $natureza_saldo_final = 'C';
            }else {
                $natureza_saldo_final = 'D';
            }
            break;
        default :
            printnl("Conta $cc com primeiro dígito inválido: $classe_cc");
            exit();
    }
    
    // Verifica se é conta retificadora
    if(in_array($cc, $cc_invertidas)) {
        switch ($natureza_esperada) {
            case 'D':
                $natureza_esperada = 'C';
                break;
            case 'C':
                $natureza_esperada = 'D';
                break;
        }
    }
    
    if($natureza_esperada !== $natureza_saldo_final) {
        $problemas[] = [
            'cc' => fmt_cc($cc),
            'saldo' => fmt_currency($saldo_final),
            'identificado' => $natureza_saldo_final,
            'esperado' => $natureza_esperada
        ];
    }
}



printnl('');
printnl('');

// Resumo
printnl('=======================================================================');
printnl('RESULTADO');
printnl('-----------------------------------------------------------------------');

if(sizeof($problemas) > 0) {
    printnl("Conta contábil\t\t\tSaldo\t\t\tNatureza Encontrada\tNatureza Esperada");
    foreach ($problemas as $item) {
        printnl("{$item['cc']}\t{$item['saldo']}\t\t{$item['identificado']}\t\t\t{$item['esperado']}");
    }
} else {
    notice('Nenhum problema encontrado.');
}

printnl('=======================================================================');

notice('Processo terminado!');