<?php

use PgSql\Result;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @description: Testa o relacionamento entre base legal e modalidades de contratação informada nos empenhos.
 * 
 * Os testes abrangem apenas os empenhos emitidos dentor do mês da remessa e do ano do empenho o ano da remessa.
 * 
 */

require 'vendor/autoload.php';

printnl('=======================================================================');
printnl('TESTE: BASE LEGAL x MODALIDADE DE CONTRATAÇÃO');
printnl('Testa a consistência dos empenhos quanto ao relacionamento entre a');
printnl('base legal e a modalidade de contratação.');
printnl('=======================================================================');
printnl('');
printnl('');

$cfg = load_config();

$remessa = read_remessa();
notice("Remessa selecionada: {$remessa->remessa}.");

// Preparando pasta de trabalho.
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
        ->setCreator($cfg['creator'])
        ->setTitle("Base Legal e Modalidades de Contratação")
        ->setSubject("Testa o relacionamento entre base legal de contratação e as modalidades de contratação.")
        ->setDescription("Testa o relacionamento entre base legal de contratação e as modalidades de contratação para o município de {$cfg['municipio']} dos empenhos de {$remessa->ano} emitidos entre {$remessa->inicioDoMes->format('d/m/Y')} e {$remessa->dataBase->format('d/m/Y')}.")
        ->setKeywords('liciatação compras teste')
        ->setCategory('Table');

function build_sheet(Spreadsheet $spreadsheet, string $sheet_name, string $data_explain, Result $result): void {
    // Cria a planilha
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle($sheet_name);
    $sheet->getCell('A1')->setValue($data_explain);
    
    // Salva os dados
    $headers = array_keys(pg_fetch_assoc($result, 0));
    $data = array_merge([$headers], pg_fetch_all($result, PGSQL_NUM));
    $sheet->fromArray($data, null, 'A3');
    
    // Formatando planilha
    $last_line = pg_num_rows($result) + 3;
    $sheet->getStyle("D3:D$last_line")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

    $sheet->getStyle('A3:I3')->getFont()->setBold(true);
    $sheet->getStyle('A1')->getFont()->setItalic(true);

    $borderOptions = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '00000000']
            ]
        ]
    ];
    $sheet->getStyle("A3:I$last_line")->applyFromArray($borderOptions);
    
    
    $sheet->getColumnDimension('E')->setWidth(100);
    
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setAutoSize(true);
    $sheet->getColumnDimension('F')->setAutoSize(true);
    $sheet->getColumnDimension('G')->setAutoSize(true);
    $sheet->getColumnDimension('H')->setAutoSize(true);
    $sheet->getColumnDimension('I')->setAutoSize(true);
    
    $sheet->getStyle("E4:E$last_line")->getAlignment()->setWrapText(true);

    // Configurando a impressão
    $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
    $page_header = '&L&16&B'.$data_explain;
    $page_footer = '&LEmitido em &D &T&RPágina &P de &N';
    $sheet->getHeaderFooter()
            ->setOddHeader($page_header)
            ->setEvenHeader($page_header)
            ->setOddFooter($page_footer)
            ->setEvenFooter($page_footer);
    $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(3, 3);
}



// INÍCIO DOS TESTES ==========================================================================================================

// Testa 00 NSA != NSA
notice('Testando 00 NSA != NSA');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 0
	AND FORMA_CONTRATACAO NOT IN ('NSA')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'Não se aplica', 'Compara a base legal Não se Aplica com as modalidades permitidas (Não se Aplica).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}

// ------------------------------------------------------------------------------------------------------------------------------------------------

// Testa 01 L8666 != [CNC, CNS, CNV, DPV, PRD, PRI, TMP]
notice('Testando 01 L8666 != [CNC, CNS, CNV, DPV, PRD, PRI, TMP]');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 1
	AND FORMA_CONTRATACAO NOT IN ('CNC', 'CNV', 'CNS', 'DPV', 'PRD', 'PRI', 'TMP')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'L8666', 'Compara a base legal Lei nº 8.666/93 com as modalidades permitidas (Concorrência, Concurso, Convite, Dispensa por pequeno valor, Dispensa (exceto pequeno valor), Inexigibilidade, Tomada de Preços).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}

