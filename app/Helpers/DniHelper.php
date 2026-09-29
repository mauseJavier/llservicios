<?php

namespace App\Helpers;

class DniHelper
{
    public const DOC_CUIT = 80;
    public const DOC_CUIL = 86;
    public const DOC_CDI = 87;
    public const DOC_DNI = 96;
    public const DOC_SIN_IDENTIFICAR = 99;

    /**
     * Determina el tipo de documento AFIP del receptor a partir de un DNI/CUIT.
     *
     * - 11 dígitos -> CUIT (80)
     * - 7 a 10 dígitos -> DNI (96)
     * - vacío o inválido -> Sin identificar (99)
     *
     * Acepta valores con guiones o espacios (ej: 20-35833716-4).
     *
     * @param string|int|null $valor
     * @return int Código de tipo de documento AFIP
     */
    public static function tipoDocumentoReceptor($valor): int
    {
        $limpio = preg_replace('/[\s\-]/', '', (string) ($valor ?? ''));

        if ($limpio === '' || !is_numeric($limpio)) {
            return self::DOC_SIN_IDENTIFICAR;
        }

        $longitud = strlen($limpio);

        if ($longitud === 11) {
            return self::DOC_CUIT;
        }

        if ($longitud >= 7 && $longitud <= 10) {
            return self::DOC_DNI;
        }

        return self::DOC_SIN_IDENTIFICAR;
    }

    /**
     * Normaliza un DNI/CUIT dejando solo dígitos (sin guiones ni espacios).
     *
     * @param string|int|null $valor
     * @return string
     */
    public static function soloDigitos($valor): string
    {
        return preg_replace('/\D/', '', (string) ($valor ?? ''));
    }

    /**
     * Extrae el DNI de 8 dígitos desde un valor que puede ser DNI o CUIT.
     * 
     * En Argentina, el CUIT tiene formato: XX-XXXXXXXX-X (11 dígitos)
     * donde los 8 dígitos centrales (posición 3-10) corresponden al DNI.
     * 
     * Ejemplos:
     * - CUIT: 20358337164 -> DNI: 35833716
     * - DNI: 35833716 -> DNI: 35833716
     * 
     * @param string|int|null $valor El valor que puede ser DNI (7-8 dígitos) o CUIT (11 dígitos)
     * @return string|null El DNI normalizado de 8 dígitos o null si el valor es inválido
     */
    public static function extractDni($valor)
    {
        // Si el valor es nulo o vacío, retornar null
        if (empty($valor)) {
            return null;
        }

        // Convertir a string y eliminar espacios y guiones
        $valor = preg_replace('/[\s\-]/', '', (string)$valor);

        // Si no es numérico, retornar null
        if (!is_numeric($valor)) {
            return null;
        }

        $longitud = strlen($valor);

        // Si tiene 11 dígitos (CUIT), extraer los 8 dígitos centrales
        if ($longitud === 11) {
            return substr($valor, 2, 8);
        }

        // Si tiene 7-10 dígitos, asumir que es DNI y retornarlo
        if ($longitud >= 7 && $longitud <= 10) {
            return $valor;
        }

        // Si no cumple con ningún formato esperado, retornar null
        return null;
    }

    /**
     * Compara dos valores de DNI/CUIT normalizando ambos antes de la comparación.
     * 
     * @param string|int|null $valor1 Primer valor a comparar
     * @param string|int|null $valor2 Segundo valor a comparar
     * @return bool True si los DNI normalizados son iguales
     */
    public static function compararDni($valor1, $valor2)
    {
        $dni1 = self::extractDni($valor1);
        $dni2 = self::extractDni($valor2);

        // Si alguno es null, no son comparables
        if ($dni1 === null || $dni2 === null) {
            return false;
        }

        return $dni1 === $dni2;
    }

    /**
     * Busca un cliente por DNI/CUIT, normalizando el valor de búsqueda.
     * 
     * @param string|int $dniOCuit El DNI o CUIT a buscar
     * @return \Illuminate\Database\Eloquent\Builder Query builder para buscar clientes
     */
    public static function buscarPorDni($dniOCuit)
    {
        $dniNormalizado = self::extractDni($dniOCuit);
        
        if ($dniNormalizado === null) {
            return \App\Models\Cliente::whereRaw('1 = 0'); // Query que no retorna resultados
        }

        // Buscar por DNI exacto o por CUIT que contenga el DNI
        return \App\Models\Cliente::where(function($query) use ($dniNormalizado) {
            $query->where('dni', $dniNormalizado)
                  ->orWhere('dni', 'like', '%' . $dniNormalizado . '%');
        });
    }
}
