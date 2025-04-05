<?php

namespace Model;

class ActiveRecord
{
    //Variable estática para conectar a la BD
    protected static $db;
    protected static $columnasDB = [];
    protected static $tabla = '';

    //Errores

    protected static $errores = [];
    protected static $aux = [];


    //Definimos la conexión a la base de datos
    public static function setDB($database)
    {
        self::$db = $database;
    }

    /**
     * Guarda el registro en la base de datos. Si el registro ya existe,
     * se actualiza, de lo contrario se crea.
     *
     * @return void
     */
    public function guardar()
    {
        if (!is_null($this->id)) {
            //Actualizar
            $this->actualizar();
        } else {
            //Crear
            $this->crear();
        }
    }
    public function guardarsinRedireccion()
    {
        if (!is_null($this->id)) {
            //Actualizar
            $resultado = $this->actualizarsinRedireccion();
            if (isset($resultado)) {
                return $resultado;
            }
        } else {
            //Crear
            $resultado = $this->crearsinRedireccion();
            if (isset($resultado)) {
                return $resultado;
            }
        }
    }


    public function crear(): bool
    /**
     * Crea un nuevo registro en la base de datos. Los datos son sanitizados 
     * antes de la inserción. Si la inserción es exitosa, el usuario es 
     * redirigido a la página de administración correspondiente.
     *
     * @return bool Retorna true si el registro fue insertado exitosamente, 
     * false en caso contrario.
     */

    {

        //Sanitizamos los datos
        //LLamamos a un método dentro de otro método
        $atributos = $this->sanitizarAtributos();
        // Convertimos todos los strings a mayúsculas
        $atributos = $this->convertirAMayusculas($atributos);
        $stringcolumnas = join(', ', array_keys($atributos));
        $stringdatos = join("', '", array_values($atributos));

        //Consulta a la base de datos.
        $query = "INSERT INTO " . static::$tabla . " (";
        $query .= $stringcolumnas;
        $query .= ") VALUES ('";
        $query .= $stringdatos;
        $query .= "');";

        //Insertamos a la base de datos.
        $resultado = self::$db->query($query);

        //Mensaje de exito o error
        if ($resultado) {
            //Redireccionamiento del usuario en una inserción correcta en la base de datos
            $url = "Location: /" . static::$tabla . "/admin?resultado=1";
            header($url);
            exit();
        }
        return $resultado;
    }
    public function crearsinRedireccion(): bool
    {

        //Sanitizamos los datos
        //LLamamos a un método dentro de otro método
        $atributos = $this->sanitizarAtributos();
        // Convertimos todos los strings a mayúsculas
        $atributos = $this->convertirAMayusculas($atributos);

        $stringcolumnas = join(', ', array_keys($atributos));
        $stringdatos = join("', '", array_values($atributos));

        //Consulta a la base de datos.
        $query = "INSERT INTO " . static::$tabla . " (";
        $query .= $stringcolumnas;
        $query .= ") VALUES ('";
        $query .= $stringdatos;
        $query .= "');";

        //Insertamos a la base de datos.
        $resultado = self::$db->query($query);
        return $resultado;
    }

    /**
     * Actualiza un registro existente en la base de datos. Los datos son sanitizados 
     * antes de la actualización. Si la actualización es exitosa, el usuario es 
     * redirigido a la página de administración correspondiente.
     *
     * @return void
     */
    public function actualizar()
    {
        //Sanitizamos los datos
        $atributos = $this->sanitizarAtributos();
        // Convertimos todos los strings a mayúsculas
        $atributos = $this->convertirAMayusculas($atributos);
        $valores = [];
        foreach ($atributos as $key => $value) {
            $valores[] = "$key='$value'";
        }
        $query = "UPDATE " . static::$tabla . " SET ";
        $query .= join(', ', $valores);
        $query .= " WHERE id='";
        $query .= self::$db->escape_string($this->id) . "'";
        $query .= " LIMIT 1;";

        $resultado = self::$db->query($query);

        if ($resultado) {
            //Redireccionamiento del usuario en una inserción correcta en la base de datos
            header("Location: /" . static::$tabla . "/admin?resultado=2");
            exit();
        }
    }
    public function actualizarsinRedireccion()
    {
        //Sanitizamos los datos
        $atributos = $this->sanitizarAtributos();
        // Convertimos todos los strings a mayúsculas
        $atributos = $this->convertirAMayusculas($atributos);
        $valores = [];
        foreach ($atributos as $key => $value) {
            $valores[] = "$key='$value'";
        }
        $query = "UPDATE " . static::$tabla . " SET ";
        $query .= join(', ', $valores);
        $query .= " WHERE id='";
        $query .= self::$db->escape_string($this->id) . "'";
        $query .= " LIMIT 1;";
        $resultado = self::$db->query($query);
        return $resultado;
    }
    //Funciones