// ------------------------------------------------------------------------------------------------------------------------------------------------


// Testa 02 L12462 != [RDC, RDE, RIN]
notice('Testando 02 L12462 != [RDC, RDE, RIN]');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 2
	AND FORMA_CONTRATACAO NOT IN ('RDC', 'RDE', 'RIN')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'L12462', 'Compara a base legal Lei nº 12.462/11 RDC com as modalidades permitidas (Regime Diferenciado de Contratação (Presencial), Regime Diferenciado de Contratação (Eletrônico), Regras internacionais).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}

// ------------------------------------------------------------------------------------------------------------------------------------------------


// Testa 03 L13019 != NSA
notice('Testando 03 L13019 != NSA');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 3
	AND FORMA_CONTRATACAO NOT IN ('NSA')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'L13019', 'Compara a base legal Lei nº 13.019/2014 Parcerias com as modalidades permitidas (Não se Aplica).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}

// ------------------------------------------------------------------------------------------------------------------------------------------------


// Testa 04 L9637!= NSA
notice('Testando 04 L9637 != NSA');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 4
	AND FORMA_CONTRATACAO NOT IN ('NSA')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'L9637', 'Compara a base legal Lei nº 9.637/98 O.S. com as modalidades permitidas (Não se Aplica).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}

// ------------------------------------------------------------------------------------------------------------------------------------------------


// Testa 05 L9790 != NSA
notice('Testando 05 L9790 != NSA');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 5
	AND FORMA_CONTRATACAO NOT IN ('NSA')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'L9790', 'Compara a base legal Lei nº 9.790/99 O.S.C.I.P. com as modalidades permitidas (Não se Aplica).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}

// ------------------------------------------------------------------------------------------------------------------------------------------------


// Testa 06 Outra != [CHP, CPC, CPP, RPO, NSA]
notice('Testando 06 Outra != [CHP, CPC, CPP, RPO, NSA]');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 6
	AND FORMA_CONTRATACAO NOT IN ('CHP', 'CPC', 'CPP', 'RPO', 'NSA')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'Outra', 'Compara a base legal Outra com as modalidades permitidas (Chamamento público, Credenciamento, Chamada Pública PNAE, Adesão à Ata de Registro de Preços, Não se aplica).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}


// ------------------------------------------------------------------------------------------------------------------------------------------------


// Testa 07 L10520 != [PRE, PRP]
notice('Testando 07 L10520 != [PRE, PRP]');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 7
	AND FORMA_CONTRATACAO NOT IN ('PRE', 'PRP')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'L10520', 'Compara a base legal Lei nº 10.520 Pregão com as modalidades permitidas (Pregão Eletrônico, Pregão Presencial).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}

// ------------------------------------------------------------------------------------------------------------------------------------------------


