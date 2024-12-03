<?php
require_once _PS_MODULE_DIR_ . '/erp_integracion/src/utils/Logger.php';

class ClientSyncService
{
    private $erpSync;

    public function __construct(ErpSyncBase $erpSync)
    {
        $this->erpSync = $erpSync;
    }

    public function addClient($customerData)
    {
        try {
            $this->validateClientData($customerData);
            $response = $this->sendClientDataToErp($customerData);
            return $response;
        } catch (Exception $e) {
            Logger::logError("Error al agregar cliente: " . $e->getMessage());
            return false;
        }
    }

    private function validateClientData($customerData)
    {
        $requiredFields = ['rut', 'firstname', 'lastname', 'email'];
        foreach ($requiredFields as $field) {
            if (empty($customerData[$field])) {
                throw new Exception("El campo '$field' es obligatorio.");
            }
        }
    }

    private function sendClientDataToErp($customerData)
    {
        $endpointName = 'insertaCliente';
        $params = [
            'rut' => $customerData['rut'],
            'nombre' => "{$customerData['firstname']} {$customerData['lastname']}",
            'email' => $customerData['email'],
            // Otros campos...
        ];

        $addParams = http_build_query($params);
        $xml = $this->erpSync->fetchErpData($endpointName, "&" . $addParams, true);

        if (!$xml) {
            throw new Exception("No se recibió una respuesta válida del ERP.");
        }

        $response = simplexml_load_string($xml);
        return $response;
    }
}
