<?php
class AdminEasyPostSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'easypost_boxes';
        $this->identifier = 'id_box';
        $this->className = 'EasyPostBox';
        $this->lang = false;
        
        parent::__construct();
        
        $this->fields_list = [
            'id_box' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Name'),
            ],
            'length' => [
                'title' => $this->l('Length (in)'),
                'suffix' => 'in',
                'align' => 'right',
            ],
            'width' => [
                'title' => $this->l('Width (in)'),
                'suffix' => 'in',
                'align' => 'right',
            ],
            'height' => [
                'title' => $this->l('Height (in)'),
                'suffix' => 'in',
                'align' => 'right',
            ],
            'max_weight' => [
                'title' => $this->l('Max Weight (lbs)'),
                'suffix' => 'lbs',
                'align' => 'right',
            ],
            'enabled' => [
                'title' => $this->l('Enabled'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
            ],
        ];
        
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?')
            ],
            'enable' => [
                'text' => $this->l('Enable selection'),
                'confirm' => $this->l('Enable selected items?')
            ],
            'disable' => [
                'text' => $this->l('Disable selection'),
                'confirm' => $this->l('Disable selected items?')
            ],
        ];
    }
    
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        
        return parent::renderList();
    }
    
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Box Configuration'),
                'icon' => 'icon-cog'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Box Name'),
                    'name' => 'name',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Length (inches)'),
                    'name' => 'length',
                    'required' => true,
                    'col' => '2',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Width (inches)'),
                    'name' => 'width',
                    'required' => true,
                    'col' => '2',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Height (inches)'),
                    'name' => 'height',
                    'required' => true,
                    'col' => '2',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Maximum Weight (lbs)'),
                    'name' => 'max_weight',
                    'required' => true,
                    'col' => '2',
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Enabled'),
                    'name' => 'enabled',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        ]
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ]
        ];
        
        return parent::renderForm();
    }
    
    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['new_box'] = [
            'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
            'desc' => $this->l('Add new box'),
            'icon' => 'process-icon-new'
        ];
        
        parent::initPageHeaderToolbar();
    }
}