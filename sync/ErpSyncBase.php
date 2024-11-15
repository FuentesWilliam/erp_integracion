<?php

abstract class ErpSyncBase
{
    protected $erpEndpoint;
    protected $rutEmpresa;
    protected $erpToken;

    public function __construct()
    {
        // Configuración obtenida desde la base de datos
        $this->rutEmpresa = Configuration::get('ERP_MANAGER_RUT');
        $this->erpToken = Configuration::get('ERP_MANAGER_TOKEN');
        $this->erpEndpoint = Configuration::get('ERP_MANAGER_ENDPOINT');

        // Validar que las configuraciones estén presentes
        if (empty($this->rutEmpresa) || empty($this->erpToken) || empty($this->erpEndpoint)) {
            $this->logMessage("Error: Faltan configuraciones necesarias para el ERP.");
            throw new Exception("Configuraciones de ERP incompletas.");
        }
    }

    // Método para escribir en el log
    public function logMessage($message, $log_file = 'sync_succes.txt')
    {
        $logDir = _PS_MODULE_DIR_ . 'erp_integracion/logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . $log_file;
        $date = new DateTime();
        file_put_contents($logFile, $date->format('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    }

    // Método para limpiar y preparar el XML
    protected function cleanXmlData($response)
    {
        $lines = explode("\n", $response);
        if (!isset($lines[5])) {
            $this->logMessage("Error: La línea 5 no existe en la respuesta del ERP.", 'sync_error.txt');
            return false;
        }

        $decodedXml = html_entity_decode(trim($lines[5]));
        $decodedXml = preg_replace('/^<Data>/', '', $decodedXml);
        $decodedXml = preg_replace('/<\/Data>$/', '', $decodedXml);
        $decodedXml = preg_replace('/[\x00-\x1F\x7F]/', '', $decodedXml);

        if (empty($decodedXml)) {
            $this->logMessage("Error: El contenido XML está vacío después de la limpieza.", 'sync_error.txt');
            return false;
        }

        return $decodedXml;
    }

    // Método común para obtener datos de un endpoint específico del ERP
    protected function fetchErpData($endpointName, $additionalParams = "")
    {
        try {
            // Construimos la URL del endpoint
            $erpUrlMethod = $this->erpEndpoint . $endpointName;  

            $url = "{$erpUrlMethod}?rutEmpresa={$this->rutEmpresa}&token={$this->erpToken}{$additionalParams}";

            $response = @file_get_contents($url);
            if ($response === false) {
                $this->logMessage("Error al conectarse al endpoint '$endpointName' del ERP.", 'sync_error.txt');
                return false;
            }

            return $this->cleanXmlData($response);
        } catch (Exception $e) {
            $this->logMessage("Excepción en fetchErpData: " . $e->getMessage(), 'sync_error.txt');
            return false;
        }
    }
}
