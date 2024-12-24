<?php

class Logger
{

    // Método público para registrar un mensaje en el archivo JSON de logs
    public static function logSync($message, $status, $context = null)
    {
        $date = date('Y-m-d'); // Obtener la fecha actual
        $logFilePath = _PS_MODULE_DIR_ . "erp_integracion/src/logs/sync_log_{$date}.json";

        // Crear la entrada del log
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'status' => $status, // success, failure, pending
            'context' => $context
        ];

        // Llamar a la función estática para escribir el log
        self::writeJsonLog($logFilePath, $logEntry);
    }

    private static function writeJsonLog($file, $newEntry)
    {
        // Verifica si el directorio existe, si no, lo crea
        if (!file_exists(dirname($file))) {
            if (!mkdir(dirname($file), 0777, true)) {
                throw new Exception("No se pudo crear el directorio para los logs: " . dirname($file));
            }
        }

        // Usa 'a+' para abrir el archivo en modo lectura/escritura
        $fileHandle = fopen($file, 'a+'); // 'a+' permite lectura/escritura y coloca el puntero al final
        if ($fileHandle) {
            // Bloquea el archivo para evitar accesos simultáneos
            if (flock($fileHandle, LOCK_EX)) {
                // Asegúrate de mover el puntero al principio para leer contenido existente
                rewind($fileHandle);

                // Leer el contenido actual del archivo
                $existingLogs = [];
                $fileContents = stream_get_contents($fileHandle);
                if (!empty($fileContents)) {
                    $existingLogs = json_decode($fileContents, true);
                    if (!is_array($existingLogs)) {
                        $existingLogs = [];
                    }
                }

                // Agregar la nueva entrada al historial
                $existingLogs[] = $newEntry;

                // Reescribir el archivo con los nuevos datos
                ftruncate($fileHandle, 0); // Limpia el archivo
                rewind($fileHandle); // Vuelve al principio
                fwrite($fileHandle, json_encode($existingLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                // Libera el bloqueo
                flock($fileHandle, LOCK_UN);
            } else {
                fclose($fileHandle);
                throw new Exception("No se pudo bloquear el archivo para escritura: " . $file);
            }
            fclose($fileHandle);
        } else {
            throw new Exception("No se puede abrir el archivo de log: " . $file);
        }
    }

    public static function getLogs($date = null)
    {
        $date = $date ?: date('Y-m-d'); // Por defecto, la fecha actual
        $logFilePath = _PS_MODULE_DIR_ . "erp_integracion/src/logs/sync_log_{$date}.json";

        if (file_exists($logFilePath)) {
            return json_decode(file_get_contents($logFilePath), true) ?: [];
        }

        return []; // Devuelve un array vacío si no hay logs para la fecha
    }
}