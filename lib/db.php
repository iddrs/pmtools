<?php

/**
 * Biblioteca de funções para interação com o banco de dados Postgre
 */

function connect(): PgSql\Connection {
    return pg_connect(load_config()['connection_string']);
}
