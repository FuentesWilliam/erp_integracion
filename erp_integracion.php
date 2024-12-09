<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

//require_once __DIR__ . '/src/services/ClientSyncService.php';
//require_once __DIR__ . '/src/utils/Logger.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class ERP_integracion extends Module
{
    public function __construct()
    {
        $this->name = 'erp_integracion';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'VRWEB';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Integración ERP Manager');
        $this->description = $this->l('Módulo para sincronización de stock, precios, clientes y pedidos con ERP.');

        $this->confirmUninstall = $this->l('¿Está seguro de que desea desinstalar este módulo?');
        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_
        );
    }

    public function install()
    {

        if (!parent::install()) {
            return false;
        }

        // Configuración inicial durante la instalación
        $configOk = Configuration::updateValue('ERP_RUT', '') &&
                    Configuration::updateValue('ERP_TOKEN', '') &&
                    Configuration::updateValue('ERP_ENDPOINT_COMPRAS', '') &&
                    Configuration::updateValue('ERP_ENDPOINT_VENTAS', '');
        
        if (!$configOk) {
            return false;
        }

        // Obtener el ID del tab padre "Improve" (Mejoras)
        $parentId = Tab::getIdFromClassName('IMPROVE');
        if (!$this->installTab('AdminSyncStockPrices', 'ERP Manager', $parentId)) {
            return false;
        }

        // Crear los tabs hijos bajo "ERP Manager"
        $erpTabId = Tab::getIdFromClassName('AdminSyncStockPrices');
        $tabsOk = $this->installTab($this->name, 'Configuración del manager', $erpTabId) &&
                  $this->installTab('AdminSyncStockPrices', 'Sincronización Stock y Precios', $erpTabId) &&
                  $this->installTab('AdminLogsClientes', 'Logs Clientes', $erpTabId) &&
                  $this->installTab('AdminLogsFacturas', 'Logs Facturas', $erpTabId) &&
                  $this->installTab('AdminLogError', 'Log Error', $erpTabId);

        if (!$tabsOk) {
            return false;
        }

        // Registrar hooks
        $hooksOk = $this->registerHook('actionCustomerAccountAdd') &&
                   $this->registerHook('actionObjectAddressAddAfter');
        if (!$hooksOk) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // Eliminar configuraciones
        $configsDeleted = Configuration::deleteByName('ERP_RUT') &&
                          Configuration::deleteByName('ERP_TOKEN') &&
                          Configuration::deleteByName('ERP_ENDPOINT_COMPRAS') &&
                          Configuration::deleteByName('ERP_ENDPOINT_VENTAS');

        if (!$configsDeleted) {
            return false;
        }

        // Eliminar tabs hijos primero
        $tabsDeleted = $this->uninstallTab('AdminSyncStockPrices') &&
                       $this->uninstallTab('AdminLogsClientes') &&
                       $this->uninstallTab('AdminLogsFacturas') &&
                       $this->uninstallTab('AdminLogError') &&
                       $this->uninstallTab('AdminErpManager') &&
                       $this->uninstallTab($this->name); // Tab principal del módulo

        if (!$tabsDeleted) {
            return false;
        }

        return parent::uninstall();
    }

    public function installTab($className, $tabName, $parentId = 0)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className; // Si no es controlador, dejar class_name vacío
        $tab->name = array();

        // Añadir traducciones para cada idioma instalado
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }

        $tab->id_parent = (int)$parentId;
        $tab->module = $this->name;

        // Guardar el tab en la base de datos
        return $tab->add();
    }

    public function uninstallTab($className)
    {
        $idTab = Tab::getIdFromClassName($className);
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return true; // Si no encuentra el tab, devuelve true para evitar errores
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitERP_integracionModule')) == true) {
            $this->postProcess();
        }

        // Cargar los valores actuales de configuración para asignarlos a Smarty
        $fields_value = array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Configuración del ERP Manager'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Introduzca un RUT válido'),
                        'name' => 'ERP_RUT',
                        'label' => $this->l('Rut Empresa'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Introduzca un token válido'),
                        'name' => 'ERP_TOKEN',
                        'label' => $this->l('Token'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-link"></i>',
                        'desc' => $this->l('Introduzca una URL válida'),
                        'name' => 'ERP_ENDPOINT_COMPRAS',
                        'label' => $this->l('ERP'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-link"></i>',
                        'desc' => $this->l('Introduzca una URL válida'),
                        'name' => 'ERP_ENDPOINT_VENTAS',
                        'label' => $this->l('ERP VENTAS'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                ),
            ),
        );

        // Asignar URLs y otros valores a Smarty
        $this->context->smarty->assign([
            'module' => $this,
            'fields_value' => $fields_value
        ]);

        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitERP_integracionModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Configuración del ERP Manager'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Introduzca un RUT válido'),
                        'name' => 'ERP_RUT',
                        'label' => $this->l('Rut Empresa'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Introduzca un token válido'),
                        'name' => 'ERP_TOKEN',
                        'label' => $this->l('Token'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-link"></i>',
                        'desc' => $this->l('Introduzca una URL válida'),
                        'name' => 'ERP_ENDPOINT_COMPRAS',
                        'label' => $this->l('ERP'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-link"></i>',
                        'desc' => $this->l('Introduzca una URL válida'),
                        'name' => 'ERP_ENDPOINT_VENTAS',
                        'label' => $this->l('ERP VENTAS'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'ERP_RUT' => Configuration::get('ERP_RUT', ''),
            'ERP_TOKEN' => Configuration::get('ERP_TOKEN', ''),
            'ERP_ENDPOINT_COMPRAS' => Configuration::get('ERP_ENDPOINT_COMPRAS', ''),
            'ERP_ENDPOINT_VENTAS' => Configuration::get('ERP_ENDPOINT_VENTAS', ''),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Añadir los archivos CSS y JavaScript en el Back Office.
     */
    public function hookDisplayBackOfficeHeader()
    {   
        // Solo carga el CSS si estás en la configuración del módulo
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookActionCustomerAccountAdd($params)
    {
        // Obtenemos el ID del cliente recién creado
        $data = $params['newCustomer'];
        $customerId = $data->id;
        
        // Consulta SQL para recuperar el cliente y sus datos personalizados
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'customer WHERE id_customer = ' . (int)$customerId;
        $customer = Db::getInstance()->getRow($sql);

        $clientData = [
            'rut' => $customer['rut'],
            'nombre' => $customer['firstname'] .' '. $customer['lastname'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'giro' => $customer['company_business_activity'],
            'dir' => '',
            'comuna' => '',
            'ciudad' => '',
            'dirDespacho' => '',
            'comunaDespacho' => '',
            'ciudadDespacho' => '',
            'fono' => ''
        ];
        
        // Sincronizar con el ERP
        try {
            $syncService = new ClientSyncService();
            $result = $syncService->addClient($clientData);

            if (!$result) {
                Logger::logError("Error al sincronizar cliente ID: $customerId. Respuesta del ERP: Falló la sincronización.");
            }

        } catch (Exception $e) {
            Logger::logError("Excepción al sincronizar cliente ID: $customerId. Detalles: " . $e->getMessage());
        }

    }

    public function hookActionObjectAddressAddAfter($params)
    {
        // $data = $params['object']; // Dirección recién agregada
        // $customerId = $data->id;

        // // Consulta SQL para recuperar el cliente y sus datos personalizados
        // $sql = 'SELECT c.id_customer, c.rut, c.firstname AS nombre, c.lastname  AS apellido, c.email, c.phone AS telefono, c.company_business_activity as giro,
        // a.address1 as dir, a.city as comuna
        // FROM ' . _DB_PREFIX_ . 'customer c
        // INNER JOIN '. _DB_PREFIX_ .'address a ON c.id_customer = a.id_customer
        // WHERE a.id_address = ' . (int)$customerId;
        // $customer = Db::getInstance()->getRow($sql);

        // $clientData = [
        //     'rut' => $customer['rut'],
        //     'nombre' => $customer['nombre'] .' '. $customer['apellido'],
        //     'email' => $customer['email'],
        //     'phone' => $customer['telefono'],
        //     'giro' => $customer['giro'],
        //     'dir' => $customer['dir'],
        //     'comuna' => $customer['comuna'],
        //     'ciudad' => '',
        //     'dirDespacho' => $customer['dir'],
        //     'comunaDespacho' => $customer['comuna'],
        //     'ciudadDespacho' => '',
        //     'fono' => ''
        // ];

        // // Sincronizar con el ERP
        // try {
        //     $syncService = new ClientSyncService();
        //     $result = $syncService->addClient($clientData);

        //     if (!$result) {
        //         Logger::logError("Error al sincronizar cliente ID: $customerId. Respuesta del ERP: Falló la sincronización.");
        //     }

        // } catch (Exception $e) {
        //     Logger::logError("Excepción al sincronizar cliente ID: $customerId. Detalles: " . $e->getMessage());
        // }
    }

}