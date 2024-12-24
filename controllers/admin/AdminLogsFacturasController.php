<?php
require_once _PS_MODULE_DIR_ . '/erp_integracion/src/utils/Logger.php';

class AdminLogsFacturasController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();
        // Recuperar log_date de la URL o de la cookie
        $date = Tools::getValue('log_date', $this->context->cookie->__get('log_date', date('Y-m-d')));
        $this->context->cookie->__set('log_date', $date); // Asegurar que la cookie esté sincronizada

        // Obtener los logs
        $logs = Logger::getLogs($date);
        $filteredLogs = [
            'data' => [],
        ];

        foreach ($logs as $log) {
            if (strpos($log['message'], 'factura') !== false) {
                $filteredLogs['data'][] = $log;
            }
        }

        // Asignar datos a Smarty
        $actionUrl = $this->context->link->getAdminLink('AdminLogsFacturas', true, [], ['log_date' => $date]);
        $this->context->smarty->assign([
            'titulo' => "Factura",
            'logs' => $filteredLogs,
            'date' => $date,
            'current_url' => $actionUrl,
        ]);
        ## erp_integracion/views/templates/admin/logs.tpl'
        $this->setTemplate('logs.tpl');
    }

    public function postProcess()
    {
        // Obtener la fecha enviada en el formulario
        $date = Tools::getValue('log_date', date('Y-m-d'));

        // Verificar si la fecha en la cookie es diferente a la enviada
        if ($date && $this->context->cookie->__get('log_date') !== $date) {
            // Guardar la nueva fecha en la cookie
            $this->context->cookie->__set('log_date', $date);

            // Redirigir para recargar la página con la nueva fecha en la URL
            Tools::redirectAdmin(
                $this->context->link->getAdminLink('AdminLogsFacturas', true, [], ['log_date' => $date])
            );
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        // Agregar CSS
        $this->addCSS($this->module->getPathUri() . 'views/css/logs.css');

        // Agregar JavaScript (si lo necesitas)
        //$this->addJS($this->module->getPathUri() . 'views/js/logs.js');
    }
}