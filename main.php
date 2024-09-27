<?php

/**
 * Ponto de entrada da aplicação.
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('PM TOOLS');
printnl('Ferramentas para facilitar a vida do contador.');
printnl('=======================================================================');
printnl('');
printnl('');


function scanDirectoryForDescriptions($directory) {
    $descriptions = [];

    // Escaneia o diretório em busca de arquivos *.php
    $files = glob($directory . '/*.php');

    foreach ($files as $file) {
        // Lê o conteúdo do arquivo
        $lines = file($file);

        foreach ($lines as $line) {
            // Procura pela linha que contém '@description:'
            if (strpos($line, '@description:') !== false) {
                // Extrai o texto após '@description:' até o final da linha
                $description = trim(substr($line, strpos($line, '@description:') + 13));
                
                if(strlen($description) > 3){
                    // Salva o texto extraído em um array usando o nome do arquivo como chave
                    $descriptions[basename($file)] = $description;
                }
                break; // Para de procurar após encontrar a primeira ocorrência
            }
        }
    }

    return $descriptions;
}


$directory = dirname(__FILE__);
$descriptions = scanDirectoryForDescriptions($directory);


printnl('ESCOLHA UMA OPÇÃO:');
printnl('');
printnl('');

$i = 0;
foreach ($descriptions as $lbl) {
    printf("[ %02d ]\t%s".PHP_EOL, $i, $lbl);
    $i++;
}

printnl('');
printnl('Digite a opção desejada:');

$option = (int) trim(fgets(STDIN));

$scripts = array_keys($descriptions);

if(key_exists($option, $scripts)) {
    require_once $scripts[$option];
} else {
    printnl("Opção $option inválida!");
}