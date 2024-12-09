<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');

// Asegúrate de verificar si no está ya definido
if (!defined('_PS_VERSION_')) {
    require_once _PS_ROOT_DIR_ . '/config/config.inc.php';
}

require_once 'ErpSyncBase.php';
require_once _PS_MODULE_DIR_ . '/erp_integracion/src/utils/Logger.php';

class ClientSyncService extends ErpSyncBase
{
    public function addClient($customerData)
    {
        Logger::logInfo("Enviando cliente al ERP ...");

        try {
            // Validar que el RUT esté presente y sea válido
            if (empty($customerData['rut']) || !$this->isValidRut($customerData['rut'])) {
                throw new Exception("El campo 'rut' es obligatorio y debe ser válido.");
            }

            $response = $this->sendClientDataToErp($customerData);
            return $response;

        } catch (Exception $e) {
            Logger::logError("Error al agregar cliente: " . $e->getMessage());
            return false;

        }
    }

    private function sendClientDataToErp($customerData)
    {
        $endpointName = 'insertaCliente';
        $addParams = http_build_query($customerData);

        $response = $this->fetchErpCompras($endpointName, $addParams, true);
        if (!$response) {
            throw new Exception("No se recibió una respuesta válida del ERP.");
        }

        // Analizar la respuesta XML
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            throw new Exception("Error al procesar los datos del ERP.");
        }

        // Registrar log
        Logger::logInfo("Respuesta del ERP: " . print_r($xml, true));

        // if (strpos((string)$xml->Mensaje, "ingresada") !== false || 
        //     strpos((string)$xml->Mensaje, "modificado") !== false) {
        //     $status = true; // Operación exitosa

        // } else {
        //     $status = false; // Operación fallida
        // }

        return true;
    }

    private function saveJsonToFile($filePath, $data)
    {
        try {
            if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT)) === false) {
                Logger::logError("No se pudo guardar el archivo JSON en $filePath.");
            }
        } catch (Exception $e) {
            Logger::logError("Excepción al guardar el archivo JSON: " . $e->getMessage());
        }
    }

    // Ejemplo de validador de RUT (simple)
    private function isValidRut($rut)
    {
        // Validación básica para evitar campos vacíos, longitud o caracteres no válidos
        return preg_match('/^[0-9]{7,8}-[0-9kK]$/', $rut); // Formato: 12345678-K
    }
}
