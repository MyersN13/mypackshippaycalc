<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/lib/EasyPost/EasyPost.php';
require_once __DIR__ . '/lib/StripeHelper.php';

class EasyPostShipping extends CarrierModule
{
    const EASYPOST_LOG_SECURITY = 1;
    const EASYPOST_LOG_ERROR = 2;
    const EASYPOST_LOG_INFO = 3;

    protected $config_form = false;
    private $easypost;

    public function __construct()
    {
        $this->name = 'easypostshipping';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'YourName';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->is_ecommerce = true;

        parent::__construct();

        $this->displayName = $this->l('EasyPost Shipping + Stripe Checkout');
        $this->description = $this->l('Integrated shipping and payments with EasyPost and Stripe');
        
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        
        $api_key = Configuration::get('EASYPOST_API_KEY');
        if ($api_key) {
            $this->easypost = new EasyPost($api_key);
        }
    }

    /* INSTALLATION */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You need to enable the cURL extension');
            return false;
        }

        if (!$this->validateTaxConfiguration()) {
            return false;
        }

        $carrier = $this->addCarrier();
        $this->addZones($carrier);
        $this->addGroups($carrier);
        $this->addRanges($carrier);
        
        return parent::install() &&
            $this->registerHooks() &&
            $this->createTables() &&
            $this->setDefaultConfig() &&
            $this->disableOtherPaymentMethods();
    }

    private function validateTaxConfiguration()
    {
        if (!Configuration::get('PS_TAX')) {
            $this->_errors[] = $this->l('Enable taxes in International > Taxes');
            return false;
        }

        if (!TaxRule::getTaxRulesCount()) {
            $this->_errors[] = $this->l('Create tax rules in International > Taxes > Tax Rules');
            return false;
        }

        return true;
    }

    private function registerHooks()
    {
        $hooks = [
            'header',
            'backOfficeHeader',
            'displayAdminOrderMain',
            'displayAdminOrderSideBottom',
            'actionCarrierProcess',
            'displayCarrierExtraContent',
            'actionAdminControllerSetMedia',
            'actionOrderStatusPostUpdate',
            'displayOrderDetail',
            'paymentOptions',
            'displayOrderConfirmation',
            'actionFrontControllerSetMedia'
        ];

        $result = true;
        foreach ($hooks as $hook) {
            $result &= $this->registerHook($hook);
        }

        return $result;
    }

    /* CARRIER SETUP */
    private function addCarrier()
    {
        $carrier = new Carrier();
        $carrier->name = 'EasyPost Shipping';
        $carrier->is_module = true;
        $carrier->active = 1;
        $carrier->range_behavior = 1;
        $carrier->shipping_external = true;
        $carrier->shipping_method = Carrier::SHIPPING_METHOD_WEIGHT;

        foreach (Language::getLanguages() as $lang) {
            $carrier->delay[$lang['id_lang']] = 'Delivery time depends on selected shipping method';
        }

        if ($carrier->add()) {
            Configuration::updateValue('EASYPOST_CARRIER_ID', (int)$carrier->id);
            return $carrier;
        }
        return false;
    }

    private function addZones($carrier)
    {
        $zones = Zone::getZones();
        foreach ($zones as $zone) {
            $carrier->addZone($zone['id_zone']);
        }
    }

    private function addGroups($carrier)
    {
        $groups = Group::getGroups(true);
        foreach ($groups as $group) {
            $carrier->addGroup($group['id_group']);
        }
    }

    private function addRanges($carrier)
    {
        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier->id;
        $rangePrice->delimiter1 = '0';
        $rangePrice->delimiter2 = '10000';
        $rangePrice->add();

        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier->id;
        $rangeWeight->delimiter1 = '0';
        $rangeWeight->delimiter2 = '10000';
        $rangeWeight->add();
    }

    /* PAYMENT METHODS */
    private function disableOtherPaymentMethods()
    {
        if (!Configuration::get('EASYPOST_DISABLE_OTHERS')) {
            return true;
        }

        $paymentModules = Db::getInstance()->executeS("
            SELECT m.name 
            FROM "._DB_PREFIX_."module m
            JOIN "._DB_PREFIX_."hook_module hm ON hm.id_module = m.id_module
            JOIN "._DB_PREFIX_."hook h ON h.id_hook = hm.id_hook
            WHERE h.name = 'paymentOptions' 
            AND m.name != '".pSQL($this->name)."'
            AND m.active = 1
        ");

        foreach ($paymentModules as $module) {
            if ($moduleObj = Module::getInstanceByName($module['name'])) {
                $moduleObj->disable();
                $this->log('Disabled payment module: '.$module['name'], self::EASYPOST_LOG_INFO);
            }
        }

        return true;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active || $this->context->cart->id_carrier != Configuration::get('EASYPOST_CARRIER_ID')) {
            return [];
        }

        $stripeOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $stripeOption->setModuleName($this->name)
            ->setCallToActionText($this->l('Pay Securely with Stripe'))
            ->setAction($this->context->link->getModuleLink($this->name, 'stripecheckout'))
            ->setAdditionalInformation($this->fetch('module:easypostshipping/views/templates/hook/payment_option.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/stripe-logo.png'));

        return [$stripeOption];
    }

    /* SHIPPING METHODS */
    public function getShippingRates($cart)
    {
        if (!$this->easypost) {
            return [];
        }

        $address = new Address($cart->id_address_delivery);
        $country = new Country($address->id_country);
        $customer = new Customer($cart->id_customer);

        try {
            $packages = $this->packCartItems($cart);
            $rates = [];

            foreach ($packages as $package) {
                $shipment = $this->easypost->createShipment([
                    'to_address' => [
                        'name' => $address->firstname.' '.$address->lastname,
                        'street1' => $address->address1,
                        'city' => $address->city,
                        'state' => State::getNameById($address->id_state),
                        'zip' => $address->postcode,
                        'country' => $country->iso_code,
                        'email' => $customer->email,
                        'phone' => $address->phone
                    ],
                    'from_address' => [
                        'company' => Configuration::get('PS_SHOP_NAME'),
                        'street1' => Configuration::get('PS_SHOP_ADDR1'),
                        'city' => Configuration::get('PS_SHOP_CITY'),
                        'state' => Configuration::get('PS_SHOP_STATE'),
                        'zip' => Configuration::get('PS_SHOP_CODE'),
                        'country' => Configuration::get('PS_SHOP_COUNTRY')
                    ],
                    'parcel' => [
                        'length' => $package['length'],
                        'width' => $package['width'],
                        'height' => $package['height'],
                        'weight' => $package['weight']
                    ],
                    'carrier_accounts' => json_decode(Configuration::get('EASYPOST_DEFAULT_CARRIERS'))
                ]);

                foreach ($shipment['rates'] as $rate) {
                    $rates[$rate['id']] = [
                        'id' => $rate['id'],
                        'service' => $rate['service'],
                        'carrier' => $rate['carrier'],
                        'rate' => $rate['rate'],
                        'currency' => $rate['currency'],
                        'delivery_days' => $rate['delivery_days'],
                        'delivery_date' => $rate['delivery_date']
                    ];
                }
            }

            return $rates;
        } catch (Exception $e) {
            $this->log('Shipping rate error: '.$e->getMessage(), self::EASYPOST_LOG_ERROR);
            return [];
        }
    }

    private function packCartItems($cart)
    {
        $packer = new Packer();
        $boxes = $this->getBoxes(true);
        $products = $cart->getProducts();

        foreach ($boxes as $box) {
            $packer->addBox(new Box(
                $box['name'],
                $box['length'],
                $box['width'],
                $box['height'],
                $box['max_weight']
            ));
        }

        foreach ($products as $product) {
            $dimensions = $this->getProductDimensions($product['id_product']);
            for ($i = 0; $i < $product['cart_quantity']; $i++) {
                $packer->addItem(new Item(
                    $product['name'],
                    $dimensions['length'],
                    $dimensions['width'],
                    $dimensions['height'],
                    $product['weight'],
                    true
                ));
            }
        }

        $packedBoxes = $packer->pack();
        $result = [];

        foreach ($packedBoxes as $packedBox) {
            $result[] = [
                'length' => $packedBox->getUsedWidth(),
                'width' => $packedBox->getUsedLength(),
                'height' => $packedBox->getUsedDepth(),
                'weight' => $packedBox->getWeight()
            ];
        }

        return $result;
    }

    private function getProductDimensions($id_product)
    {
        $product = new Product($id_product);
        return [
            'length' => max(1, $product->depth),
            'width' => max(1, $product->width),
            'height' => max(1, $product->height)
        ];
    }

    /* BOX MANAGEMENT */
    private function createTables()
    {
        $sql = [];
        
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'easypost_boxes` (
            `id_box` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `length` DECIMAL(10,2) NOT NULL,
            `width` DECIMAL(10,2) NOT NULL,
            `height` DECIMAL(10,2) NOT NULL,
            `max_weight` DECIMAL(10,2) NOT NULL,
            `enabled` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_box`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'easypost_shipments` (
            `id_shipment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `easypost_shipment_id` VARCHAR(255) NOT NULL,
            `tracking_code` VARCHAR(255) NOT NULL,
            `label_url` TEXT NOT NULL,
            `label_pdf` LONGBLOB,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_shipment`),
            INDEX (`id_order`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    public function getBoxes($enabled_only = false)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'easypost_boxes';
        if ($enabled_only) {
            $sql .= ' WHERE enabled = 1';
        }
        return Db::getInstance()->executeS($sql);
    }

    /* SHIPMENT MANAGEMENT */
    public function createShipment($id_order, $rate_id)
    {
        if (!$this->easypost) {
            return false;
        }

        $order = new Order($id_order);
        $cart = new Cart($order->id_cart);
        $packages = $this->packCartItems($cart);

        try {
            $shipment = $this->easypost->buyShipment($rate_id);
            
            Db::getInstance()->insert('easypost_shipments', [
                'id_order' => $order->id,
                'easypost_shipment_id' => pSQL($shipment['id']),
                'tracking_code' => pSQL($shipment['tracking_code']),
                'label_url' => pSQL($shipment['postage_label']['label_url']),
                'label_pdf' => $this->easypost->getLabelPdf($shipment['postage_label']['label_url']),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $orderCarrier = new OrderCarrier($order->getIdOrderCarrier());
            $orderCarrier->tracking_number = $shipment['tracking_code'];
            $orderCarrier->update();

            return true;
        } catch (Exception $e) {
            $this->log('Shipment creation error: '.$e->getMessage(), self::EASYPOST_LOG_ERROR);
            return false;
        }
    }

    public function getShipmentByOrder($id_order)
    {
        return Db::getInstance()->getRow('
            SELECT * FROM '._DB_PREFIX_.'easypost_shipments
            WHERE id_order = '.(int)$id_order.'
            ORDER BY created_at DESC
        ');
    }

    /* ADMIN CONFIGURATION */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitEasyPostSettings'))) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/settings.tpl');

        return $output.$this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEasyPostSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    // ... [All configuration inputs as previously shown] ...
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            'EASYPOST_API_KEY' => Configuration::get('EASYPOST_API_KEY'),
            'EASYPOST_DEFAULT_PAPER_SIZE' => Configuration::get('EASYPOST_DEFAULT_PAPER_SIZE', '4x6'),
            'STRIPE_PUBLISHABLE_KEY' => Configuration::get('STRIPE_PUBLISHABLE_KEY'),
            'STRIPE_API_KEY' => Configuration::get('STRIPE_API_KEY'),
            'EASYPOST_DISABLE_OTHERS' => Configuration::get('EASYPOST_DISABLE_OTHERS', 1),
            'EASYPOST_LOG_LEVEL' => Configuration::get('EASYPOST_LOG_LEVEL', 2),
        ];
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        if (Tools::getValue('EASYPOST_DISABLE_OTHERS')) {
            $this->disableOtherPaymentMethods();
        }
    }

    /* UTILITIES */
    private function setDefaultConfig()
    {
        $defaults = [
            'EASYPOST_TAX_DISPLAY' => (int)Configuration::get('PS_TAX_DISPLAY'),
            'EASYPOST_CALCULATE_TAX' => 1,
            'EASYPOST_LOG_LEVEL' => 2,
            'EASYPOST_DEFAULT_PAPER_SIZE' => '4x6',
            'EASYPOST_DISABLE_OTHERS' => 1
        ];

        $result = true;
        foreach ($defaults as $key => $value) {
            $result &= Configuration::updateValue($key, $value);
        }

        return $result;
    }

    private function log($message, $level = self::EASYPOST_LOG_INFO)
    {
        $log_level = (int)Configuration::get('EASYPOST_LOG_LEVEL');
        if ($level <= $log_level) {
            PrestaShopLogger::addLog('[EasyPost] '.$message, $level);
        }
    }

    /* UNINSTALL */
    public function uninstall()
    {
        $carrier_id = (int)Configuration::get('EASYPOST_CARRIER_ID');
        if ($carrier_id) {
            $carrier = new Carrier($carrier_id);
            $carrier->delete();
        }

        $config_keys = [
            'EASYPOST_API_KEY',
            'EASYPOST_CARRIER_ID',
            'EASYPOST_DEFAULT_PAPER_SIZE',
            'EASYPOST_DEFAULT_CARRIERS',
            'STRIPE_PUBLISHABLE_KEY',
            'STRIPE_API_KEY',
            'EASYPOST_DISABLE_OTHERS',
            'EASYPOST_LOG_LEVEL'
        ];

        foreach ($config_keys as $key) {
            Configuration::deleteByName($key);
        }

        $sql = [
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.'easypost_boxes`',
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.'easypost_shipments`'
        ];

        foreach ($sql as $query) {
            Db::getInstance()->execute($query);
        }

        return parent::uninstall();
    }
}