    //Eliminar un registro
    public function eliminarsinRedireccion()
    {
        //Eliminar el archivo
        $query = "DELETE FROM " . static::$tabla . " WHERE id=" . self::$db->escape_string($this->id) . " LIMIT 1;";
        $resultado = self::$db->query($query);

        if ($resultado) {
            $this->borrarImagen();
        }
    }
    /**
     * Elimina un registro de la base de datos. 
     * Si el registro es eliminado exitosamente, se borra la imagen asociada
     * y se redirige al usuario a la página de administración correspondiente.
     *
     * @return void
     */
    public function eliminar()
    {
        //Eliminar el archivo
        $query = "DELETE FROM " . static::$tabla . " WHERE id=" . self::$db->escape_string($this->id) . " LIMIT 1;";
        $resultado = self::$db->query($query);

        if ($resultado) {
            $this->borrarImagen();
            //Comentado por haber colocado el redireccionamiento en la misma función del controller
            //header("Location: /".static::$tabla."/admin?resultado=3");
        }
    }

    //Esta función se encarga de iterar los elementos de columnasDB.
    //Identifica y une los atributos de la BD.
    public function atributos()
    {
        $atributos = [];
        foreach (static::$columnasDB as $columna) {
            //Este if nos permite ignorar el ID
            if ($columna === 'id') continue;
            $atributos[$columna] = $this->$columna;
        }
        return $atributos;
    }


    /**
     * Sanitiza los atributos de la base de datos, escapando strings
     * y convirtiendo a números enteros los valores que lo requieran.
     * Se utiliza para preparar los datos antes de insertarlos en la base de datos.
     *
     * @return array con los atributos sanitizados
     */
    public function sanitizarAtributos()
    {
        $atributos = $this->atributos();
        $sanitizado = [];

        foreach ($atributos as $key => $value) {
            $sanitizado[$key] = self::$db->escape_string($value);
        }

        return $sanitizado;
    }

    // Subida de archivos
    public function setImagen($imagen)
    {
        //Eliminando el archivo antes de asignarlo
        if (!is_null($this->id)) {
            //Comprobando si existe el archivo
            $this->borrarImagen();
        }
        //Asignar al atributo de imagen el nombre de la imagen
        if ($imagen) {
            $this->imagen = $imagen;
        }
    }

    //Eliminar el archivo de imagen
    public function borrarImagen()
    {
        //Comprobando si existe el archivo
        $existeArchivo = file_exists(CARPETA_IMAGENES . $this->imagen);
        if ($existeArchivo) {
            unlink(CARPETA_IMAGENES . $this->imagen);
        }
    }


    //Validación
    public static function getErrores()
    {
        return static::$errores;
    }
    /**
     * Valida los atributos de la clase actual según las reglas definidas
     * en la clase.
     *
     * @return array con los errores encontrados, vacío si no hay errores.
     */
    public function validar()
    {
        static::$errores = [];
        return static::$errores;
    }
    //Lista todas las propiedades
    public static function all()
    {
        $query = "SELECT * FROM " . static::$tabla . ";";
        $resultado = self::consultarSql($query);
        return $resultado;
    }

