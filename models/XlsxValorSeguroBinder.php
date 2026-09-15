<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Binder de valores para los reportes Excel que NUNCA convierte un texto en fórmula.
 *
 * El binder por defecto de PhpSpreadsheet trata como fórmula cualquier cadena que
 * empiece por "=". En los reportes se escriben textos que teclean los usuarios
 * (nombre del rubro, razón social, detalle, descripción…), así que un coordinador
 * que nombrara un rubro `=HYPERLINK("http://…","Ver")` hacía llegar una fórmula
 * ACTIVA al Excel que abre el Contador o el donante, y otras podían romper la
 * generación del reporte (auditoría de seguridad 2026-09-14, hallazgo M4).
 *
 * Con este binder todo `setCellValue()` con una cadena que empiece por "=" se guarda
 * como texto literal. Las fórmulas legítimas del sistema (los TOTAL con =SUM) se
 * escriben con `setCellValueExplicit(..., DataType::TYPE_FORMULA)`, que no pasa por
 * el binder. Se instala con `nuevoLibroXlsx()` (includes/funciones.php).
 *
 * No hace falta tratar "+", "-" ni "@": la celda se guarda con tipo texto y Excel no
 * la evalúa. Los números siguen siendo números (el resto lo decide el binder base).
 */
class XlsxValorSeguroBinder extends DefaultValueBinder
{
    public static function dataTypeForValue(mixed $value): string
    {
        if (is_string($value) && strlen($value) > 1 && $value[0] === '=') {
            return DataType::TYPE_STRING;
        }
        return parent::dataTypeForValue($value);
    }
}
