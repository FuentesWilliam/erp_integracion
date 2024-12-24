<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once dirname(__FILE__, 3) . '/config/config.inc.php';

require_once 'src/services/InvoiceSyncService.php';
require_once 'src/services/ClientSyncService.php';

// Datos de prueba para la factura
$testCustomerData = [
    'rut' => '3.513.113-2',
    'nombre' => 'test2',
    'dir' => 'cerca',
    'comuna' => 'providencia',
    'ciudad' => '',
    'dirDespacho' => '',
    'comunaDespacho' => '',
    'ciudadDespacho' => '',
    'email' => '',
    'fono' => '',
    'giro' => '',
];

$testInvoiceData = [
    'numDocumento' => '597854',
    'fecha' => '19-12-2024',
    'fechaVencimiento' => '20-12-2024',
    'rutFactA' => '91647547-0',
    'glosaPago' => '',
    'rutCliente' => '91647547-0',
    'codigoMoneda' => '$',
    'codigoComisionista' => '',
    'comision' => '0',
    'codigoVendedor' => '',
    'tipoVenta' => '0',
    'numeroGuia' => '0',
    'numeroOc' => '0',
    'descuentoTipo' => '0',
    'descuento' => '0',
    'codigoSucursal' => '1',
    'tipoDespacho' => '0',
    'emitePacking' => '0',
    'rebajaStock' => '1',
    'proforma' => '0',
    'nula' => '0',
    'esElectronica' => '1',
    'formaPago' => 'Efectivo',
    'atencionA' => '',
    'codigoCtaCble' => '110501',
    'codigoCentroCosto' => '',
    'glosaContable' => '',
    'numOt' => '0',
    'procesoNum' => '0',
    'numCaja' => '0',
    'turno' => '0',
    'obra' => '',
    'observaciones' => '',
    'codelcoOcNum' => '0',
    'codelcoFechaOc' => '2024/12/01',
    'codelcoHesNum' => '0',
    'codelcoFechaHes' => '2024/12/01',
    'codelcoGDNum' => '0',
    'codelcoFechaGdve' => '2024/12/01',
    'codigoPersonal' => 'KRQ',
];

$testInvoiceData2 = [
    'numDocumento'=>'597854',
    'fecha'=>'19-12-2024',
    'codigoProducto'=>'961280130146',
    'cantidad'=>'1',
    'descuento'=>'0',
    'numLote'=>'0',
    'codigoBodega'=>'B01',
    'codigoCtaCble'=>'110501',
    'numSerie'=>'0',
    'codigoCentroCosto'=>'0',
    'numItemRef'=>'0',
    'codigoPersonal'=>'KRQ',
];


// Llamar a la función
$syncService = new InvoiceSyncService();
//$result = $syncService->addInvoice($testInvoiceData);

//$syncService = new ClientSyncService();
//$result = $syncService->addClient($testCustomerData);

//$result = $syncService->addInvoiceDescontarStock($testInvoiceData2);

echo "<pre> ";
echo "--> data: ";
var_dump($testInvoiceData2);
var_dump($result);
echo "</pre>";