    //Obtiene determinado número de registros
    public static function get($cantidad)
    {
        $query = "SELECT * FROM " . static::$tabla . " LIMIT " . $cantidad . ";";
        $resultado = self::consultarSql($query);
        return $resultado;
    }
    //Busca un registro por su id
    public static function find($id)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE id=$id";
        $resultado = self::consultarSql($query);
        return (array_shift($resultado));
    }
    public static function findmany($id)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE id=$id";
        $resultado = self::consultarSql($query);
        return ($resultado);
    }
    //Todos los registros de una tabla según un atributo y dato
    public static function findxatributo(string $atributo, $valor)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE " . $atributo . "=" . $valor;
        $resultado = self::consultarSql($query);
        return $resultado;
    }
    public static function findlast()
    {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY id DESC LIMIT 1;";
        $resultado = self::consultarSql($query);
        return (array_shift($resultado));
    }
    /**
     * Encuentra registros en la tabla actual basados en un atributo especificado y su valor.
     *
     * @param string $atributoid El nombre del atributo (columna) por el cual filtrar.
     * @param int $id El valor del atributo a coincidir.
     * @return array El conjunto de resultados de la consulta.
     */
    public static function findwithmoretables(string $atributoid, int $id)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE " . $atributoid . "=" . $id;
        $resultado = self::consultarSql($query);
        return $resultado;
    }
    //Editar Después
    //     public static function findporRangoFechas(string $fechaInicio, string $fechaFin)
    // {
    //     // Construir la consulta SQL para filtrar por rango de fechas
    //     $query = "SELECT * FROM " . static::$tabla . " 
    //               WHERE " . $fechaCampo . " BETWEEN '" . $fechaInicio . "' AND '" . $fechaFin . "'";

    //     // Ejecutar la consulta
    //     $resultado = self::consultarSql($query);

    //     return $resultado;
    // }
    //Editar después
    public static function findwithparameters(string $prmtuno, string $iduno, string $prmtdos, string $iddos)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE " . $prmtuno . " = " . $iduno . " AND " . $prmtdos . " = " . $iddos;
        $resultado = self::consultarSql($query);
        return $resultado;
    }
    public static function findwithtableforanea(string $id)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE programa_id =" . $id;
        $resultado = self::consultarSql($query);
        return $resultado;
    }

    /**
     * Recupera todos los IDs de la tabla de la base de datos asociada con el modelo ActiveRecord.
     *
     * Este método construye una consulta SQL para seleccionar todos los IDs de la tabla definida por la propiedad estática `$tabla`.
     * Luego ejecuta la consulta utilizando el método `consultarSql` y devuelve el resultado.
     *
     * @return array El resultado de la consulta SQL, típicamente un array de IDs.
     */
    public static function allid()
    {
        $query = "SELECT id FROM " . static::$tabla;
        $resultado = self::consultarSql($query);
        return $resultado;
    }
    public static function allidspecial()
    {
        $query = "SELECT id, nombre FROM " . static::$tabla;
        $result = self::$db->query($query);
        $datos = [];
        while ($row = $result->fetch_assoc()) {
            $datos[] = [
                'id' => (int)$row['id'],
                'nombre' => $row['nombre']
            ];
        }
        $result->free();
        return $datos;
    }
    public static function ejecutarSql($query)
    {
        $resultado = self::$db->query($query);
        return $resultado;
    }
    public static function consultarSql($query): array
    {
        //Consultar la base de datos
        $resultado = self::$db->query($query);

        //Iterar los resultados
        $array = [];
        while ($registro = $resultado->fetch_assoc()) {
            $array[] = static::crearObjeto($registro);
        }
        //Liberar la memoria
        $resultado->free();
        //Retornar los resultados en un array indexado
        return $array;
    }
    public static function consultarSqldvolveruno($query)
    {
        //Consultar la base de datos
        $resultado = self::$db->query($query);

        //Iterar los resultados
        $array = [];
        while ($registro = $resultado->fetch_assoc()) {
            $array[] = static::crearObjeto($registro);
        }
        //Liberar la memoria
        $resultado->free();
        //Retornar los resultados como un array asociativo(objeto)
        return array_shift($array);
    }

    protected static function crearObjeto($registro): object
    {
        $objeto = new static;
        foreach ($registro as $key => $value) {
            if (property_exists($objeto, $key)) {
                $objeto->$key = $value;
            }
        }
        return $objeto;
    }

    /**
     * Sincroniza las propiedades del objeto actual con los argumentos proporcionados.
     *
     * Este método itera sobre el array asociativo proporcionado ($args) y asigna
     * los valores a las propiedades correspondientes del objeto actual si la propiedad
     * existe y el valor no es nulo.
     *
     * @param array $args Un array asociativo donde las claves son nombres de propiedades y los valores son los valores a asignar.
     */
    public function sincronizar($args = [])
    {
        foreach ($args as $key => $value) {
            if (property_exists($this, $key) && !is_null($value)) {
                $this->$key = $value;
            }
        }
    }
    public static function devolverIdforaneo(int $id, string $tb)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE " . $tb . "_id=" . $id;
        $resultados = self::consultarSql($query);
        if (count($resultados) != 0) {
            $arreglo = [];
            foreach ($resultados as $resultado) {
                $arreglo[] = $resultado->id;
            }
        }
        return $arreglo;
    }
    public static function devolverTodoforaneo(int $id, string $tb)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE " . $tb . "_id=" . $id;
        $resultados = self::consultarSql($query);
        return $resultados;
    }


    //Funciones para los reportes
    public static function combinarCeldasRepetidas($sheet, array $columnas, int $rowstart = 5)
    {
        $endRow = $sheet->getHighestRow(); // Última fila con datos
        foreach ($columnas as $col) {
            $cont = 0; // Contador de celdas con datos repetidos
            $currentValue = ''; // Valor actual de la celda a comparar
            $startRow = $rowstart; // Fila de inicio del rango de combinación

            for ($i = $rowstart; $i <= $endRow; $i++) {
                $cellValue = $sheet->getCell("{$col}{$i}")->getValue();

                if ($cellValue === $currentValue && $cellValue !== null && $cellValue !== '') {
                    // Si el valor actual es igual al anterior y no está vacío, continúa el rango de combinación
                    $cont++;
                } else {
                    // Si hay celdas para combinar, realiza la combinación
                    if ($cont > 0) {
                        $sheet->mergeCells("{$col}{$startRow}:{$col}" . ($i - 1));
                    }
                    // Actualiza el valor actual y el inicio del siguiente rango
                    $currentValue = $cellValue;
                    $startRow = $i;
                    $cont = 0;
                }
            }
            // Combina el último rango si es necesario
            if ($cont > 0) {
                $sheet->mergeCells("{$col}{$startRow}:{$col}{$endRow}");
            }
            // Aplicar estilo de alineación vertical centrada
            $sheet->getStyle("{$col}1:{$col}{$endRow}")
                ->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        }
    }

    /**
     * Insertar celdas con datos de reporte POA.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Hoja de cálculo
     * @param array $data Array con los datos a insertar
     *
     * @return void
     */
    //Modificar parámetros
    private static function insertarSumaMontos($sheet, $row, $sumaMontos, $tipoCambioDolar, $tipoCambioEuro)
    {
        $sheet->setCellValue("G$row", $sumaMontos);
        $sheet->setCellValue("H$row", round($sumaMontos / $tipoCambioDolar, 2));
        $sheet->setCellValue("I$row", round($sumaMontos / $tipoCambioEuro, 2));
    }


    public static function insertarCeldasReportePOA($sheet, array $data, object $dolar, object $euro, int $filaini = 5, array $rendiciones = null, array $fuentes = null, array $cols = null): int
    {
        $tipoCambioDolar = floatval($dolar->tipo_cambio);
        $tipoCambioEuro = floatval($euro->tipo_cambio);

        // Insertar datos sin encabezados
        $row = $filaini; // Inicia desde la fila indicada en el parámetro, por defecto 5
        $actividadactual = 0;
        $sumaMontos = 0;
        //recopilamos los actividad_id de todos los rubros que existan
        $mismactividad = array_unique(array_column($data, 'actividad_id'));
        //Contador de actividades
        $contadoractividades = count($mismactividad);
        //Creamos 2 foreach
        //En caso de una sola actividad
        if ($contadoractividades === 1) {
            foreach ($data as $dato) {
                $actividad_codigo = $dato->actividad_codigo;
                $producto_codigo = $dato->producto_codigo;
                $actividad = $dato->actividad_id;
                $idtiporubro = $dato->id_tipo_rubro;
                $monto = $dato->monto;

                // Insertar datos
                $codigoconproducto = $producto_codigo . " " . $dato->producto;
                $codigoconactividad = $actividad_codigo . " " . $dato->actividad;
                $sheet->setCellValue("A$row", $codigoconproducto);
                $sheet->setCellValue("B$row", $codigoconactividad);

                if ($idtiporubro == 1) { // Bienes
                    $sheet->setCellValue("C$row", $dato->rubros);
                    $sheet->setCellValue("D$row", $monto);
                } else { // Servicios
                    $sheet->setCellValue("E$row", $dato->rubros);
                    $sheet->setCellValue("F$row", $monto);
                }

                // Sumar el monto actual
                $sumaMontos += $monto;

                // Formatear fila
                $sheet->getRowDimension($row)->setRowHeight(53);
                //Cambiando posición de cambio de actividad:
                $actividadactual = $actividad;
                //Fin de cambio de posición
                $row++;
            }
        } else {
            foreach ($data as $dato) {
                $actividad_codigo = $dato->actividad_codigo;
                $producto_codigo = $dato->producto_codigo;
                $actividad = $dato->actividad_id;
                $idtiporubro = $dato->id_tipo_rubro;
                $monto = $dato->monto;
                //Si existen más de una actividad
                //Si hay cambio de actividad ...
                if ($actividadactual != 0 && $actividadactual !== $actividad) {
                    //En caso solo exista un registro de suma de rendiciones
                    self::insertarSumaMontos($sheet, $row - 1, $sumaMontos, $tipoCambioDolar, $tipoCambioEuro);
                    if ($rendiciones && $fuentes) {
                        foreach ($rendiciones as $rendicio) {
                            if ($rendicio->actividad_id == $actividadactual) {
                                $rendi[] = $rendicio;
                            }
                        }
                        self::insertarRendicionesFuente($sheet, $cols, $row - 1, $fuentes, $rendi, $tipoCambioDolar, $tipoCambioEuro);
                    }
                    $sumaMontos = 0; // Reiniciar la suma
                }

                // Insertar datos
                $codigoconproducto = $producto_codigo . " " . $dato->producto;
                $codigoconactividad = $actividad_codigo . " " . $dato->actividad;
                $sheet->setCellValue("A$row", $codigoconproducto);
                $sheet->setCellValue("B$row", $codigoconactividad);

                if ($idtiporubro == 1) { // Bienes
                    $sheet->setCellValue("C$row", $dato->rubros);
                    $sheet->setCellValue("D$row", $monto);
                } else { // Servicios
                    $sheet->setCellValue("E$row", $dato->rubros);
                    $sheet->setCellValue("F$row", $monto);
                }

                // Sumar el monto actual
                $sumaMontos += $monto;

                // Formatear fila
                $sheet->getRowDimension($row)->setRowHeight(53);
                //Cambiando posición de cambio de actividad:
                $actividadactual = $actividad;
                //Fin de cambio de posición
                $row++;
            }
        }

        // Insertar la suma de rubros de la última actividad
        if ($sumaMontos > 0) {
            self::insertarSumaMontos($sheet, $row - 1, $sumaMontos, $tipoCambioDolar, $tipoCambioEuro);
        }
        //Insertamos la última rendicion en caso exista
        if ($rendiciones && $sumaMontos > 0) {
            //Buscamos la última rendición
            $lastrendicion[] = end($rendiciones);
            self::insertarRendicionesFuente($sheet, $cols, $row - 1, $fuentes, $lastrendicion, $tipoCambioDolar, $tipoCambioEuro);
        }
        if ($contadoractividades === 1 && $rendiciones) {
            self::insertarRendicionesFuente($sheet, $cols, $row - 1, $fuentes, $rendiciones, $tipoCambioDolar, $tipoCambioEuro);
        }

        // Aplicar ajuste de texto a todas las celdas
        $sheet->getStyle("A5:I$row")->getAlignment()->setWrapText(true);

        // Retornar el número de la última fila ingresada
        return $row;
    }




    public static function insertarDatosDesdeArray($sheet, int $filaini = 3, array $data): int
    {
        // Definir las columnas según los encabezados creados
        $columnas = [
            'FECHA',
            'CODIGO',
            'DESCRIPCION',
            'TIPO/COMPROBANTE',
            'MONTO',
            'FUENTE_FINANCIAMIENTO',
            'FECHA_COMPROBANTE',
            'RUC',
            'PERSONA',
            'SERIE',
            'NUMERO',
            'DETALLE',
            'MONTO/COMPROBANTE'
        ];

        // Calcular la primera y última columna (Ejemplo: A -> M para 13 columnas)
        $startColumn = 'A';
        $endColumn = chr(ord($startColumn) + count($columnas) - 1);

        // Fila de inicio
        $row = $filaini;

        // Iterar sobre cada objeto en el array $data
        foreach ($data as $obj) {
            $colIndex = $startColumn;
            $values = array_values((array) $obj); // Convertir objeto a array y obtener solo los valores

            // Recorrer los valores y asignarlos a las celdas
            foreach ($values as $value) {
                $sheet->setCellValue("$colIndex$row", $value);
                $colIndex++;
                if ($colIndex > $endColumn) break; // Evitar desbordamiento si hay más valores de los esperados
            }

            // Ajustar el alto de la fila para mejor visibilidad
            $sheet->getRowDimension($row)->setRowHeight(20);

            // Avanzar a la siguiente fila
            $row++;
        }

        // Ajustar automáticamente el ancho de las columnas
        foreach (range($startColumn, $endColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Aplicar ajuste de texto a todas las celdas con datos
        $sheet->getStyle("$startColumn$filaini:$endColumn$row")->getAlignment()->setWrapText(true);

        // Retornar el número de la última fila ingresada
        return $row;
    }



    private static function insertarRendicionesFuente($sheet, $cols, $row, $fuentes, $rendiciones, $tcdolar, $tceuro)
    {
        //Creamos 2 variables contadores
        //El contador especial está sujeta a una función algebraica
        $contespecial = 0;
        //Contador simple
        $i = 0;

        foreach ($rendiciones as $rendicion) {
            //Por cada rendición veremos si comparten el mismo fuente_financiamiento_id con las fuentes recibidas
            if (isset($fuentes[$rendicion->fuente_financiamiento_id])) {
                //Creamos 2 variables adicionales que nos permitirán ingresar los registros convertidos en dólares y euros
                $columna = $contespecial;
                $columna1 = $columna + 1;
                $columna2 = $columna + 2;
                //Redondeamos los valores a 2 decimales
                $dolares = round($rendicion->suma_monto_rendiciones / $tcdolar, 2);
                $euros = round($rendicion->suma_monto_rendiciones / $tceuro, 2);
                //Insertamos los registros en el reporte excel
                $sheet->setCellValue("{$cols[$columna]}{$row}", "{$rendicion->suma_monto_rendiciones}");
                $sheet->setCellValue("{$cols[$columna1]}{$row}", "{$dolares}");
                $sheet->setCellValue("{$cols[$columna2]}{$row}", "{$euros}");
                //Ingresamos el valor del contador simple dentro de la función algebraica de $contespecial
                $contespecial = $i * 3 + 3;
            }
            $i++;
        }
    }

    //Función que transforma cualquier dato en mayúscula antes de ser insertado en la base de datos
    //Protected para que solo sea accesible dentro de esta clase
    protected function convertirAMayusculas(array $atributos): array
    {
        foreach ($atributos as $key => $value) {
            if (is_string($value)) {
                $atributos[$key] = mb_strtoupper($value, 'UTF-8');
            }
        }
        return $atributos;
    }
}
