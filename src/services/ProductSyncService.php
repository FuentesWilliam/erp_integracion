<?php 
// Asegúrate de verificar si no está ya definido
if (!defined('_PS_VERSION_')) {
    require_once _PS_ROOT_DIR_ . '/config/config.inc.php';
}

require_once 'ErpSyncBase.php';
require_once _PS_MODULE_DIR_ . '/erp_integracion/src/utils/Logger.php';

class ProductSyncService extends ErpSyncBase
{
    private $jsonFound;
    private $jsonData;

    public function syncStockAndPrices()
    {
        $endpointName = "consultaInfoProductos";
        $addParams = "&codProd=";

        try {
            $data = $this->fetchProductData($endpointName, $addParams);
            $this->processProductData($data);
            return true;
        } catch (Exception $e) {
            Logger::logError("Error al sincronizar productos: " . $e->getMessage());
            
            // Obtener el trace y tomar solo la línea 0
            $trace = debug_backtrace();
            $errorDetails = $trace[0];

            // Log del trace con detalles de la línea 0
            Logger::logError("Error en " . $errorDetails['file'] . " en la línea " . $errorDetails['line'] . " en la función " . $errorDetails['function']);
            return false;
        }
    }

    public function updateStock()
    {
        $this->jsonData  = _PS_MODULE_DIR_ . 'erp_integracion/src/cache/data.json';

        if (!file_exists($this->jsonFile)) {
            Logger::error('El archivo de datos de stock no existe.');
            return ['status' => 'error', 'message' => 'El archivo de datos de stock no existe.'];
        }

        $data = json_decode(file_get_contents($this->jsonFile), true);
        $updatedCount = 0;
        $errorCount = 0;

        foreach ($data as $product) {
            try {
                $product_id = Product::getIdByReference($product['reference']);
                $quantity = (int)$product['stock'];

                if ($product_id) {
                    $currentStock = StockAvailable::getQuantityAvailableByProduct($product_id);
                    if ($currentStock !== $quantity) {
                        StockAvailable::setQuantity($product_id, 0, $quantity);
                        $updatedCount++;
                    }
                } else {
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }

        return [
            'status' => 'success',
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ];
    }

    public function updatePrice()
    {
        $this->jsonData  = _PS_MODULE_DIR_ . 'erp_integracion/src/cache/data.json';

        if (!file_exists($this->jsonFile)) {
            Logger::error('El archivo de datos de precios no existe.');
            return ['status' => 'error', 'message' => 'El archivo de datos de precios no existe.'];
        }

        $data = json_decode(file_get_contents($this->jsonFile), true);
        $updatedCount = 0;
        $errorCount = 0;

        foreach ($data as $product) {
            try {
                $product_id = Product::getIdByReference($product['reference']);
                $price = (float)$product['price'];

                if ($product_id) {
                    $product = new Product($product_id);
                    $currentPrice = $product->price;
                    if ($currentPrice !== $price) {
                        $product->price = $price;
                        $product->update();
                        $updatedCount++;
                    }
                } else {
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }

        return [
            'status' => 'success',
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ];
    }

    private function fetchProductData($endpoint, $params)
    {
        // Obtener datos del ERP
        $response = $this->fetchErpCompras($endpoint, $params, false);
        if (!$response) {
            throw new Exception("No se recibió una respuesta válida del ERP.");
        }

        $utf16Xml = mb_convert_encoding($response, 'UTF-16', 'UTF-8');
        return simplexml_load_string($utf16Xml);
    }

    private function processProductData($xml)
    {
        if ($xml === false) {
            throw new Exception("Error al procesar los datos del ERP.");
        }

        $filteredArray = [];
        foreach ($xml->RESPUESTA as $producto) {
            if (trim((string)$producto->CodBodega) === 'B01') {
                $filteredArray[] = [
                    'reference' => (string)$producto->CODIGO,
                    'stock' => floatval($producto->Stock),
                    'price' => floatval($producto->PrecioVenta),
                ];
            }
        }

        $this->saveProductDataToCache($filteredArray);
    }

    private function saveProductDataToCache($data)
    {
        $this->jsonData  = _PS_MODULE_DIR_ . 'erp_integracion/src/cache/data.json';

        if (!file_exists($this->jsonData)) {
            throw new Exception("El archivo de datos no existe: ". $this->jsonData);
        }

        if (file_put_contents($this->jsonData, json_encode($data)) === false) {
            throw new Exception("No se pudo guardar el archivo de datos de productos: " . $this->jsonData);
        }

        Logger::logInfo('Sincronización de stock y precio completada: ' . count($data));
    }
}
