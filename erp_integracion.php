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
        // Configuración inicial durante la instalación
        Configuration::updateValue('ERP_RUT', '');
        Configuration::updateValue('ERP_TOKEN', '');
        Configuration::updateValue('ERP_ENDPOINT_COMPRAS', '');
        Configuration::updateValue('ERP_ENDPOINT_VENTAS', '');

        return parent::install() &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('actionObjectAddressAddAfter');
    }

    public function uninstall()
    {
        Configuration::deleteByName('ERP_RUT');
        Configuration::deleteByName('ERP_TOKEN');
        Configuration::deleteByName('ERP_ENDPOINT_COMPRAS');
        Configuration::deleteByName('ERP_ENDPOINT_VENTAS');
        return parent::uninstall();
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
        $fields_value = $this->getConfigFormValues();

        // Generar URLs para cada acción del controlador AdminSync
        $syncInventoryUrl = $this->context->link->getAdminLink('AdminERPIntegracionSync', true) . '&action=syncInventory';
        $syncSalesUrl = $this->context->link->getAdminLink('AdminERPIntegracionSync', true) . '&action=syncSales';

        // Asignar URLs y otros valores a Smarty
        $this->context->smarty->assign([
            'module' => $this,
            'fields_value', $fields_value,
            'sync_inventory_url' => $syncInventoryUrl,
            'sync_sales_url' => $syncSalesUrl,
        ]);

        // Cargar la plantilla de navegación
        $navigation = $this->context->smarty->fetch(
            $this->local_path . 'views/templates/admin/navigation.tpl');

        return $navigation . $this->renderForm();
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
        // // Obtenemos el ID del cliente recién creado
        // $data = $params['newCustomer'];
        // $customerId = $data->id;

        // // Consulta SQL para recuperar el cliente y sus datos personalizados
        // $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'customer WHERE id_customer = ' . (int)$customerId;
        // $customerData = Db::getInstance()->getRow($sql);

        // if ($customerData) {
        //     // Maneja los datos del cliente
        //     $logDir = _PS_MODULE_DIR_ . 'erp_integracion/logs/';    
        //     $logFile = $logDir . 'sync_test.txt';
        //     $date = new DateTime();
        //     file_put_contents($logFile, $date->format('Y-m-d H:i:s') . " - " . print_r($customerData, true) . "\n", FILE_APPEND);

        //     // Ejemplo: enviar datos al ERP
        //     $syncService = new SyncService();
        //     $syncService->syncAddClient([
        //         'rut' => $customerData['rut'],
        //         'firstname' => $customerData['firstname'],
        //         'lastname' => $customerData['lastname'],
        //         'email' => $customerData['email'],
        //         'phone' => $customerData['phone'],
        //         'giro' => $customerData['company_business_activity']
        //     ]);
        // }
    }

    public function hookActionObjectAddressAddAfter($params)
    {
        // $address = $params['object']; // Dirección recién agregada
        // $customer = new Customer($address->id_customer);

        // // Preparar datos para el ERP
        // $customerData = [
        //     'rut' => $customer->rut, // Asegúrate de asignar un campo RUT aquí.
        //     'firstname' => $customer->firstname,
        //     'lastname' => $customer->lastname,
        //     'address' => $address->address1,
        //     'comuna' => $address->postcode,
        //     'city' => $address->city,
        //     'address_delivery' => $address->address1, // Igual que address en este caso.
        //     'comuna_delivery' => $address->postcode,
        //     'city_delivery' => $address->city,
        //     'email' => $customer->email,
        //     'phone' => $customer->phone,
        //     'giro' => $customer->company_business_activity
        // ];

        // // Instancia de SyncService y envío de datos
        // $syncService = new SyncService();
        // $syncService->addClient($customerData);
    }

}