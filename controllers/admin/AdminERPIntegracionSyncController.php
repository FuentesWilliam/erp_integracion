<?php

class AdminERPIntegracionSyncController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        // Obtener la acción solicitada y cargar la plantilla correspondiente
        $action = Tools::getValue('action');
        switch ($action) {
            case 'syncInventory':
                $this->sync_inventory();
                break;
            default:
                $this->setTemplate('error.tpl');
                break;
        }
    }

    public function sync_inventory(){
        // Generar URLs para cada acción de sincronización
        $mainUrl     = $this->context->link->getAdminLink('AdminModules') . '&configure=erp_integracion';
        $getUrl      = $this->context->link->getAdminLink('AdminERPIntegracionSync') . '&ajax=1&action=getAll';
        $updateUrlStock   = $this->context->link->getAdminLink('AdminERPIntegracionSync') . '&ajax=1&action=updateStock';
        $notFoundUrlStock = $this->context->link->getAdminLink('AdminERPIntegracionSync') . '&ajax=1&action=notFound';
        $updateUrlPrice   = $this->context->link->getAdminLink('AdminERPIntegracionSync') . '&ajax=1&action=updatePrice';
        $notFoundUrlPrice = $this->context->link->getAdminLink('AdminERPIntegracionSync') . '&ajax=1&action=notFoundPrice';

        // Cargar el JS específico para esta página
        Media::addJsDef([
            'pagination_url' => $getUrl,
            'update_url_s' => $updateUrlStock,
            'update_url_p' => $updateUrlPrice
        ]);
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/all.js');

        $this->context->smarty->assign([
            'main_menu_url' => $mainUrl,
            'download_not_found' => $notFoundUrlStock,
        ]);

        $this->setTemplate('sync_all.tpl');
    }

    public function ajaxProcessGetAll()
    {
        // Configuración de paginación
        $page = Tools::getValue('page', 1);
        $itemsPerPage = Tools::getValue('itemsPerPage', 100);

        // Leer el archivo JSON de productos
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/found.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos de stock no existe.']));
        }

        // Cargar y decodificar los datos de stock
        $stockData = json_decode(file_get_contents($jsonFile), true);
        $countUdpStock = $countUdpPrice = 0;

        foreach ($stockData as $product) {
            if($product['stock'] !== $product['prestashop_stock']){
                $countUdpStock++;
            }

            if($product['price'] !== $product['prestashop_price']){
                $countUdpPrice++;
            }
        }

        // Calcular total de elementos y páginas después del filtrado
        $totalItems = count($stockData);
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Calcular el rango de elementos para la página actual
        $offset = ($page - 1) * $itemsPerPage;
        $pageData = array_slice($stockData, $offset, $itemsPerPage);

        // Respuesta JSON
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
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/stock.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos de stock no existe.']));
        }

        $stockData = json_decode(file_get_contents($jsonFile), true);

        // Filtrar solo los productos encontrados
        $foundProducts = array_filter($stockData, function ($product) {
            return isset($product['encontrado']) && $product['encontrado'] == 1;
        });

        // Inicializar contadores para los resultados
        $updatedCount = 0;
        $errorCount = 0;

        foreach ($foundProducts as $product) {
            try {
                // Obtener producto de PrestaShop por referencia
                $product_id = Product::getIdByReference($product['reference']);
                $quantity  = (int)$product['stock']; 

                // Verifica si el producto existe y actualiza su cantidad
                if ($product_id) {
                    StockAvailable::setQuantity($product_id, 0, $quantity); // 0 es el ID de atributo (para productos simples)
                    $updatedCount++;
                } else {
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }
        
        // Respuesta JSON
        die(json_encode([
            'status' => 'success',
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ]));
    }

    public function ajaxProcessUpdatePrice()
    {
        // Leer el archivo JSON de productos
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/stock.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos de stock no existe.']));
        }

        $stockData = json_decode(file_get_contents($jsonFile), true);

        // Filtrar solo los productos encontrados
        $foundProducts = array_filter($stockData, function ($product) {
            return isset($product['encontrado']) && $product['encontrado'] == 1;
        });

        // Inicializar contadores para los resultados
        $updatedCount = 0;
        $errorCount = 0;

        foreach ($foundProducts as $product) {
            try {
                // Obtener producto de PrestaShop por referencia
                $product_id = Product::getIdByReference($product['reference']);
                $quantity  = (int)$product['stock']; 

                // Verifica si el producto existe y actualiza su cantidad
                if ($product_id) {
                    StockAvailable::setQuantity($product_id, 0, $quantity); // 0 es el ID de atributo (para productos simples)
                    $updatedCount++;
                } else {
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }
        
        // Respuesta JSON
        die(json_encode([
            'status' => 'success',
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ]));
    }

    public function ajaxProcessNotFound()
    {
        // Leer el archivo JSON de productos
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/stock.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos de stock no existe.']));
        }

        $stockData = json_decode(file_get_contents($jsonFile), true);

        // Filtrar productos que NO están en PrestaShop
        $notFoundProducts = array_filter($stockData, function ($product) {
            return isset($product['encontrado']) && $product['encontrado'] == 0;
        });

        // Definir el nombre y la ruta del archivo CSV temporal
        $csvFile = tempnam(sys_get_temp_dir(), 'no_encontrados_') . '.csv';

        // Crear el archivo CSV y agregar encabezados
        $fileHandle = fopen($csvFile, 'w');
        fputcsv($fileHandle, ['Referencia']); // Cambia los encabezados según tus campos

        // Añadir datos de productos no encontrados al CSV
        foreach ($notFoundProducts as $product) {
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

