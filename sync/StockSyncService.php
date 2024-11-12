<?php
// Configuración de error reporting
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');

// Aumentar límite de memoria y tiempo de ejecución
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600); // 10 minutos

require_once 'ErpSyncBase.php';

class StockSyncService extends ErpSyncBase
{

    // Función para sincronizar stock desde el ERP
    public static function syncStock()
    {
        // Define parámetros específicos para el endpoint de stock
        $endpointName = "consultaStockMasivo";

        $cleanedXml = $this->fetchErpData($endpointName);
        if ($cleanedXml === false) {
            return;
        }

        // Convertir y procesar el XML
        $utf16Xml = mb_convert_encoding($cleanedXml, 'UTF-16', 'UTF-8');
        $xml = simplexml_load_string($utf16Xml);
        if ($xml === false) {
            $this->logMessage("Error: No se pudo cargar el XML después de la conversión.");
            return;
        }

        $filteredArray = [];
        foreach ($xml->Articulo as $articulo) {
            if (trim((string)$articulo->Cod_Bodega) === 'B01') {
                $filteredArray[] = [
                    'reference' => (string)$articulo->Cod_Prod,
                    'stock' => isset($articulo->Cantidad) ? (int)$articulo->Cantidad : 0
                ];
            }
        }

        //$this->logMessage("Sincronización de stock completada con éxito. Total de productos: " . count($filteredArray));
        return $filteredArray;
    }
}