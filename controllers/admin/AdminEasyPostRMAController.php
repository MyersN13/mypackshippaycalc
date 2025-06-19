<?php
class AdminEasyPostRMAController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }
    
    public function initContent()
    {
        parent::initContent();
        
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);
        
        if (Tools::isSubmit('submitCreateRMA')) {
            if ($this->module->createRma($order->id)) {
                $this->confirmations[] = $this->module->l('RMA created successfully');
            } else {
                $this->errors[] = $this->module->l('Failed to create RMA');
            }
        }
        
        $rmas = $this->module->getOrderRmas($order->id);
        
        $this->context->smarty->assign([
            'order' => $order,
            'rmas' => $rmas,
            'module_dir' => $this->module->_path,
        ]);
        
        $this->content = $this->context->smarty->fetch($this->module->local_path.'views/templates/admin/rma.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}