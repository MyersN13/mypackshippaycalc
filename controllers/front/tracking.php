<?php
class EasyPostShippingTrackingModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        $tracking_code = Tools::getValue('tracking');
        $shipment = Db::getInstance()->getRow('
            SELECT s.*, o.reference
            FROM '._DB_PREFIX_.'easypost_shipments s
            LEFT JOIN '._DB_PREFIX_.'orders o ON (s.id_order = o.id_order)
            WHERE s.tracking_code = "'.pSQL($tracking_code).'"
        ');
        
        if (!$shipment) {
            $this->errors[] = $this->module->l('Tracking number not found');
            $this->setTemplate('module:easypostshipping/views/templates/front/error.tpl');
            return;
        }
        
        try {
            $tracking_info = $this->module->easypost->trackShipment($tracking_code);
            
            $this->context->smarty->assign([
                'tracking' => $tracking_info,
                'tracking_code' => $tracking_code,
                'order_reference' => $shipment['reference'],
                'carrier' => $tracking_info['carrier'] ?? 'Unknown',
                'status' => $tracking_info['status'] ?? null,
                'estimated_delivery' => $tracking_info['est_delivery_date'] ?? null,
                'history' => $tracking_info['tracking_details'] ?? [],
                'public_url' => $tracking_info['public_url'] ?? '#'
            ]);
            
            $this->setTemplate('module:easypostshipping/views/templates/front/tracking.tpl');
            
        } catch (Exception $e) {
            $this->errors[] = $this->module->l('Unable to fetch tracking information');
            $this->setTemplate('module:easypostshipping/views/templates/front/error.tpl');
        }
    }
}