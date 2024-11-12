<?php

abstract class ErpSyncBase
{
    protected $erpEndpoint;
    protected $rutEmpresa;
    protected $erpToken;

    public function __construct()
    {
        // Configuración obtenida desde la base de datos
        $rutEmpresa = Configuration::get('ERP_MANAGER_RUT');
        $erpToken = Configuration::get('ERP_MANAGER_TOKEN');
        $erpUrl = Configuration::get('ERP_MANAGER_ENDPOINT');
    }

    // Método para escribir en el log
    protected function logMessage($message)
    {
        $logFile = _PS_MODULE_DIR_ . 'mymodule/logs/sync_log.txt';
        $date = new DateTime();
        file_put_contents($logFile, $date->format('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    }

    // Método para limpiar y preparar el XML
    protected function cleanXmlData($response)
    {
        $lines = explode("\n", $response);
        if (!isset($lines[5])) {
            $this->logMessage("Error: La línea 5 no existe en la respuesta del ERP.");
            return false;
        }

        $decodedXml = html_entity_decode(trim($lines[5]));
        $decodedXml = preg_replace('/^<Data>/', '', $decodedXml);
        $decodedXml = preg_replace('/<\/Data>$/', '', $decodedXml);
        $decodedXml = preg_replace('/[\x00-\x1F\x7F]/', '', $decodedXml);

        if (empty($decodedXml)) {
            $this->logMessage("Error: El contenido XML está vacío después de la limpieza.");
            return false;
        }

        return $decodedXml;
    }

    // Método común para obtener datos de un endpoint específico del ERP
    protected function fetchErpData($endpointName, $additionalParams = "")
    {
        // Construimos la URL del endpoint
        $erpUrlMethod = $this->$erpUrl . $endpointName;  

        $url = "{$erpUrlMethod}?rutEmpresa={$this->rutEmpresa}&token={$this->erpToken}{$additionalParams}";

        $response = @file_get_contents($url);
        if ($response === false) {
            $this->logMessage("Error al conectarse al endpoint del ERP.");
            return false;
        }

        return $this->cleanXmlData($response);
    }
}
