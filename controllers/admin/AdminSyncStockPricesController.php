<?php
require_once _PS_MODULE_DIR_ . 'erp_integracion/src/services/ProductSyncService.php';

class AdminSyncStockPricesController extends ModuleAdminController
{

	public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->productSyncService = new ProductSyncService();
    }

    public function initContent()
    {
        parent::initContent();

        // Cargar el archivo CSS
        $this->context->controller->addCSS(
            $this->module->getPathUri() . 'views/css/back.css'
        );

        // Generar URLs para cada acción de sincronización
        $mainUrl     = $this->context->link->getAdminLink('AdminModules') . '&configure=erp_integracion';
        $syncUrl     = $this->context->link->getAdminLink('AdminSyncStockPrices') . '&ajax=1&action=sync';
        $getAll      = $this->context->link->getAdminLink('AdminSyncStockPrices') . '&ajax=1&action=getAll';
        $updateStock = $this->context->link->getAdminLink('AdminSyncStockPrices') . '&ajax=1&action=updateStock';
        $updatePrice = $this->context->link->getAdminLink('AdminSyncStockPrices') . '&ajax=1&action=updatePrice';
        $notFound    = $this->context->link->getAdminLink('AdminSyncStockPrices') . '&ajax=1&action=notFound';


        // Cargar el JS específico para esta página
        Media::addJsDef([
            'pagination_url' => $getAll,
            'erp_url'        => $syncUrl,
            'stock_url'      => $updateStock,
            'price_url'      => $updatePrice
        ]);
        $this->context->controller->addJS(
            $this->module->getPathUri() . 'views/js/all.js'
        );

        $this->context->smarty->assign([
            'main_menu_url' => $mainUrl,
            'download_not_found' => $notFound,
        ]);

        $this->setTemplate('sync_all.tpl');
    }

    public function ajaxProcessSync()
    {
        // Primero sincronizamos stock y precios
        $syncResult = $this->productSyncService->syncStockAndPrices();

        // Luego comparamos los productos con PrestaShop
        $compareResult = $this->productSyncService->compareProductsWithPrestashop();

        // Retornamos el resultado de ambas operaciones
        die(json_encode(['status' => $syncResult && $compareResult]));
    }

    public function ajaxProcessGetAll()
    {
        //actualizamos precios
        $this->productSyncService->compareProductsWithPrestashop();

        $page = Tools::getValue('page', 1);
        $itemsPerPage = Tools::getValue('itemsPerPage', 100);

        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/src/cache/found.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos de stock no existe.']));
        }

        $stockData = json_decode(file_get_contents($jsonFile), true);
        $countUdpStock = $countUdpPrice = 0;

        foreach ($stockData as $product) {
            if ($product['stock'] !== $product['prestashop_stock']) {
                $countUdpStock++;
            }

            if ($product['price'] !== $product['prestashop_price']) {
                $countUdpPrice++;
            }
        }

        $totalItems = count($stockData);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $offset = ($page - 1) * $itemsPerPage;
        $pageData = array_slice($stockData, $offset, $itemsPerPage);

        die(json_encode([
            'status' => 'success',
            'data' => $pageData,
            'udpStock' => $countUdpStock,
            'udpPrice' => $countUdpPrice,
            'page' => $page,
            'totalPages' => $totalPages,
            'itemsPerPage' => $itemsPerPage,
        ]));
    }      

    public function ajaxProcessUpdateStock()
    {
        // Leer el archivo JSON de productos
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/src/cache/found.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos no existe.']));
        }

        $data = json_decode(file_get_contents($jsonFile), true);

        // Inicializar contadores para los resultados
        $updatedCount = 0;
        $errorCount = 0;

        // Iterar sobre cada producto en el archivo JSON
        foreach ($data as $product) {
            try {
                // Obtener producto de PrestaShop por referencia
                $product_id = Product::getIdByReference($product['reference']);
                $quantity  = (int)$product['stock'];

                // Verifica si el producto existe en PrestaShop
                if ($product_id) {
                    // Obtener el stock actual del producto en PrestaShop
                    $currentStock = StockAvailable::getQuantityAvailableByProduct($product_id);

                    // Verificamos si el stock ha cambiado
                    if ($currentStock !== $quantity) {
                        // Si el stock ha cambiado, actualizar
                        StockAvailable::setQuantity($product_id, 0, $quantity); // 0 es el ID de atributo (para productos simples)
                        $updatedCount++;
                    }
                } else {
                    // Si no se encuentra el producto
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }
    
        // Respuesta JSON
        die(json_encode([
            'status' => true,
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ]));
    }

    public function ajaxProcessUpdatePrice()
    {
        // Leer el archivo JSON de productos
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/src/cache/found.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos no existe.']));
        }

        $data = json_decode(file_get_contents($jsonFile), true);

        // Inicializar contadores para los resultados
        $updatedCount = 0;
        $errorCount = 0;

        // Iterar sobre cada producto en el archivo JSON
        foreach ($data as $product) {
            try {
                // Obtener producto de PrestaShop por referencia
                $product_id = Product::getIdByReference($product['reference']);
                $price = (float)$product['price'];

                // Verifica si el producto existe en PrestaShop
                if ($product_id) {
                    
                    $product = new Product($product_id);
                    $currentPrice = $product->price;

                    // Verificamos si el precio ha cambiado
                    if ($currentPrice !== $price) {
                        // Si el precio ha cambiado, actualizar
                        $product->price = $price;  // Actualizamos el precio
                        $product->update();  // Guardamos el cambio
                        $updatedCount++;
                    }
                } else {
                    // Si no se encuentra el producto
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }
        
        // Respuesta JSON
        die(json_encode([
            'status' => true,
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ]));
    }

    public function ajaxProcessNotFound()
    {
        // Leer el archivo JSON de productos
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/src/cache/notFound.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos no existe.']));
        }

        $stockData = json_decode(file_get_contents($jsonFile), true);

        // Definir el nombre y la ruta del archivo CSV temporal
        $csvFile = tempnam(sys_get_temp_dir(), 'no_encontrados_') . '.csv';

        // Crear el archivo CSV y agregar encabezados
        $fileHandle = fopen($csvFile, 'w');
        fputcsv($fileHandle, ['Referencia']); // Cambia los encabezados según tus campos

        // Añadir datos de productos no encontrados al CSV
        foreach ($stockData as $product) {
            fputcsv($fileHandle, [
                $product['reference']
            ]);
        }

        fclose($fileHandle);

        // Configuración de la descarga del archivo
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="productos_no_encontrados.csv"');
        header('Content-Length: ' . filesize($csvFile));

        // Enviar el archivo al navegador y luego eliminarlo del servidor
        readfile($csvFile);
        unlink($csvFile);
        exit;
    }
}