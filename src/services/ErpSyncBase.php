<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once _PS_MODULE_DIR_ . '/erp_integracion/src/utils/Logger.php';

/**
 * Clase base para la sincronización con el ERP.
 * Proporciona métodos comunes para interactuar con endpoints de compras y ventas.
 */

abstract class ErpSyncBase
{
    /**
     * @var string Endpoint para las compras en el ERP.
     */
    protected $erpEndpointCompras;

    /**
     * @var string Endpoint para las ventas en el ERP.
     */
    protected $erpEndpointVentas;

    /**
     * @var string RUT de la empresa configurado.
     */
    protected $rutEmpresa;

    /**
     * @var string Token de autenticación para el ERP.
     */
    protected $erpToken;

    /**
     * Constructor de la clase.
     * Inicializa las configuraciones y valida que estén completas.
     *
     * @throws Exception Si alguna configuración necesaria está ausente.
     */
    public function __construct()
    {
        try {
            $this->initializeConfig();
            $this->validateConfig();
        } catch (Exception $e) {
            Logger::logError("Error en la inicialización del ERP: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Inicializa las configuraciones desde la base de datos.
     */
    private function initializeConfig()
    {
        $this->rutEmpresa = Configuration::get('ERP_RUT');
        $this->erpToken = Configuration::get('ERP_TOKEN');
        $this->erpEndpointCompras = Configuration::get('ERP_ENDPOINT_COMPRAS');
        $this->erpEndpointVentas = Configuration::get('ERP_ENDPOINT_VENTAS');
    }

    /**
     * Valida que las configuraciones requeridas estén presentes.
     *
     * @throws Exception Si falta alguna configuración.
     */
    private function validateConfig()
    {
        if (empty($this->rutEmpresa) 
            || empty($this->erpToken) 
            || empty($this->erpEndpointCompras) 
            || empty($this->erpEndpointVentas)) {
            throw new Exception('Configuraciones de ERP incompletas.');
        }
    }

    /**
     * Obtiene datos del endpoint de compras en el ERP.
     *
     * @param string $endpointName Nombre del endpoint específico.
     * @param string $additionalParams Parámetros adicionales para la solicitud (opcional).
     * @param bool $isUtf8 Indica si la respuesta debe devolverse tal cual (true) o limpiarse (false).
     * @return string|false Respuesta del ERP o false si ocurrió un error.
     */
    protected function fetchErpCompras($endpointName, $additionalParams = "", $isUtf8 = false)
    {
        return $this->fetchErpData($this->erpEndpointCompras, $endpointName, $additionalParams, $isUtf8);
    }

    /**
     * Obtiene datos del endpoint de ventas en el ERP.
     *
     * @param string $endpointName Nombre del endpoint específico.
     * @param string $additionalParams Parámetros adicionales para la solicitud (opcional).
     * @param bool $isUtf8 Indica si la respuesta debe devolverse tal cual (true) o limpiarse (false).
     * @return string|false Respuesta del ERP o false si ocurrió un error.
     */
    protected function fetchErpVentas($endpointName, $additionalParams = "", $isUtf8 = false)
    {
        return $this->fetchErpData($this->erpEndpointVentas, $endpointName, $additionalParams, $isUtf8);
    }

    /**
     * Método genérico para realizar solicitudes al ERP.
     *
     * @param string $baseUrl URL base del endpoint (compras o ventas).
     * @param string $endpointName Nombre del endpoint específico.
     * @param string $additionalParams Parámetros adicionales para la solicitud.
     * @param bool $isUtf8 Indica si la respuesta debe devolverse tal cual (true) o limpiarse (false).
     * @return string|false Respuesta del ERP o false si ocurrió un error.
     */
    private function fetchErpData($baseUrl, $endpointName, $additionalParams, $isUtf8)
    {
        try {
            $url = "{$baseUrl}{$endpointName}?rutEmpresa={$this->rutEmpresa}&token={$this->erpToken}&{$additionalParams}";

            $response = @file_get_contents($url);

            if ($response === false) {
                Logger::logError("Error al conectarse al endpoint '$endpointName'. URL: $url");
                return false;
            }

            return $isUtf8 ? $response : $this->cleanXmlData($response);

        } catch (Exception $e) {
            Logger::logError("Excepción en fetchErpData: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpia y prepara un string XML recibido del ERP.
     *
     * @param string $response Respuesta sin procesar del ERP.
     * @return string|false Contenido XML limpio o false si ocurrió un error.
     */
    protected function cleanXmlData($response)
    {
        $lines = explode("\n", $response);
        if (!isset($lines[5])) {
            Logger::logError('Error: La línea 5 no existe en la respuesta del ERP.');
            return false;
        }

        $decodedXml = html_entity_decode(trim($lines[5]));
        $decodedXml = preg_replace(['/^<Data>/', '/<\/Data>$/', '/[\x00-\x1F\x7F]/'], '', $decodedXml);
        /**
         * $decodedXml = preg_replace('/^<Data>/', '', $decodedXml);
         * $decodedXml = preg_replace('/<\/Data>$/', '', $decodedXml);
         * $decodedXml = preg_replace('/[\x00-\x1F\x7F]/', '', $decodedXml);
         * */

        if (empty($decodedXml)) {
            Logger::logError('Error: El contenido XML está vacío después de la limpieza.');
            return false;
        }

        return $decodedXml;
    }
}
