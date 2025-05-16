<?php

function conectarDB() : mysqli {//Indica que retorna una conexión de Mysqli
    $db= new mysqli('localhost','root','root','u612374195_sysai');

    if (!$db) {
        echo 'ERROR no se pudo conectar';
        exit;
    }
    return $db;
}