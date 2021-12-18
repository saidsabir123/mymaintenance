<?php
/**
* 2007-2021 PrestaShop
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
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mymaintenance extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'mymaintenance';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Sabir';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('mymaintenance');
        $this->description = $this->l('add ip to ips mainteannce');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('MYMAINTENANCE_LIVE_MODE', false);
        Configuration::updateValue('MYMAINTENANCE_PARAM_NAME', "myip");
        Configuration::updateValue('MYMAINTENANCE_PARAM_VALUE', 1);

        return parent::install() &&
            $this->registerHook('displayMaintenance') ;
    }

    public function uninstall()
    {
        Configuration::deleteByName('MYMAINTENANCE_LIVE_MODE');
        Configuration::deleteByName('MYMAINTENANCE_PARAM_NAME');
        Configuration::deleteByName('MYMAINTENANCE_PARAM_VALUE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitMymaintenanceModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

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
        $helper->submit_action = 'submitMymaintenanceModule';
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
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter Parameter name'),
                        'name' => 'MYMAINTENANCE_PARAM_NAME',
                        'label' => $this->l('PARAM NAME'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'MYMAINTENANCE_PARAM_VALUE',
                        'label' => $this->l('PARAM VALUE'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'MYMAINTENANCE_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
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
            'MYMAINTENANCE_LIVE_MODE' => Configuration::get('MYMAINTENANCE_LIVE_MODE'),
            'MYMAINTENANCE_PARAM_NAME' => Configuration::get('MYMAINTENANCE_PARAM_NAME'),
            'MYMAINTENANCE_PARAM_VALUE' => Configuration::get('MYMAINTENANCE_PARAM_VALUE'),
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
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayMaintenance()
    {
        $lastIPS = Configuration::get('PS_MAINTENANCE_IP');
        $arrayLastIPS = explode(',', $lastIPS);
        $param_name = Configuration::get('MYMAINTENANCE_PARAM_NAME');
        $param_value = Configuration::get('MYMAINTENANCE_PARAM_VALUE');
        $live_mode = Configuration::get('MYMAINTENANCE_LIVE_MODE');
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if(!Configuration::get('PS_SHOP_ENABLE') && !in_array($ip, $arrayLastIPS) && $live_mode && Tools::getValue($param_name) && Tools::getValue($param_name) == $param_value) {
            if(!empty($lastIPS)) {
                $lastIPS .= ','.$ip;
            } else {
                $lastIPS = $ip;
            }
            Configuration::updateValue('PS_MAINTENANCE_IP', $lastIPS);
            Tools::clearCache();
            Tools::redirect('/');
        }
    }

    
}
