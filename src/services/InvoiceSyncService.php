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
        Logger::logSync(
            "Iniciando sincronización de factura al ERP",
            "pending", "Pedido: " . $invoiceData['numeroOc']
        );

        try {
            // Normalizar el RUT (remover puntos)
            if (!empty($invoiceData['rutFactA'])) {
                $invoiceData['rutFactA'] = $this->normalizeRut($invoiceData['rutFactA']);
            }

            if (!empty($invoiceData['rutCliente'])) {
                $invoiceData['rutCliente'] = $this->normalizeRut($invoiceData['rutCliente']);
            }

            $this->validateInvoiceData($invoiceData);
            $response = $this->sendInvoiceToErp($invoiceData);            
            return $response;

        } catch (Exception $e) {
            Logger::logSync(
                "Error durante la sincronización de factura: ",
                "failure", $e->getMessage()
            );
            return false;
        }
    }

    public function addInvoiceDetails($invoiceData, $reference)
    {
        Logger::logSync(
            "Iniciando sincronización de factura detalles al ERP",
            'pending', "Pedido: {$reference} N: {$invoiceData['numDocumento']}"
        );

        try {
            $response = $this->sendInvoiceToErpDetails($invoiceData);
            return $response;

        } catch (Exception $e) {
            Logger::logSync("Error durante la sincronización de factura", 'failure', $e->getMessage() );
            return false;

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
            $error = libxml_get_last_error();
            throw new Exception("Error al procesar los datos del ERP: " . ($error ? $error->message : "Error desconocido"));
        }

        $return = false;
        $mnj = (string)$xml->Mensaje; //ejemplo <Información ingresada N: 339423>
        if (strpos($mnj, "ingresada") !== false) {
            Logger::logSync(
                "Respuesta ERP factura : ",
                'success', $mnj
            );

            $pos = strpos($mnj, "N: ");
            if ($pos !== false) {
                $return = substr($mnj, $pos + 3);
            } else {
                throw new Exception("No se encontró el identificador en la respuesta del ERP.");
            }
            
        }else{
            Logger::logSync(
                "Respuesta ERP factura : ",
                'failure', $mnj
            );
        }

        return $return;
    }

    private function sendInvoiceToErpDetails($invoiceData)
    {
        // Construir la URL de consulta
        $endpointName = 'factVentaDetIngresar_V2';
        $addParams = http_build_query($invoiceData);

        $response = $this->fetchErpVentas($endpointName, $addParams, true);
        if (!$response) {
            throw new Exception("No se recibió una respuesta válida del ERP.");
        }

        // Analizar la respuesta XML
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            $error = libxml_get_last_error();
            throw new Exception("Error al procesar los datos del ERP: " . ($error ? $error->message : "Error desconocido"));
        }

        $mnj = (string)$xml->Mensaje; //ejemplo <Información ingresada N: 339423>
        if (strpos($mnj, 'permiso EXECUTE') == true){
            Logger::logSync(
                "Respuesta del ERP factura detalles: ",
                "failure", $mnj
            );
        }else{
            Logger::logSync(
                "Respuesta del ERP factura detalles: ",
                "success", $mnj
            );
        }
        
        return true;
    }

    private function validateInvoiceData($invoiceData)
    {
        $requiredFields = ['rutCliente', 'rutFactA'];
        foreach ($requiredFields as $field) {
            if (empty($invoiceData[$field])) {
                throw new Exception("El campo '$field' es obligatorio pero está vacío.");
            }
        }
    }

    private function normalizeRut($rut)
    {
        // Remover puntos del RUT
        return str_replace('.', '', $rut);
    }
}