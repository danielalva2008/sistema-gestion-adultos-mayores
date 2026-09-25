<?php
/**
 * Validación de formato de los datos de una actividad (RN-01..RN-05).
 * Solo formato/estructura; las reglas que dependen de la BD viven en el Servicio.
 * Nombres/longitudes verificados contra database.sql oficial.
 * G6 §8.1 paso 3, §6.1.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

class Validador
{
    /** Longitudes VARCHAR reales de database.sql (V-08). */
    public const MAX_NOMBRE      = 100;
    public const MAX_LUGAR       = 80;
    public const MAX_DESCRIPCION = 255;

    /**
     * Normaliza y valida el formato de la actividad.
     *
     * @param array $in  Datos crudos del formulario ($_POST).
     * @return array{datos: array, errores: array<string,string>}
     *         'errores' está indexado por campo (RN-30: se devuelven TODOS los de formato).
     */
    public static function actividad(array $in): array
    {
        $errores = [];

        // --- Normalización de texto (RN-02: trim; espacios => vacío) ---
        $nombre      = trim((string)($in['nombre'] ?? ''));
        $lugar       = trim((string)($in['lugar'] ?? ''));
        $descripcion = trim((string)($in['descripcion'] ?? ''));
        $fecha       = trim((string)($in['fecha'] ?? ''));
        $hora_inicio = trim((string)($in['hora_inicio'] ?? ''));
        $hora_fin    = trim((string)($in['hora_fin'] ?? ''));
        $cupo_raw    = trim((string)($in['cupo'] ?? ''));
        $responsable = trim((string)($in['id_personal'] ?? ''));

        // --- Obligatorios (RN-01) ---
        if ($nombre === '')      { $errores['nombre']      = 'El campo Nombre es obligatorio.'; }
        if ($lugar === '')       { $errores['lugar']       = 'El campo Lugar es obligatorio.'; }
        if ($fecha === '')       { $errores['fecha']       = 'El campo Fecha es obligatorio.'; }
        if ($hora_inicio === '') { $errores['hora_inicio'] = 'El campo Hora de inicio es obligatorio.'; }
        if ($hora_fin === '')    { $errores['hora_fin']    = 'El campo Hora de fin es obligatorio.'; }

        // --- Longitudes (RN-02 / V-08 / MSG-E02) ---
        if ($nombre !== '' && mb_strlen($nombre) > self::MAX_NOMBRE) {
            $errores['nombre'] = 'El campo Nombre supera los ' . self::MAX_NOMBRE . ' caracteres permitidos.';
        }
        if ($lugar !== '' && mb_strlen($lugar) > self::MAX_LUGAR) {
            $errores['lugar'] = 'El campo Lugar supera los ' . self::MAX_LUGAR . ' caracteres permitidos.';
        }
        if (mb_strlen($descripcion) > self::MAX_DESCRIPCION) {
            $errores['descripcion'] = 'El campo Descripción supera los ' . self::MAX_DESCRIPCION . ' caracteres permitidos.';
        }

        // --- Fecha estricta Y-m-d y real (RN-03 / MSG-E03) ---
        if ($fecha !== '' && !self::fecha_valida($fecha)) {
            $errores['fecha'] = 'La fecha ingresada no es válida.';
        }

        // --- Horas H:i, normalizadas a H:i:00 (RN-03) ---
        $hi = self::normaliza_hora($hora_inicio);
        $hf = self::normaliza_hora($hora_fin);
        if ($hora_inicio !== '' && $hi === null) {
            $errores['hora_inicio'] = 'La hora de inicio no es válida.';
        }
        if ($hora_fin !== '' && $hf === null) {
            $errores['hora_fin'] = 'La hora de fin no es válida.';
        }

        // --- hora_fin > hora_inicio, mismo día (RN-04 / MSG-E04) ---
        if ($hi !== null && $hf !== null && $hf <= $hi) {
            $errores['hora_fin'] = 'La hora de fin debe ser posterior a la hora de inicio.';
        }

        // --- Cupo entero >= 1 (RN-05 / MSG-E05) ---
        $cupo = filter_var($cupo_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($cupo === false) {
            $errores['cupo'] = 'El cupo debe ser un número entero mayor que 0.';
            $cupo = null;
        }

        // --- Responsable: entero >= 1 (existencia/estado se valida en el Servicio, RN-07) ---
        $id_personal = filter_var($responsable, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id_personal === false) {
            $errores['id_personal'] = 'Debe seleccionar un responsable válido.';
            $id_personal = null;
        }

        $datos = [
            'nombre'      => $nombre,
            'lugar'       => $lugar,
            'descripcion' => $descripcion === '' ? null : $descripcion,
            'fecha'       => $fecha,
            'hora_inicio' => $hi,   // H:i:00 o null
            'hora_fin'    => $hf,   // H:i:00 o null
            'cupo'        => $cupo,
            'id_personal' => $id_personal,
        ];

        return ['datos' => $datos, 'errores' => $errores];
    }

    /** Fecha estricta Y-m-d y existente (rechaza 2026-02-30). */
    public static function fecha_valida(string $fecha): bool
    {
        $d = DateTime::createFromFormat('!Y-m-d', $fecha);
        return $d !== false && $d->format('Y-m-d') === $fecha;
    }

    /** Devuelve 'H:i:00' si la hora es válida en formato H:i, o null. */
    public static function normaliza_hora(string $hora): ?string
    {
        if ($hora === '') {
            return null;
        }
        $d = DateTime::createFromFormat('!H:i', $hora);
        if ($d !== false && $d->format('H:i') === $hora) {
            return $hora . ':00';
        }
        // Aceptar también H:i:s por si el navegador lo envía así.
        $d2 = DateTime::createFromFormat('!H:i:s', $hora);
        if ($d2 !== false && $d2->format('H:i:s') === $hora) {
            return substr($hora, 0, 5) . ':00';
        }
        return null;
    }
}
