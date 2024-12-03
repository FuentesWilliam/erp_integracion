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
    // Función para sincronizar stock y precio desde el ERP
    public function syncStockAndPrices()
    {
        // Define parámetros específicos para el endpoint de stock
        $endpointName = "consultaInfoProductos";
        $addParams = "&codProd=";

        $cleanedXml = $this->fetchErpCompras($endpointName, $addParams, false);
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
            * <PRODUCTO>
            *     <RESPUESTA>
            *         <CODIGO>56170264</CODIGO>
            *         <NOMBRE>JUEGO DE REPARACION</NOMBRE>
            *         <Stock>0.0000000</Stock>
            *         <CodBodega>B01</CodBodega>
            *         <Bodega>BOD.PRINCIPAL</Bodega>
            *         <PrecioVenta>1072147.00000</PrecioVenta>
            *     </RESPUESTA>
            *    <RESPUESTA>
            *         <CODIGO>56170264</CODIGO>
            *         <NOMBRE>JUEGO DE REPARACION</NOMBRE>
            *         <Stock>0.0000000</Stock>
            *         <CodBodega>B04</CodBodega>
            *         <Bodega>BOD.LINDEROS</Bodega>
            *         <PrecioVenta>1072147.00000</PrecioVenta>
            *     </RESPUESTA>
            *     <RESPUESTA> .... </RESPUESTA>
            * */

        $filteredArray = [];
        foreach ($xml->RESPUESTA as $producto) {
            if (trim((string)$producto->CodBodega) === 'B01') {
                $filteredArray[] = [
                    'reference' => (string)$producto->CODIGO,
                    'stock' => floatval($producto->Stock), // Convertir a número decimal
                    'price' => floatval($producto->PrecioVenta) // Convertir a número decimal
                ];
            }
        }

        // Verificar que el directorio de caché exista
        $cacheDir = _PS_MODULE_DIR_ . 'erp_integracion/cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Guardar los datos en un archivo JSON
        $jsonFile = $cacheDir . 'all_data.json';
        file_put_contents($jsonFile, json_encode($filteredArray));
        
        $this->logMessage("Sincronización de stock y precio completada con éxito. Total de productos: " . count($filteredArray));
    }

    public function addPrestashopData()
    {
        // Obtener todas las referencias de productos existentes en PrestaShop
        $query = "SELECT 
                p.reference AS referencia,
                p.price AS precio,
                sa.quantity AS stock
            FROM ps_product AS p
            JOIN ps_stock_available AS sa ON p.id_product = sa.id_product
            WHERE sa.id_shop = 1;";

        $existingReferences = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        // Crear un array de stock y price de PrestaShop con referencia como clave
        $prestashopData = [];
        foreach ($existingReferences as $row) {
            $prestashopData[$row['referencia']] = [
                'stock' => (int)$row['stock'],  // Convertir stock a entero
                'price' => (float)$row['precio'] // Convertir price a flotante
            ];
        }

        // Cargar el archivo JSON generado anteriormente
        $jsonFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/all_data.json';
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
            if (isset($prestashopData[$reference])) {
                // Producto encontrado en PrestaShop:
                $item['prestashop_stock'] = $prestashopData[$reference]['stock'];
                $item['prestashop_price'] = $prestashopData[$reference]['price'];
                $foundProducts[] = $item;
            } else {
                // Producto no encontrado en PrestaShop
                $notFoundProducts[] = $item;
            }
        }
        unset($item);

        // Guardar el JSON actualizado en el archivo
        $outputFile = _PS_MODULE_DIR_ . 'erp_integracion/cache/found.json';
        if (file_put_contents($outputFile, json_encode($foundProducts, JSON_PRETTY_PRINT)) === false) {
            $this->logMessage("Error: No se pudo guardar el archivo 'found.json' actualizado.", 'sync_error.txt');
            return;
        }

        // Guardar el JSON actualizado en el archivo
        $outputFile2 = _PS_MODULE_DIR_ . 'erp_integracion/cache/notFound.json';
        if (file_put_contents($outputFile2, json_encode($notFoundProducts, JSON_PRETTY_PRINT)) === false) {
            $this->logMessage("Error: No se pudo guardar el archivo 'notFound.json' actualizado.", 'sync_error.txt');
            return;
        }

        $this->logMessage("Creación de stock y precio de PrestaShop completada con éxito.");
    }

    public function syncAddClient($customerData)
    {
        $endpointName = 'insertaCliente';

        try {
            // Validar datos obligatorios
            $requiredFields = ['rut', 'firstname', 'lastname', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($customerData[$field])) {
                    throw new Exception("El campo '$field' es obligatorio pero está vacío.");
                }
            }

            // Construir parámetros para el ERP
            $params = [
                'rut' => $customerData['rut'],
                'nombre' => "{$customerData['firstname']} {$customerData['lastname']}",
                'dir' => $customerData['address'] ?? '',
                'comuna' => $customerData['comuna'] ?? '',
                'ciudad' => $customerData['city'] ?? '',
                'dirDespacho' => $customerData['address_delivery'] ?? '',
                'comunaDespacho' => $customerData['comuna_delivery'] ?? '',
                'ciudadDespacho' => $customerData['city_delivery'] ?? '',
                'email' => $customerData['email'],
                'fono' => $customerData['phone'] ?? '',
                'giro' => $customerData['giro'] ?? '',
            ];

            // Construir la URL de consulta
            $addParams = http_build_query($params);

            // Llamar al endpoint del ERP
            $xml = $this->fetchErpVentas($endpointName, $addParams, true);

            // Validar la respuesta del ERP
            if (!$xml) {
                throw new Exception("No se recibió una respuesta válida del ERP.");
            }

            // Analizar la respuesta XML (ejemplo)
            $response = simplexml_load_string($xml);
            // if (!$response || (string)$response->status !== 'success') {
            //     $errorMsg = $response->message ?? "Respuesta desconocida del ERP";
            //     throw new Exception("Error del ERP: " . $errorMsg);
            // }

            // Log de éxito
            $this->logMessage("Cliente agregado correctamente al ERP: " . print_r($response, true));
            return true;
        } catch (Exception $e) {
            // Log de error
            $this->logMessage("Fallo al intentar agregar un cliente al ERP: " . $e->getMessage(), 'sync_error.txt');
            return false;
        }
    }

    public function syncAddInvoice($invoiceData)
    {
        $endpointName = 'IngresaCabeceraDeFacturaDeVenta';
        try {
            // Validar datos obligatorios
            //$requiredFields = ['rut', 'firstname', 'lastname', 'email'];
            //foreach ($requiredFields as $field) {
            //    if (empty($customerData[$field])) {
            //        throw new Exception("El campo '$field' es obligatorio pero está vacío.");
            //    }
            //}

            // Construir parámetros para el ERP
            $params = [
                'numDocumento' => $invoiceData['numDocumento'] ?? '',
                'fecha' => $invoiceData['fecha'] ?? '',
                'fechaVencimiento' => $invoiceData['fechaVencimiento'] ?? '',
                'rutFactA' => $invoiceData['rutFactA'] ?? '',
                'glosaPago' => $invoiceData['glosaPago'] ?? '',
                'rutCliente' => $invoiceData['rutCliente'] ?? '',
                'codigoMoneda' => $invoiceData['codigoMoneda'] ?? '',
                'codigoComisionista' => $invoiceData['codigoComisionista'] ?? '',
                'comision' => $invoiceData['comision'] ?? '',
                'codigoVendedor' => $invoiceData['codigoVendedor'] ?? '',
                'tipoVenta' => $invoiceData['tipoVenta'] ?? '',
                'numeroGuia' => $invoiceData['numeroGuia'] ?? '',
                'numeroOc' => $invoiceData['numeroOc'] ?? '',
                'descuentoTipo' => $invoiceData['descuentoTipo'] ?? '',
                'descuento' => $invoiceData['descuento'] ?? '',
                'codigoSucursal' => $invoiceData['codigoSucursal'] ?? '',
                'tipoDespacho' => $invoiceData['tipoDespacho'] ?? '',
                'emitePacking' => $invoiceData['emitePacking'] ?? '',
                'rebajaStock' => $invoiceData['rebajaStock'] ?? '',
                'proforma' => $invoiceData['proforma'] ?? '',
                'nula' => $invoiceData['nula'] ?? '',
                'esElectronica' => $invoiceData['esElectronica'] ?? '',
                'formaPago' => $invoiceData['formaPago'] ?? '',
                'atencionA' => $invoiceData['atencionA'] ?? '',
                'codigoCtaCble' => $invoiceData['codigoCtaCble'] ?? '',
                'codigoCentroCosto' => $invoiceData['codigoCentroCosto'] ?? '',
                'glosaContable' => $invoiceData['glosaContable'] ?? '',
                'numOt' => $invoiceData['numOt'] ?? '',
                'procesoNum' => $invoiceData['procesoNum'] ?? '',
                'numCaja' => $invoiceData['numCaja'] ?? '',
                'turno' => $invoiceData['turno'] ?? '',
                'obra' => $invoiceData['obra'] ?? '',
                'observaciones' => $invoiceData['observaciones'] ?? '',
                'codelcoOcNum' => $invoiceData['codelcoOcNum'] ?? '',
                'codelcoFechaOc' => $invoiceData['codelcoFechaOc'] ?? '',
                'codelcoHesNum' => $invoiceData['codelcoHesNum'] ?? '',
                'codelcoFechaHes' => $invoiceData['codelcoFechaHes'] ?? '',
                'codelcoGDNum' => $invoiceData['codelcoGDNum'] ?? '',
                'codelcoFechaGdve' => $invoiceData['codelcoFechaGdve'] ?? '',
                'codigoPersonal' => $invoiceData['codigoPersonal'] ?? '',
            ];

            // Construir la URL de consulta
            $addParams = http_build_query($params);

            // Llamar al endpoint del ERP
            $xml = $this->fetchErpVentas($endpointName, "&". $addParams, true);

            // Validar la respuesta del ERP
            if (!$xml) {
                throw new Exception("No se recibió una respuesta válida del ERP.");
            }

            // Analizar la respuesta XML (ejemplo)
            $response = simplexml_load_string($xml);
            return $response;

            // if (!$response || (string)$response->status !== 'success') {
            //     $errorMsg = $response->message ?? "Respuesta desconocida del ERP";
            //     throw new Exception("Error del ERP: " . $errorMsg);
            // }

            // Log de éxito
            //$this->logMessage("Factura agregada correctamente al ERP: " . print_r($response, true));
            //return true;
            
        } catch (Exception $e) {
            // Log de error
            $this->logMessage("Fallo al intentar agregar una Factura al ERP: " . $e->getMessage(), 'sync_error.txt');
            return false;
        }

    }

}

