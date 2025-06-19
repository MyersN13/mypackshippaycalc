<?php
class EasyPostShippingRatesModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        if (!Tools::getValue('ajax')) {
            die(json_encode(['error' => 'Invalid request']));
        }

        try {
            $cart = new Cart(Tools::getValue('id_cart'));
            if (!Validate::isLoadedObject($cart)) {
                throw new Exception('Invalid cart');
            }

            $rates = $this->module->getShippingRates($cart);
            
            if (empty($rates)) {
                throw new Exception('No rates available');
            }

            die(json_encode([
                'success' => true,
                'rates' => $rates
            ]));
            
        } catch (Exception $e) {
            $this->module->log('Rate error: '.$e->getMessage(), EASYPOST_LOG_ERROR);
            die(json_encode([
                'error' => $this->module->l('Error calculating shipping rates')
            ]));
        }
    }
}