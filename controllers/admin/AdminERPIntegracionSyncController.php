<?php
//require_once dirname(__FILE__) . '/../../sync/StockSyncService.php';
//require_once dirname(__FILE__) . '/../../sync/PricesSyncService.php';
//require_once dirname(__FILE__) . '/../../sync/BodegaSyncService.php';

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

        // Generar URLs para cada acción de sincronización
        $syncStockUrl = $this->context->link->getAdminLink('AdminERPIntegracionSync', true) . '&action=syncStock';
        $syncPricesUrl = $this->context->link->getAdminLink('AdminERPIntegracionSync', true) . '&action=syncPrices';
        $syncSalesUrl = $this->context->link->getAdminLink('AdminERPIntegracionSync', true) . '&action=syncSales';
        $ajaxStockSyncUrl = $this->context->link->getAdminLink('AdminERPIntegracionSync') . '&ajax=1&action=fetchPaginatedStock';

        // Cargar el JS específico para esta página
        Media::addJsDef(['ajax_stock_pagination_url' => $ajaxStockSyncUrl]);
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/back.js');

         // Asignar URLs y otros valores a Smarty
        $this->context->smarty->assign([
            'module' => $this,
            'sync_stock_url' => $syncStockUrl,
            'sync_prices_url' => $syncPricesUrl,
            'sync_sales_url' => $syncSalesUrl,
        ]);

        // Obtener la acción solicitada y cargar la plantilla correspondiente
        $action = Tools::getValue('action');
        switch ($action) {
            case 'syncStock':
                $this->setTemplate('sync_stock.tpl');
                break;
            case 'syncPrices':
                $this->syncPrices();
                //$this->setTemplate('sync_prices.tpl');
                break;
            case 'syncSales':
                $this->syncSales();
                //$this->setTemplate('sync_sales.tpl');
                break;
            default:
                $this->context->smarty->assign('error', 'Acción no reconocida');
                //$this->setTemplate('error.tpl');
                break;
        }
    }

    public function ajaxProcessFetchPaginatedStock()
    {
        // Configuración de paginación
        $page = Tools::getValue('page', 1);
        $itemsPerPage = Tools::getValue('itemsPerPage', 100);

        // Leer el archivo JSON de productos
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/stock_data.json';
        if (!file_exists($jsonFile)) {
            die(json_encode(['status' => 'error', 'message' => 'El archivo de datos de stock no existe.']));
        }

        $stockData = json_decode(file_get_contents($jsonFile), true);
        $totalItems = count($stockData);
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Calcular el rango de elementos para la página actual
        $offset = ($page - 1) * $itemsPerPage;
        $pageData = array_slice($stockData, $offset, $itemsPerPage);

        // Respuesta JSON
        die(json_encode([
            'status' => 'success',
            'data' => $pageData,
            'page' => $page,
            'totalPages' => $totalPages,
            'itemsPerPage' => $itemsPerPage,
        ]));
    }


    private function syncPrices()
    {
        // Lógica de sincronización de precios con el ERP
        $this->context->smarty->assign('result', 'Sincronización de precios completada.');
    }

    private function syncSales()
    {
        // Lógica de sincronización de ventas con el ERP
        $this->context->smarty->assign('result', 'Sincronización de ventas completada.');
    }
}

