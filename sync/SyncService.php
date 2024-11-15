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
            $this->logMessage("Error: No se pudo cargar el XML '$endpointName' después de la conversión.", 'sync_error.txt');
            return;
        }

        /** ## Ejemplo de lo que trae el XML
        * <Articulos>
        *     <Articulo>
        *         <Cod_Prod>000000001500A</Cod_Prod>
        *         <Descripcion>CONO SINCRONIZADOR</Descripcion>
        *         <Cod_Bodega>B02</Cod_Bodega>
        *         <Uni_Medida>C/U</Uni_Medida>
        *         <Cantidad>0.00</Cantidad>
        *         <Cta_Ctble_VI>110801</Cta_Ctble_VI>
        *         <NombreLote></NombreLote>
        *     </Articulo>
        * 
        *     <Articulo> .... </Articulo>
        *     
        * </Articulos>
        * */

        $filteredArray = [];
        foreach ($xml->Articulo as $producto) {
            if (trim((string)$producto->Cod_Bodega) === 'B01') {
                $filteredArray[] = [
                    'reference' => (string)$producto->Cod_Prod,
                    'stock' => isset($producto->Cantidad) ? (int)$producto->Cantidad : 0
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
        
        $this->logMessage("Sincronización de stock completada con éxito. Total de productos: " . count($filteredArray));
    }

    // Función para sincronizar stock desde el ERP
    public function syncPrices()
    {
        // Define parámetros específicos para el endpoint de stock
        $endpointName = "TodasListasPrecio";

        $cleanedXml = $this->fetchErpData($endpointName);
        if ($cleanedXml === false) {
            return;
        }

        // Convertir y procesar el XML
        $utf16Xml = mb_convert_encoding($cleanedXml, 'UTF-16', 'UTF-8');
        $xml = simplexml_load_string($utf16Xml);
        if ($xml === false) {
            $this->logMessage("Error: No se pudo cargar el XML '$endpointName' después de la conversión.", 'sync_error.txt');
            return;
        }

        /** ## Ejemplo de lo que trae el XML
         * <ListasDePrecio>
         *   <Resultado>
         *     <CodigoLista>MGS</CodigoLista>
         *     <NombreLista>MGS</NombreLista>
         *     <CodigoProducto>00080</CodigoProducto>
         *     <NombreProducto>REGISTRO DE FRENO DE 10 ESTRIAS CHICHARRA)</NombreProducto>
         *     <Precio>0.0000</Precio> 
         *   </Resultado>
         * 
         *   <Resultado> .... </Resultado>
         * 
         * </ListasDePrecio> 
         * */

        $filteredArray = [];
        foreach ($xml->Resultado as $producto) {
            $filteredArray[] = [
                'reference' => (string)$producto->CodigoProducto,
                'price' => (int)$producto->Precio,
                'codLista' =>(string)$producto->CodigoLista,
                //'lista' =>(string)$producto->NombreLista,
                //'name' =>(string)$producto->NombreProducto,
            ];
        }

        // Verificar que el directorio de caché exista
        $cacheDir = _PS_MODULE_DIR_ . 'erp_integracion/cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Guardar los datos en un archivo JSON
        $jsonFile = $cacheDir . 'price_data.json';
        file_put_contents($jsonFile, json_encode($filteredArray));
        
        $this->logMessage("Sincronización de precio completada con éxito. Total de productos: " . count($filteredArray));
    }

    // Función para añadir stock de PrestaShop y el estado al JSON
    public function addPrestashopStock()
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
        if (!file_exists($jsonFile) || !is_readable($jsonFile)) {
            $this->logMessage("Error: El archivo JSON no existe o no es legible.", 'sync_error.txt');
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logMessage("Error de JSON: " . json_last_error_msg(), 'sync_error.txt');
            return;
        }

        // Arrays para productos encontrados y no encontrados
        $foundProducts = [];
        $notFoundProducts = [];

        // Actualizar los productos en función de si existen en PrestaShop
        foreach ($jsonData as &$item) {
            $reference = $item['reference'];
            if (isset($prestashopStock[$reference])) {
                // Producto encontrado en PrestaShop: añadir cantidad de stock
                $item['prestashop_stock'] = $prestashopStock[$reference];
                $item['encontrado'] = 1;
                $foundProducts[] = $item;
            } else {
                // Producto no encontrado en PrestaShop
                $item['prestashop_stock'] = 0;
                $item['encontrado'] = 0;
                $notFoundProducts[] = $item;
            }
        }
        unset($item);

        // Combinar productos encontrados y no encontrados, poniendo los encontrados primero
        $jsonData = array_merge($foundProducts, $notFoundProducts);

        // Guardar el JSON actualizado en el archivo
        $outputFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/stock.json';
        if (file_put_contents($outputFile, json_encode($jsonData, JSON_PRETTY_PRINT)) === false) {
            $this->logMessage("Error: No se pudo guardar el archivo JSON actualizado.", 'sync_error.txt');
            return;
        }

        $this->logMessage("Creación de stock de PrestaShop completada con éxito.");
    }

    // Función para añadir precios de PrestaShop y el estado al JSON
    public function addPrestashopPrices()
    {
        // Obtener todas las referencias de productos existentes en PrestaShop junto con sus precios
        $query = "SELECT p.reference AS referencia, p.price AS precio FROM ps_product AS p";
        $existingReferences = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        // Crear un array de precios de PrestaShop con referencia como clave
        $prestashopPrices = [];
        foreach ($existingReferences as $row) {
            $prestashopPrices[$row['referencia']] = (float)$row['precio'];
        }

        // Cargar el archivo JSON generado anteriormente
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/price_data.json';
        if (!file_exists($jsonFile) || !is_readable($jsonFile)) {
            $this->logMessage("Error: El archivo JSON no existe o no es legible.", 'sync_error.txt');
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logMessage("Error de JSON: " . json_last_error_msg(), 'sync_error.txt');
            return;
        }

        // Arrays para productos encontrados y no encontrados
        $foundProducts = [];
        $notFoundProducts = [];

        // Actualizar los productos en función de si existen en PrestaShop
        foreach ($jsonData as &$item) {
            $reference = $item['reference'];
            if (isset($prestashopPrices[$reference])) {
                // Producto encontrado en PrestaShop: añadir precio
                $item['prestashop_price'] = $prestashopPrices[$reference];
                $item['encontrado'] = 1;
                $foundProducts[] = $item;
            } else {
                // Producto no encontrado en PrestaShop
                $item['prestashop_price'] = 0.0;
                $item['encontrado'] = 0;
                $notFoundProducts[] = $item;
            }
        }
        unset($item);

        // Combinar productos encontrados y no encontrados, poniendo los encontrados primero
        $jsonData = array_merge($foundProducts, $notFoundProducts);

        // Guardar el JSON actualizado en el archivo
        $outputFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/price.json';
        if (file_put_contents($outputFile, json_encode($jsonData, JSON_PRETTY_PRINT)) === false) {
            $this->logMessage("Error: No se pudo guardar el archivo JSON actualizado.", 'sync_error.txt');
            return;
        }

        $this->logMessage("Creación de precios de PrestaShop completada con éxito.");
    }

}

// Ejecutar el servicio y manejar errores
$service = new SyncService();

try {
    //$service->syncStock();
    //$service->syncPrices();
    $service->addPrestashopStock();
    //$service->addPrestashopPrices();
} catch (Exception $e) {
    // Registrar cualquier excepción
    $service->logMessage("Excepción en SyncService: " . $e->getMessage(), 'sync_error.txt');
}
