<?php

class Logger
{
    // Archivos de log por defecto
    private static $logFile = _PS_MODULE_DIR_ . 'erp_integracion/src/logs/sync_log.txt';
    private static $errorLogFile = _PS_MODULE_DIR_ . 'erp_integracion/src/logs/sync_error.txt';

    /**
     * Establece el archivo de log general.
     * 
     * @param string $file Ruta del archivo de log
     */
    public static function setLogFile($file)
    {
        self::$logFile = $file;
    }

    /**
     * Establece el archivo de log de errores.
     * 
     * @param string $file Ruta del archivo de log de errores
     */
    public static function setErrorLogFile($file)
    {
        self::$errorLogFile = $file;
    }

    /**
     * Escribe un mensaje de log en el archivo general de logs.
     * 
     * @param string $message El mensaje que se registrará
     */
    public static function logInfo($message)
    {
        self::writeLog(self::$logFile, "[INFO] " . self::getTimestamp() . " - " . $message);
    }

    /**
     * Escribe un mensaje de advertencia en el archivo de logs de errores.
     * 
     * @param string $message El mensaje que se registrará
     */
    public static function logWarning($message)
    {
        self::writeLog(self::$logFile, "[WARNING] " . self::getTimestamp() . " - " . $message);
    }

    /**
     * Escribe un mensaje de error en el archivo de logs de errores.
     * 
     * @param string $message El mensaje de error que se registrará
     */
    public static function logError($message)
    {
        self::writeLog(self::$errorLogFile, "[ERROR] " . self::getTimestamp() . " - " . $message);
    }

    /**
     * Escribe el mensaje en el archivo de log especificado.
     * 
     * @param string $file El archivo donde se guardará el log
     * @param string $message El mensaje que se guardará en el archivo
     * @throws Exception Si no se puede escribir en el archivo
     */
    private static function writeLog($file, $message)
    {
        // Verifica si el directorio existe, si no, lo crea
        if (!file_exists(dirname($file))) {
            if (!mkdir(dirname($file), 0777, true)) {
                throw new Exception("No se pudo crear el directorio para los logs: " . dirname($file));
            }
        }

        // Abre el archivo de log en modo 'append' (agregar al final)
        $fileHandle = fopen($file, 'a');
        if ($fileHandle) {
            // Bloquea el archivo para evitar accesos simultáneos
            if (flock($fileHandle, LOCK_EX)) {
                fwrite($fileHandle, $message . PHP_EOL);
                flock($fileHandle, LOCK_UN);  // Libera el bloqueo
            } else {
                fclose($fileHandle);
                throw new Exception("No se pudo bloquear el archivo para escritura: " . $file);
            }
            fclose($fileHandle);
        } else {
            throw new Exception("No se puede abrir el archivo de log: " . $file);
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
