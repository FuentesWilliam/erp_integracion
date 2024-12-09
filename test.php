<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once dirname(__FILE__, 3) . '/config/config.inc.php';

require_once 'src/services/InvoiceSyncService.php';
require_once 'src/services/ClientSyncService.php';

// Datos de prueba para la factura
$testInvoiceData = [
    'numDocumento' => '120',
    'fecha' => '2024/12/01',
    'fechaVencimiento' => '2024/12/02',
    'rutFactA' => '92379000-4',
    'glosaPago' => '',
    'rutCliente' => '92379000-4',
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
    'codigoPersonal' => 'MME',
];

$testCustomerData = [
    'rut' => '66666666-6',
    'nombre' => '',
    'dir' => '',
    'comuna' => '',
    'ciudad' => '',
    'dirDespacho' => '',
    'comunaDespacho' => '',
    'ciudadDespacho' => '',
    'email' => '',
    'fono' => '',
    'giro' => '',
];

// Llamar a la función
$syncService = new InvoiceSyncService();
$result = $syncService->addInvoice($testInvoiceData);

//$syncService = new ClientSyncService();
//$result = $syncService->addClient($testCustomerData);


echo "<pre> ";
echo "--> data: ";
//var_dump($testCustomerData);
var_dump($result);
echo "</pre>";

