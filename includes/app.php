<?php
require 'funciones.php';
require 'config/database.php';
require __DIR__.'/../vendor/autoload.php';

use Model\ActiveRecord;

//Conectando a la BD
$db=conectarDB();

//Instanciando el método de conectividad a BDs
ActiveRecord::setDB($db);