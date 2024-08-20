<?php

/**
 * Biblioteca de funções para entrada de dados.
 */

/**
 * Abstração para uma remessa.
 * 
 * Remessa é a representa ção de um mês e ano.
 */
class Remessa {
    public readonly string $remessa;
    public readonly int $ano;
    public readonly int $mes;
    public readonly string $mes2;
    public readonly DateTimeImmutable $inicioDoAno;
    public readonly DateTimeImmutable $fimDoAno;
    public readonly DateTimeImmutable $inicioDoMes; 
    public readonly DateTimeImmutable $fimDoMes;
    public readonly DateTimeImmutable $dataBase;
    
    
    public function __construct(int $ano, int $mes) {
        $this->validateAno($ano);
        $this->validateMes($mes);
        $this->ano = $ano;
        $this->mes = $mes;
        $this->mes2 = sprintf('%02s', $mes);
        $this->remessa = sprintf('%s%02s', $ano, $mes);
        $this->dataBase = new DateTimeImmutable("$ano-$mes-{$this->getLastDayOfMonth($ano, $mes)}");
        $this->fimDoMes = $this->dataBase;
        $this->inicioDoMes = new DateTimeImmutable("$ano-$mes-01");
        $this->inicioDoAno = new DateTimeImmutable("$ano-01-01");
        $this->fimDoAno = new DateTimeImmutable("$ano-12-31");
    }
    
    private function validateAno(int $ano): void {
        // nenhuma validação estabelecida ainda.
        return;
    }
    
    private function validateMes(int $mes): void {
        if ($mes < 1 || $mes > 12) {
            throw new Exception("O mês [$mes] tem valor inválido. Ele deve estar entre [1, 12].");
        }
    }
    
    private function getLastDayOfMonth($ano, $mes): int {
        $date = date_create_from_format('Y-m-d', "$ano-$mes-01");
        $date->modify('next month');
        $date->modify('-1 day');
        return (int) $date->format('d');
    }
}

/**
 * Pergunta a remessa ao usuário.
 * 
 * @return Remessa
 */
function read_remessa(): Remessa {
    printnl('[ Remessa ]');
    
    printnl('Digite o ano [AAAA]:');
    $ano = (int) trim(fgets(STDIN));
    
    printnl('Digite o mês [MM]:');
    $mes = (int) trim(fgets(STDIN));
    
    return new Remessa($ano, $mes);
}

/**
 * Carrega as configurações do arquivo .env
 * 
 * O arquivo .env deve ter as configurações no formato INI.
 * @return array
 */
function load_config(): array {
    return parse_ini_file('./.env', true);
}