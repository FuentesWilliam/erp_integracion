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
    // Lista de RUT que deben ser excluidos
    private $excludedRuts = [
        "11111111-1",
        "22222222-2",
        "33333333-3",
        "44444444-4",
        "55555555-5",
        "66666666-6",
        "77777777-7",
        "88888888-8",
        "99999999-9"
    ];

    public function addClient($customerData, $customerId = null)
    {
        Logger::logSync("Iniciando sincronización de cliente al ERP", "pending", "rut: " . $customerData['rut']);

        try {
             // Normalizar el RUT (remover puntos)
            if (!empty($customerData['rut'])) {
                $customerData['rut'] = $this->normalizeRut($customerData['rut']);
            }

            // Validar que el RUT esté presente, sea válido y no esté excluido
            if (empty($customerData['rut']) || !$this->isValidRut($customerData['rut'])) {
                throw new Exception("El campo 'rut' es obligatorio y debe ser válido.");
            }

            if ($this->isExcludedRut($customerData['rut'])) {
                throw new Exception("El RUT está en la lista de exclusión.");
            }

            Logger::logSync("Enviando datos formateados del cliente al ERP", "pending", "rut: " . $customerData['rut']);

            $response = $this->sendClientDataToErp($customerData);
            
            return $response;

        } catch (Exception $e) {
            Logger::logSync("Error durante la sincronización del cliente", "failure", $e->getMessage());
            return false;

        }
    }

    # /modules/erp_integracion/src/services/ClientSyncService.php
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

        $status = false;
        if (strpos((string)$xml->Mensaje, "ingresada") !== false || 
            strpos((string)$xml->Mensaje, "modificado") !== false) {
            $status = true;
        }
        // Registrar log
        Logger::logSync("Respuesta ERP cliente : ",
            $status ? 'success' : 'failure',
            (string) $xml->Mensaje);
        return $status;
    }

    private function normalizeRut($rut)
    {
        // Remover puntos del RUT
        return str_replace('.', '', $rut);
    }

    private function isValidRut($rut)
    {
        // Validación básica para evitar campos vacíos, longitud o caracteres no válidos
        return preg_match('/^[0-9]{7,8}-[0-9kK]$/', $rut); // Formato: 12345678-K
    }

    private function isExcludedRut($rut)
    {
        // Verificar si el RUT está en la lista de excluidos
        return in_array($rut, $this->excludedRuts);
    }
}
