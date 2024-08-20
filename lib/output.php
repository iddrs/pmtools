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