// // Datos de prueba para la factura
// $testInvoiceData = [
//     'numDocumento' => '120',
//     'fecha' => '28-11-2024',
//     'fechaVencimiento' => '28-11-2024',
//     'rutFactA' => '92379000-4',
//     'glosaPago' => '',
//     'rutCliente' => '92379000-4',
//     'codigoMoneda' => '$',
//     'codigoComisionista' => '',
//     'comision' => '0',
//     'codigoVendedor' => '',
//     'tipoVenta' => '0',
//     'numeroGuia' => '0',
//     'numeroOc' => '0',
//     'descuentoTipo' => '0',
//     'descuento' => '0',
//     'codigoSucursal' => '1',
//     'tipoDespacho' => '0',
//     'emitePacking' => '0',
//     'rebajaStock' => '1',
//     'proforma' => '0',
//     'nula' => '0',
//     'esElectronica' => '1',
//     'formaPago' => 'Efectivo',
//     'atencionA' => '',
//     'codigoCtaCble' => '110501',
//     'codigoCentroCosto' => '',
//     'glosaContable' => '',
//     'numOt' => '0',
//     'procesoNum' => '0',
//     'numCaja' => '0',
//     'turno' => '0',
//     'obra' => '',
//     'observaciones' => '',
//     'codelcoOcNum' => '0',
//     'codelcoFechaOc' => '2024-11-28',
//     'codelcoHesNum' => '0',
//     'codelcoFechaHes' => '2024-11-28',
//     'codelcoGDNum' => '0',
//     'codelcoFechaGdve' => '28-11-2024',
//     'codigoPersonal' => 'MME',
// ];

// // Llamar a la función
// $syncService = new SyncService();
// $result = $syncService->syncAddInvoice($testInvoiceData);

// if ($result) {
//     echo "<pre> ";
//     echo "--> data: ";
//     var_dump($testInvoiceData);
//     var_dump($result);
//     echo "</pre>";
// } else {
//     echo "Error al sincronizar la factura.";
// }
