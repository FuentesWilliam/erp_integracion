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

class InvoiceSyncService extends ErpSyncBase
{
    public function addInvoice($invoiceData)
    {
        Logger::logInfo("Enviando factura al ERP ....");

        try {
            $this->validateInvoiceData($invoiceData);
            $response = $this->sendInvoiceToErp($invoiceData);
            return $response;

        } catch (Exception $e) {
            Logger::logWarning("Error al agregar factura: " . $e->getMessage());
            return false;

        }
    }

    private function validateInvoiceData($invoiceData)
    {
        $requiredFields = ['rutCliente'];
        foreach ($requiredFields as $field) {
            if (empty($invoiceData[$field])) {
                throw new Exception("El campo '$field' es obligatorio pero está vacío.");
            }
        }
    }

    private function sendInvoiceToErp($invoiceData)
    {
        // Construir la URL de consulta
        $endpointName = 'IngresaCabeceraDeFacturaDeVenta';
        $addParams = http_build_query($invoiceData);

        $response = $this->fetchErpVentas($endpointName, $addParams, true);
        if (!$response) {
            throw new Exception("No se recibió una respuesta válida del ERP.");
        }

        // Analizar la respuesta XML
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            throw new Exception("Error al procesar los datos del ERP.");
        }

        Logger::logInfo("respondio: " . print_r($xml['mensaje'], true)); 

        // if ((string)$xml->RESPUESTA !== 'OK') {
        //     throw new Exception("El ERP devolvió un error: " . (string)$xml->MENSAJE);
        // }

        $filePath = _PS_MODULE_DIR_ . 'erp_integracion/src/logs/invoice_' . date('Ymd_His') . '.json';
        $this->saveJsonToFile($filePath, print_r($xml, true));

        return $xml;
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
}