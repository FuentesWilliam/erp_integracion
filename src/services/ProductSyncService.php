<?php
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600); // 10 minutos

// Asegúrate de verificar si no está ya definido
if (!defined('_PS_VERSION_')) {
    require_once _PS_ROOT_DIR_ . '/config/config.inc.php';
}

require_once 'ErpSyncBase.php';
require_once _PS_MODULE_DIR_ . '/erp_integracion/src/utils/Logger.php';

class ProductSyncService extends ErpSyncBase
{
    private $jsonData;

    /**
     * Método principal para sincronizar stock y precios desde el ERP.
     * 
     * @return bool
     */
    public function syncStockAndPrices()
    {
        $endpointName = "consultaInfoProductos";
        $addParams = "codProd=";

        try {
            // Obtener los datos de productos desde el ERP
            $data = $this->fetchProductData($endpointName, $addParams);

            // Procesar los datos recibidos
            $this->processProductData($data);

            return true;

        } catch (Exception $e) {
            // Log de error con detalles adicionales
            Logger::logError("Error al sincronizar productos: " . $e->getMessage());
            
            // Obtener el trace y tomar solo la línea 0
            //$trace = debug_backtrace();
            //$errorDetails = $trace[0];

            // Log del trace con detalles de la línea 0
            //Logger::logError("Error en " . $errorDetails['file'] . " en la línea " . $errorDetails['line'] . " en la función " . $errorDetails['function']);
            return false;
            
        }
    }

    /**
     * Función que compara el stock y precio actual de los productos en PrestaShop con los datos del archivo cache data.json.
     * Los productos encontrados se guardan en 'found.json' y los no encontrados en 'notFound.json'.
     */
    public function compareProductsWithPrestashop()
    {
        try {
            // 1. Obtener referencias de productos de PrestaShop
            $prestashopData = $this->getPrestashopReferences();

            // 2. Cargar datos desde el archivo JSON
            $jsonData = $this->loadJsonData(_PS_MODULE_DIR_ . 'erp_integracion/src/cache/data.json');

            // 3. Comparar datos y generar listas de encontrados y no encontrados
            [$foundProducts, $notFoundProducts] = $this->compareProductData($jsonData, $prestashopData);

            // 4. Guardar los resultados en archivos JSON
            $this->saveJsonToFile(_PS_MODULE_DIR_ . 'erp_integracion/src/cache/found.json', $foundProducts);
            $this->saveJsonToFile(_PS_MODULE_DIR_ . 'erp_integracion/src/cache/notFound.json', $notFoundProducts);

            Logger::logInfo("Creación de stock y precio de PrestaShop completada con éxito.");
            return true;

        } catch (Exception $e) {
            Logger::logError("Error al comparar productos: " . $e->getMessage());
            return false;

        }
    }

