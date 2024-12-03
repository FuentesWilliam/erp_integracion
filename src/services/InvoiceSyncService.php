<?php
require_once _PS_MODULE_DIR_ . '/erp_integracion/src/utils/Logger.php';

class InvoiceSyncService
{
    private $erpSync;

    public function __construct(ErpSyncBase $erpSync)
    {
        $this->erpSync = $erpSync;
    }

    public function addInvoice($invoiceData)
    {
        try {
            $response = $this->sendInvoiceToErp($invoiceData);
            return $response;
        } catch (Exception $e) {
            Logger::logError("Error al agregar factura: " . $e->getMessage());
            return false;
        }
    }

    private function sendInvoiceToErp($invoiceData)
    {
        $endpointName = 'IngresaCabeceraDeFacturaDeVenta';
        $params = [
            'numDocumento' => $invoiceData['numDocumento'],
            'fecha' => $invoiceData['fecha'],
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
