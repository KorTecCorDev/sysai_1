<?php

function conectarDB() : mysqli {//Indica que retorna una conexión de Mysqli
    $db= new mysqli('localhost','u612374195_sysai','1B6$M[wv[nA','u612374195_sysai');

    if (!$db) {
        echo 'ERROR no se pudo conectar';
        exit;
    }
    return $db;
}