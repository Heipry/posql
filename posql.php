<?php
/**
* 2007-2022 PrestaShop
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
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Posql extends Module
{
    protected $config_form = false;
    public function __construct()
    {
        $this->name = 'posql';
        $this->tab = 'administration';
        $this->version = '1.0.1';
        $this->author = 'Proyecto Online';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('SQL calls');
        $this->description = $this->l('Easy SQL queries between dates');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        $this->installController('AdminPoSql', 'POSql');
        return parent::install() &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        $this->uninstallController('AdminPoSql');
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
        if (((bool)Tools::isSubmit('submitPosqlModule')) == true) {
            $this->postProcess();
        }
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign("sql", Configuration::get('POSQL_QUERY')); 
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');        
        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of module.
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
        $helper->submit_action = 'submitPosqlModule';
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
     * Create the structure of form.
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
                        'desc' => $this->l('Ingresa una consulta SQL. Donde vayan fechas escribe FFF1 y FFF2'),
                        'name' => 'POSQL_QUERY',
                        'label' => $this->l('SQL Query'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',                        
                        'desc' => $this->l('Ingresa una cabecera para los valores si lo deseas. Separa los valores con ;'),
                        'name' => 'POSQL_TEXT',
                        'label' => $this->l('SQL Text'),
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
            'POSQL_QUERY' => Configuration::get('POSQL_QUERY', null),
            'POSQL_TEXT' => Configuration::get('POSQL_TEXT', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, htmlentities(Tools::getValue($key)));
        }
    }

    /**
    * Add the CSS & JavaScript files to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    private function installController($controllerName, $name) {
        $tab_admin_order_id = Tab::getIdFromClassName ('AdminTools') ? Tab::getIdFromClassName ('AdminTools') : Tab::getIdFromClassName ('AdminAdvancedParameters');
        $tab = new Tab();
        $tab->class_name = $controllerName;
        $tab->id_parent = $tab_admin_order_id;
        $tab->module = $this->name;
        $languages = Language::getLanguages(false);
        foreach($languages as $lang){
            $tab->name[$lang['id_lang']] = "P.Online Query";
        }
    	$tab->save();
	}

	public function uninstallController($controllerName) {
		$tab_controller_main_id = TabCore::getIdFromClassName($controllerName);
		$tab_controller_main = new Tab($tab_controller_main_id);
		$tab_controller_main->delete();
    }
}
