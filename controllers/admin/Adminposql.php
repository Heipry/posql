<?php
use Symfony\Component\Form\AbstractType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;


/**
 * Easy SQL queries between dates
 * @category Administration
 *
 * @author Javier Diaz
 * @copyright Javier Diaz / PrestaShop
 * @license http://www.opensource.org/licenses/osl-3.0.php Open-source licence 3.0
 * @version 0.4
 */
class AdminPoSqlController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->meta_title = 'Consulta por fechas';
        $this->name = 'Consulta por fechas';
        parent::__construct();
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }       
    }

    public function renderView()
    {       
        $this->context->smarty->assign("sql", Configuration::get('POSQL_QUERY', null));         
        $this->context->smarty->assign("text", Configuration::get('POSQL_TEXT', null));
        $content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'posql/views/templates/hook/content.tpl');
        return $content.$this->renderConfigurationForm();        
    }
   
    public function renderConfigurationForm()    
    {   
        $inputs = [ 
            [
                'type' => 'datetime-local',
                'label' => $this->module->l('From', 'AdminPoSql'),
                'name' => 'date_from',
                'maxlength' => 10,
                'required' => true,
                'desc' => $this->module->l('Choose first day of interval', 'AdminPoSql'),
                'hint' => $this->module->l('Format: 2011-12-31', 'AdminPoSql')
            ],
           [
                'type' => 'datetime',
                'label' => $this->module->l('To', 'AdminPoSql'),
                'name' => 'date_to',
                'maxlength' => 10,
                'required' => true,
                'desc' => $this->module->l('Choose last day of interval', 'AdminPoSql'),
                'hint' => $this->module->l('Format: 2012-12-31', 'AdminPoSql')
           ],            
        ];
    
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => "&nbsp Generar",
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->module->l('Print', 'AdminPoSql'),
                    'icon' => 'icon-print',
                )
            ),
        );
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGenerar';
        $helper->currentIndex = self::$currentIndex;
        $helper->token = Tools::getAdminTokenLite('AdminPoSql');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $today = getdate();
        return array(            
            'date_from' => $today,
            'date_to' => $today,
        );
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitGenerar')) {   
            $f = fopen('php://memory', 'w');     
            $db = Db::getInstance();
            $fecha1 = "'".Tools::getValue('date_from')."'";
            $fecha2 = "'".Tools::getValue('date_to')."'";
            $sqlQuery = str_replace('FFF2',$fecha2,str_replace('FFF1',$fecha1,html_entity_decode(Configuration::get('POSQL_QUERY', null))));
            $request = $sqlQuery;
            $ordenes = $db->executeS($request);               
            for ($i=0; $i < count($ordenes); $i++) { 
                $row[$i] = implode(";",$ordenes[$i]);
            }
            $table =(Configuration::get('POSQL_TEXT', null))."\r\n";
            $table .= implode("\r\n",$row);
            
            fwrite ($f, $table);
            fseek($f, 0);
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="listado.csv";');
            echo "\xEF\xBB\xBF";          
            fpassthru($f);
            fclose($f); 
            die();
        }
    }

    public function initContent()
    {
        $this->content = $this->renderView();
        parent::initContent();
    }
}

    