// Testa 08 L14133 != [DPV, PRD, PRI, PDE, CCP, CCE, PCE, PCP]
// @TODO BUG: Registros Removidos: Propriedades da planilha de parte de /xl/worksheets/sheet5.xml
notice('Testando 08 L14133 != [DPV, PRD, PRI, PDE, CCP, CCE, PCE, PCP]');
$sql = "SELECT
	ENTIDADE AS \"Entidade\",
	NR_EMPENHO AS \"Empenho\",
	to_char(DATA_EMPENHO::timestamp with time zone, 'DD/MM/YYYY'::text) AS \"Data\",
	VALOR_EMPENHO::numeric AS \"Valor\",
	HISTORICO_EMPENHO AS \"Histórico\",
        BASE_LEGAL_CONTRATACAO AS \"Cód. Base Legal\",
        CASE BASE_LEGAL_CONTRATACAO
            WHEN 0 THEN 'Não se aplica'::text
            WHEN 1 THEN 'Lei nº 8.666/93'::text
            WHEN 2 THEN 'Lei nº 12.462/2011'::text
            WHEN 3 THEN 'Lei nº 13.019/2014'::text
            WHEN 4 THEN 'Lei nº 9.637/98'::text
            WHEN 5 THEN 'Lei nº 9.790/99'::text
            WHEN 6 THEN 'Outra'::text
            WHEN 7 THEN 'Lei nº 10.520'::text
            WHEN 8 THEN 'Lei nº 14.133/21'::text
            ELSE NULL::text
        END AS \"Base Legal\",
        FORMA_CONTRATACAO AS \"Cód. Forma de Contratação\",
	CASE FORMA_CONTRATACAO
            WHEN 'CHP' THEN 'Chamamento público'::text
            WHEN 'CNC' THEN 'Concorrência'::text
            WHEN 'CNS' THEN 'Concurso'::text
            WHEN 'CNV' THEN 'Convite'::text
            WHEN 'CPC' THEN 'Credenciamento'::text
            WHEN 'CPP' THEN 'Chamada Pública PNAE'::text
            WHEN 'DPV' THEN 'Dispensa por pequeno valor'::text
            WHEN 'PRD' THEN 'Dispensa (exceto pequeno valor)'::text
            WHEN 'PRE' THEN 'Pregão Eletrônico'::text
            WHEN 'PRI' THEN 'Inexigibilidade'::text
            WHEN 'PRP' THEN 'Pregão Presencial'::text
            WHEN 'RDC' THEN 'Regime Diferenciado de Contratação (Presencial)'::text
            WHEN 'RDE' THEN 'Regime Diferenciado de Contratação (Eletrônico)'::text
            WHEN 'RIN' THEN 'Regras internacionais'::text
            WHEN 'RPO' THEN 'Adesão à Ata de Registro de Preços'::text
            WHEN 'TMP' THEN 'Tomada de Preços'::text
            WHEN 'PDE' THEN 'Dispensa Eletrônica'::text
            WHEN 'CCP' THEN 'Concorrência Lei 14.133 Presencial'::text
            WHEN 'CCE' THEN 'Concorrência Lei 14.133 Eletrônica'::text
            WHEN 'PCE' THEN 'Pregão Lei 14.133 Eletrônico'::text
            WHEN 'PCP' THEN 'Pregão Lei 14.133 Presencial'::text
            WHEN 'NSA' THEN 'Não se aplica'::text
        END AS \"Forma de Contratação\"
FROM
	PAD.EMPENHO
WHERE
	REMESSA = $1
	AND ANO_EMPENHO = $2
        AND DATA_EMPENHO BETWEEN $3 AND $4
        AND VALOR_EMPENHO::numeric > 0.0
	AND BASE_LEGAL_CONTRATACAO = 8
	AND FORMA_CONTRATACAO NOT IN ('DPV', 'PRD', 'PRI', 'PDE', 'CCP', 'CCE', 'PCE', 'PCP')";
$result = pg_query_params(connect(), $sql, [$remessa->remessa, $remessa->ano, $remessa->inicioDoMes->format('Y-m-d'), $remessa->dataBase->format('Y-m-d')]);
if($result === false) {
    throw new Exception("Falha ao executar a query [$sql].". PHP_EOL."Postgres Error:". PHP_EOL.pg_last_error(connect()));
}
$num_rows = pg_num_rows($result);
if($num_rows > 0) {
    notice("Encontrados $num_rows empenhos com possíveis problemas.");
    build_sheet($spreadsheet, 'L14133', 'Compara a base legal Lei nº 14.133/21 com as modalidades permitidas (Dispensa por pequeno valor, Dispensa (exceto pequeno valor), Inexigibilidade, Dispensa Eletrônica, Concorrência Lei 14.133 Presencial, Concorrência Lei 14.133 Eletrônica, Pregão Lei 14.133 Eletrônico, Pregão Lei 14.133 Presencial).', $result);
} else {
    alert("Nenhum registro retornado. Continuando próximo teste...");
}

// FIM DOS TESTES ==========================================================================================================



// Preparando planilha de metadados
$meta_sheet = $spreadsheet->createSheet();
$meta_sheet->setTitle('Meta');
$meta_sheet->setCellValue('A1', 'gerado_em');
$meta_sheet->setCellValue('A2', date('d/m/Y H:i:s'));

// Gravando a planilha
$spreadsheet->removeSheetByIndex(0);
$writer = new Xlsx($spreadsheet);
$output_file = $cfg['desktop_path'].'teste-base_legal-modalidade_contratacao.xlsx';
$writer->save($output_file);

notice("Dados gravados em $output_file");

notice('Processo terminado!');