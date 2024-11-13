<?php
// Configuración de error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600); // 10 minutos

require_once dirname(__FILE__, 4) . '/config/config.inc.php';
require_once 'ErpSyncBase.php';

class SyncService extends ErpSyncBase
{

    // Función para sincronizar stock desde el ERP
    public function syncStock()
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
            $this->logMessage("Error: No se pudo cargar el XML después de la conversión.", $endpointName."_log.txt");
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

        // Verificar que el directorio de caché exista
        $cacheDir = _PS_MODULE_DIR_ . 'erp_integracion/cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Guardar los datos en un archivo JSON
        $jsonFile = $cacheDir . 'stock_data.json';
        file_put_contents($jsonFile, json_encode($filteredArray));
        
        $this->logMessage("Sincronización de stock completada con éxito. Total de productos: " . count($filteredArray), $endpointName."_log.txt");
    }

    // Función para añadir stock de PrestaShop y el estado al JSON
    public function addPrestashopStockAndStatus()
    {
        // Obtener todas las referencias de productos existentes en PrestaShop
        $query = "SELECT p.reference AS referencia, sa.quantity AS stock
                FROM ps_product AS p
                JOIN ps_stock_available AS sa ON p.id_product = sa.id_product
                WHERE sa.id_shop = 1;";

        $existingReferences = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        // Crear un array de stock de PrestaShop con referencia como clave
        $prestashopStock = [];
        foreach ($existingReferences as $row) {
            $prestashopStock[$row['referencia']] = (int)$row['stock'];
        }

        // Cargar el archivo JSON generado anteriormente
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/stock_data.json';
        $jsonData = json_decode(file_get_contents($jsonFile), true);

        // Arrays para productos encontrados y no encontrados
        $foundProducts = [];
        $notFoundProducts = [];

        // Actualizar los productos en función de si existen en PrestaShop
        foreach ($jsonData as $item) {
            $reference = $item['reference'];
            if (isset($prestashopStock[$reference])) {
                // Producto encontrado en PrestaShop: añadir cantidad de stock
                $item['prestashop_stock'] = $prestashopStock[$reference];
                $item['estado'] = 1;
                $foundProducts[] = $item;
            } else {
                // Producto no encontrado en PrestaShop
                $item['prestashop_stock'] = 0;
                $item['estado'] = 0;
                $notFoundProducts[] = $item;
            }
        }

    
        // Combinar productos encontrados y no encontrados, poniendo los encontrados primero
        $jsonData = array_merge($foundProducts, $notFoundProducts);

        // Guardar el JSON actualizado en el archivo
        file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));
        $this->logMessage("Actualización de stock de PrestaShop completada con éxito.", "prestashop_stock_log.txt");
    }
}

// Ejecutar el servicio y manejar errores
try {
    $service = new SyncService();
    //$service->syncStock();
    $service->addPrestashopStockAndStatus();
} catch (Exception $e) {
    // Registrar cualquier excepción
    $service->logMessage("Excepción: " . $e->getMessage(), "sync_service_error_log.txt");
}
