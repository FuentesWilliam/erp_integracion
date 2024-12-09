<?php

class AdminLogsClientesController extends ModuleAdminController
{

	public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();
        $this->context->smarty->assign([]);
        $this->setTemplate('test.tpl'); // Asegúrate de que este archivo exista en views/templates/admin/
    }
}