    /**
     * Actualiza el stock de productos a partir de datos locales.
     * 
     * @return array
     */
    public function updateStock()
    {
        try {
            // Cargar los datos de precios desde el archivo JSON
            $jsonData = $this->loadJsonData(_PS_MODULE_DIR_ . 'erp_integracion/src/cache/found.json');
            $updatedCount = 0;
            $errorCount = 0;

            foreach ($jsonData as $product) {
                $product_id = Product::getIdByReference($product['reference']);
                $quantity = (int)$product['stock'];

                if ($product_id) {
                    $currentStock = StockAvailable::getQuantityAvailableByProduct($product_id);
                    if ($currentStock !== $quantity) {
                        StockAvailable::setQuantity($product_id, 0, $quantity);
                        $updatedCount++; // Incrementar el contador de actualizaciones
                    }
                } else {
                    $errorCount++;
                    // Log de error si no se encuentra el producto
                    Logger::logError("No se encontró el producto con referencia: " . $product['reference']);
                }
            }

            // Devolver el resultado después de procesar todos los productos
            return [
                'status' => 'success',
                'updated' => $updatedCount,
                'errors' => $errorCount,
            ];
            
        } catch (Exception $e) {
            // Si ocurre algún error al cargar el archivo JSON, se maneja aquí
            Logger::error('El archivo de datos de stock no existe o hubo un error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        } 
    }

    /**
     * Actualiza el precio de productos a partir de datos locales.
     * 
     * @return array
     */
    public function updatePrice()
    {
        try {
            // Cargar los datos de precios desde el archivo JSON
            $jsonData = $this->loadJsonData(_PS_MODULE_DIR_ . 'erp_integracion/src/cache/found.json');
            $updatedCount = 0;
            $errorCount = 0;

            // Iterar sobre los productos en el archivo JSON
            foreach ($jsonData as $product) {
                // Obtener el ID del producto por referencia
                $product_id = Product::getIdByReference($product['reference']);
                $price = (float)$product['price'];

                if ($product_id) {
                    // Cargar el producto de PrestaShop
                    $product = new Product($product_id);
                    $currentPrice = $product->price;

                    // Comparar el precio y actualizar si es necesario
                    if ($currentPrice !== $price) {
                        $product->price = $price;
                        $product->update();
                        $updatedCount++; // Incrementar el contador de actualizaciones
                    }
                } else {
                    $errorCount++; // Incrementar el contador de errores si no se encuentra el producto
                    // Log de error si no se encuentra el producto
                    Logger::logError("No se encontró el producto con referencia: " . $product['reference']);
                }
            }

            // Devolver el resultado después de procesar todos los productos
            return [
                'status' => 'success',
                'updated' => $updatedCount,
                'errors' => $errorCount,
            ];

        } catch (Exception $e) {
            // Si ocurre algún error al cargar el archivo JSON, se maneja aquí
            Logger::error('El archivo de datos de precios no existe o hubo un error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }        
    }

    /**
     * Obtiene los datos de productos desde el ERP.
     * 
     * @param string $endpoint Nombre del endpoint para consultar productos.
     * @param string $params Parámetros adicionales para la consulta.
     * @return SimpleXMLElement
     * @throws Exception
     */
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

    /**
     * Procesa los datos XML de productos recibidos desde el ERP.
     * 
     * @param SimpleXMLElement $xml Datos XML a procesar.
     * @throws Exception
     */
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
        // Guardar los datos procesados en caché
        $this->saveJsonToFile(_PS_MODULE_DIR_ . 'erp_integracion/src/cache/data.json', $filteredArray);
    }

    private function getPrestashopReferences()
    {
        $query = "SELECT 
                    p.reference AS referencia,
                    p.price AS precio,
                    sa.quantity AS stock
                FROM ps_product AS p
                JOIN ps_stock_available AS sa ON p.id_product = sa.id_product
                WHERE sa.id_shop = 1;";
        
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        $data = [];
        foreach ($results as $row) {
            $data[$row['referencia']] = [
                'stock' => (int)$row['stock'],
                'price' => (float)$row['precio']
            ];
        }
        return $data;
    }

    private function compareProductData($jsonData, $prestashopData)
    {
        $foundProducts = [];
        $notFoundProducts = [];

        foreach ($jsonData as &$item) {
            $reference = $item['reference'];
            if (isset($prestashopData[$reference])) {
                $item['prestashop_stock'] = $prestashopData[$reference]['stock'];
                $item['prestashop_price'] = $prestashopData[$reference]['price'];
                $foundProducts[] = $item;
            } else {
                $notFoundProducts[] = $item;
            }
        }
        unset($item);

        return [$foundProducts, $notFoundProducts];
    }

    private function loadJsonData($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("El archivo JSON no existe o no es legible.");
        }

        $data = json_decode(file_get_contents($filePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error de JSON: " . json_last_error_msg());
        }

        return $data;
    }

    private function saveJsonToFile($filePath, $data)
    {
        if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT)) === false) {
            throw new Exception("No se pudo guardar el archivo JSON en $filePath.");
        }
    }

}
