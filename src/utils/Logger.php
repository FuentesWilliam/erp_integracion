<?php

class Logger
{
    // Ruta del archivo de log
    const LOG_FILE = _PS_MODULE_DIR_ . 'erp_integracion/src/logs/sync_log.txt';
    const ERROR_LOG_FILE = _PS_MODULE_DIR_ . 'erp_integracion/src/logs/sync_error.txt';

    /**
     * Escribe un mensaje de log en el archivo general de logs.
     * 
     * @param string $message El mensaje que se registrará
     */
    public static function logInfo($message)
    {
        self::writeLog(self::LOG_FILE, "[INFO] " . self::getTimestamp() . " - " . $message);
    }

    /**
     * Escribe un mensaje de advertencia en el archivo de logs de errores.
     * 
     * @param string $message El mensaje que se registrará
     */
    public static function logWarning($message)
    {
        self::writeLog(self::LOG_FILE, "[WARNING] " . self::getTimestamp() . " - " . $message);
    }

    /**
     * Escribe un mensaje de error en el archivo de logs de errores.
     * 
     * @param string $message El mensaje de error que se registrará
     */
    public static function logError($message)
    {
        self::writeLog(self::ERROR_LOG_FILE, "[ERROR] " . self::getTimestamp() . " - " . $message);
    }

    /**
     * Escribe el mensaje en el archivo de log especificado.
     * 
     * @param string $file El archivo donde se guardará el log
     * @param string $message El mensaje que se guardará en el archivo
     */
    private static function writeLog($file, $message)
    {
        if (file_exists($file)) {
            // Verificar si el archivo es escribible
            if (is_writable($file)) {
                file_put_contents($file, $message . PHP_EOL, FILE_APPEND);
            } else {
                // Si el archivo no es escribible, se escribirá un mensaje en el log de errores
                error_log("No se puede escribir en el archivo de log: " . $file);
            }
        } else {
            // Si el archivo no existe, crearlo
            file_put_contents($file, $message . PHP_EOL);
        }
    }

    /**
     * Devuelve la marca de tiempo actual para los logs.
     * 
     * @return string La marca de tiempo en formato Y-m-d H:i:s
     */
    private static function getTimestamp()
    {
        return date('Y-m-d H:i:s');
    }
}
