<?php

/**
 * Biblioteca com funções para saída de dados.
 */

/**
 * Imprime uma mensagem e acrescenta uma nova linha ao final.
 * 
 * @param string $msg
 * @return void
 */
function printnl(string $msg): void {
    echo $msg, PHP_EOL;
}

/**
 * Mostra uma mensagem do tipo NOTICE.
 * 
 * @param string $msg
 * @return void
 */
function notice(string $msg): void {
    printnl(sprintf('[ NOTICE ] %s', $msg));
}

/**
 * Mostra uma mensagem do tipo ALERT.
 * 
 * @param string $msg
 * @return void
 */
function alert(string $msg): void {
    printnl(sprintf('[ ALERT  ] %s', $msg));
}

/**
 * Mostra uma mensagem do tipo ERROR.
 * 
 * @param string $msg
 * @return void
 */
function error(string $msg): void {
    printnl(sprintf('[ ERROR  ] %s', $msg));
}

/**
 * Formata um númrero para moeda.
 * 
 * @param mixed $value
 * @param int $decimals
 * @return string
 */
function fmt_currency($value, int $decimals = 2): string {
    return number_format($value, $decimals, ',', '.');
}

/**
 * Formata a conta contábil.
 * 
 * @param string $cc
 * @return string
 */
function fmt_cc(string $cc): string {
    $n1 = $cc[0];
    $n2 = $cc[1];
    $n3 = $cc[2];
    $n4 = $cc[3];
    $n5 = $cc[4];
    $n6 = $cc[5].$cc[6];
    $n7 = $cc[7].$cc[8];
    $n8 = $cc[9].$cc[10];
    $n9 = $cc[11].$cc[12];
    $n10 = $cc[13].$cc[14];
    return "$n1.$n2.$n3.$n4.$n5.$n6.$n7.$n8.$n9.$n10";
}