<?php

namespace App\Helpers;

class NumberToWordsHelper
{
    private static $unidades = [
        '', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'
    ];

    private static $decenas = [
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'
    ];

    private static $decenasBase = [
        '', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'
    ];

    private static $centenas = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'
    ];

    public static function convert($number)
    {
        if ($number == 0) {
            return 'CERO PESOS M/CTE';
        }

        $entero = floor($number);
        $decimales = round(($number - $entero) * 100);

        $palabras = self::convertirEntero($entero);
        
        return $palabras . ' PESOS M/CTE';
    }

    private static function convertirEntero($numero)
    {
        if ($numero == 0) {
            return 'CERO';
        }

        if ($numero < 10) {
            return self::$unidades[$numero];
        }

        if ($numero < 20) {
            return self::$decenas[$numero - 10];
        }

        if ($numero < 100) {
            $decena = floor($numero / 10);
            $unidad = $numero % 10;
            
            if ($unidad == 0) {
                return self::$decenasBase[$decena];
            }
            
            if ($decena == 2) {
                return 'VEINTI' . self::$unidades[$unidad];
            }
            
            return self::$decenasBase[$decena] . ' Y ' . self::$unidades[$unidad];
        }

        if ($numero < 1000) {
            $centena = floor($numero / 100);
            $resto = $numero % 100;
            
            if ($numero == 100) {
                return 'CIEN';
            }
            
            if ($resto == 0) {
                return self::$centenas[$centena];
            }
            
            return self::$centenas[$centena] . ' ' . self::convertirEntero($resto);
        }

        if ($numero < 1000000) {
            $miles = floor($numero / 1000);
            $resto = $numero % 1000;
            
            $palabraMiles = '';
            if ($miles == 1) {
                $palabraMiles = 'MIL';
            } else {
                $palabraMiles = self::convertirEntero($miles) . ' MIL';
            }
            
            if ($resto == 0) {
                return $palabraMiles;
            }
            
            return $palabraMiles . ' ' . self::convertirEntero($resto);
        }

        if ($numero < 1000000000) {
            $millones = floor($numero / 1000000);
            $resto = $numero % 1000000;
            
            $palabraMillones = '';
            if ($millones == 1) {
                $palabraMillones = 'UN MILLON';
            } else {
                $palabraMillones = self::convertirEntero($millones) . ' MILLONES';
            }
            
            if ($resto == 0) {
                return $palabraMillones;
            }
            
            return $palabraMillones . ' ' . self::convertirEntero($resto);
        }

        return 'NUMERO DEMASIADO GRANDE';
    }
}
