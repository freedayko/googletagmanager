<?php
/**
* NOTICE OF LICENSE
**
*  @author    Rodrigo Varela Tabuyo <rodrigo@centolaecebola.com>
*  @copyright 2017 Rodrigo Varela Tabuyo
*  @license   ……
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class GoogleTagManager extends Module
{
    // add custom error messages
    protected $errors = array();
    
    protected $dataLayer;

    public function __construct()
    {
        $this->name = 'googletagmanager';
        $this->tab = 'analytics_stats';
        $this->version = '0.1.1';
        $this->author = 'Rodrigo Varela Tabuyo';
        $this->module_key = '5cb794a64177c47254bef97263fe8lbc';
        $this->bootstrap = false;
        $this->ps_versions_compliancy = array('min' => '1.6');

        parent::__construct();

        $this->displayName = $this->l('Google Tag Manager');
        $this->description = $this->l('Añade tags y no mires atrás');

        $this->confirmUninstall = $this->l('¿Está seguro de quieres desinstalar este módulo?');

        $this->dataLayer = new stdClass();
        
    }

    public function install()
    {        
        return (
            parent::install()
            // Use to set common dataLayer vars
            && $this->registerHook('displayHeader')

            // Use to set common dataLayer vars
            && $this->registerHook('displayTop')

            && $this->registerHook('actionProductListModifier')

            && $this->registerHook('actionSearch')

            // Use to set shopping cart dataLayer vars
            && $this->registerHook('displayShoppingCart')
            
            // Use to set order confirmation dataLayer vars
            && $this->registerHook('displayOrderConfirmation')
            );
    }

    public function uninstall() {

        if (!parent::uninstall()) {
            return false;
        }

        return parent::uninstall();
    }

	public function getContent()
	{
		$output = '';

		// If form has been sent
		if (Tools::isSubmit('submit'.$this->name))
		{
			Configuration::updateValue('GOOGLE_TAG_MANAGER_ID', Tools::getValue('GOOGLE_TAG_MANAGER_ID'));
			$output .= $this->displayConfirmation($this->l('Settings updated successfully'));
		}

		$output .= $this->renderForm();
		return $output;
	}

	public function renderForm()
	{
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->submit_action = 'submit'.$this->name;

		$fields_forms = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('General settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Tag Manager ID'),
						'name' => 'GOOGLE_TAG_MANAGER_ID',
						'size' => 20,
						'required' => true,
						'hint' => $this->l('Enter here your ID (GTM-XXXXXX).')
					)
				),
				'submit' => array(
					'title' => $this->l('Save')
				)
			)
		);

		// Load current value
		$helper->fields_value['GOOGLE_TAG_MANAGER_ID'] = Configuration::get('GOOGLE_TAG_MANAGER_ID');

		return $helper->generateForm(array($fields_forms));
	}

    public function hookDisplayHeader($params) {
		$tagManagerId = Tools::safeOutput(Configuration::get('GOOGLE_TAG_MANAGER_ID'));
		if (!$tagManagerId)
			return;

        $this->context->smarty->registerFilter('output',array($this, 'outputFilter'));

        $this->context->smarty->assign("GTM_ID",$tagManagerId);
        
        $this->context->controller->addJS(($this->_path).'googletagmanager.js');

        $this->dataLayer->ecommerce = new stdClass();
        
        if (isset($this->context->controller->php_self)) {
            switch($this->context->controller->php_self) {
                case 'index':
                    $pageType = 'home';
                    break;
                case 'category':
                case 'product':
                    $pageType = $this->context->controller->php_self;
                    break;
                case 'order':
                    $pageType = $this->context->controller->step == 0 ? 'cart' : false;
                    break;
                case 'order-confirmation':
                    $pageType = 'purchase';
                    break;
                default:
                    $pageType = false;
            }
            $this->dataLayer->pageType = $pageType;
            if ($pageType == 'product') {
                $this->dataLayer->ecommerce->detail = new stdClass();
                $product = $this->context->controller->getProduct();
                $dataLayerProduct = new stdClass();
                $dataLayerProduct->name = $product->name;
                $dataLayerProduct->id = $product->id;
                $dataLayerProduct->brand = $product->manufacturer_name;
                $dataLayerProduct->category = $product->id_category_default;
                $dataLayerProduct->price = (string)$product->getPrice(true, null, 2);
                $this->dataLayer->ecommerce->detail->products = array($dataLayerProduct);
            }
        }
        
        //Set up common Criteo One Tag vars
        $customer = $this->context->customer; //id_customer = $params['cart']->id_customer;
        if( $customer->id ) {
            $customerEmail = $customer->email;
            $processedAddress = strtolower($customerEmail); //conversion to lower case 
            $processedAddress = trim($processedAddress); //trimming
            $processedAddress = mb_convert_encoding($processedAddress, "UTF-8", mb_detect_encoding($customerEmail)); //conversion from ISO-8859-1 to UTF-8 (replace "ISO-8859-1" by the source encoding of your string)
            $processedAddress = md5($processedAddress); //hash with MD5 algorithm
            $this->dataLayer->hashedEmail =  $processedAddress;
        }
    }

    public function outputFilter($tplOutput, Smarty_Internal_Template $template) {
        if (($headPos = strpos($tplOutput, '<head')) !== false) {
            $headEnd = strpos($tplOutput, '>', $headPos) + 1;
            $this->context->smarty->assign('dataLayer', $this->dataLayer);
            $headPlugin = $this->display(__FILE__, 'views/templates/hooks/header.tpl');
            $tplOutput = substr_replace($tplOutput, PHP_EOL . $headPlugin, $headEnd, 0);
            $bodyEnd = strpos($tplOutput, '>', strpos($tplOutput, '<body')) + 1;
            $bodyPlugin = $this->display(__FILE__, 'views/templates/hooks/top.tpl');
            $tplOutput = substr_replace($tplOutput, PHP_EOL . $bodyPlugin, $bodyEnd, 0);
//            var_dump($this->dataLayer, $tplOutput);
        }
        return $tplOutput;
    }

    public function hookActionProductListModifier($params) {
        if (!isset($this->dataLayer->ecommerce->impressions)) {
            $this->dataLayer->ecommerce->impressions = array();
        }
        foreach ($params['cat_products'] as $i => &$product) {
            $impression = new stdClass();
            $impression->id = $product['id_product'];
            $impression->name = $product['name'];
            $impression->brand = $product['manufacturer_name'];
            $impression->category = $product['id_category_default'];
            $impression->list_position = $i;
            $impression->price = (string)$product['price'];
            $this->dataLayer->ecommerce->impressions[] = $impression;
        }
        //Get first three products in category page
        
    }

    public function hookActionSearch($params) {
        //TODO: similar to previous function, but using search results
    }
    
    public function hookDisplayShoppingCart($params) {
        //DataLayer value for Criteo One Tag
        if( $this->context->controller->php_self == 'order') { //show shopping cart
            $this->dataLayer->ecommerce->checkout = new stdClass();
            $this->dataLayer->ecommerce->checkout->actionField = new stdClass();
            $this->dataLayer->ecommerce->checkout->actionField->step = $this->context->controller->step;
        } //do not assign dataLayer vars in payment and delivery options

    }

    public function hookOrderConfirmation($params) {
        $this->context->smarty->assign("PageType", "TransactionPage");

        $obj_order = $params['objOrder'];
        $ids_payment_error = array(6, 8); //cancelled, payment error, refunded

        $new_Cart = new Cart($params['objOrder']->id_cart);
        $customer = new Customer($new_Cart->id_customer);
        
        //if first order, we have a new customer
        if( count(Order::getCustomerOrders($customer->id)) == 1 )
            $this->smarty->assign("type_of_customer", "new_customer");
        else
            $this->smarty->assign("type_of_customer", "returning_customer");

        $products_in_cart = $new_Cart->getProducts(true);
        
        if (Validate::isLoadedObject($obj_order)) {
            if (!in_array($obj_order->current_state, $ids_payment_error)) {
                // Validate all orders except payment error status
                $order_is_valid = true;
            }

            if ($order_is_valid) {
                // convert object to array
                $order = get_object_vars($obj_order);
                $order_id = $order['id'];
                $id_shop = $order['id_shop'];
                $id_lang = $order['id_lang'];

                $this->smarty->assign("transactionId", $order_id); //Transaction ID
                $this->smarty->assign("transactionTotal", $order['total_paid']); //
                $this->smarty->assign("transactionShipping", $order['total_shipping']); //
                $this->smarty->assign("transactionProducts", $products_in_cart); //
                $this->smarty->assign("dataLayer", $order);
            }
        }
    }
}
