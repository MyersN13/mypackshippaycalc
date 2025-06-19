<?php
class AdminEasyPostBulkPrintController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }
    
    public function initContent()
    {
        parent::initContent();
        
        if (Tools::isSubmit('submitBulkPrint')) {
            $order_ids = Tools::getValue('order_ids');
            if (!empty($order_ids)) {
                $this->module->bulkPrintLabels($order_ids);
            }
        }
        
        $orders = Order::getOrdersWithInformations();
        $orders_without_labels = [];
        
        foreach ($orders as $order) {
            $shipment = $this->module->getShipmentByOrder($order['id_order']);
            if (!$shipment) {
                $orders_without_labels[] = $order;
            }
        }
        
        $this->context->smarty->assign([
            'orders' => $orders_without_labels,
            'module_dir' => $this->module->_path,
        ]);
        
        $this->content = $this->context->smarty->fetch($this->module->local_path.'views/templates/admin/bulk_print.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}