<?php
/*
* 2013 Shoppilot
*
*/

if (!defined('_PS_VERSION_'))
  exit;

class shoppilot extends Module
{
	public function __construct()
	{
		$this->name = 'shoppilot';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'Shoppilot';
		$this->need_instance = 0;
		$this->dependencies = array('blockcart');

		parent::__construct();

		$this->displayName = $this->l('Shoppilot');
		$this->description = $this->l('Module provides integration with shoppilot.ru.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		if (!Configuration::get('SHOPPILOT'))
		  $this->warning = $this->l('No name provided');
	}

	private function prepareVars($params) {
		global $link;
		$id_cart = (int)(Tools::getValue('id_cart', 0));
		$id_order = Order::getOrderByCartId((int)($id_cart));
		$order = new Order((int)($id_order));
		$products = $order->getProducts();
		$customer = new Customer($order->id_customer);
		$images = array();
		$token = Configuration::get('SHOPPILOT_TOKEN');
		$secret_key = Configuration::get('SHOPPILOT_SECRET_KEY');
		$signature = md5("{$secret_key}{$order->id}");

		$i=0;
		foreach($products as $product) {
			if( isset($product) && $product['product_id'] )	{
				 $id_image = Product::getCover((int)($product['product_id']));
				if (sizeof($id_image) > 0) {
					$images[$i] = $link->getImageLink($product['product_name'], "".$product['product_id']."-".+$id_image['id_image'], 'large_default') ;
					// $images[$i] = $link->getImageLink('name', $id_image['id_image'], 'large_default') ;
				}
				$i++;
			}
		}
		return array(
			'signature' => $signature,
			'token' => $token,
			'order' => $order,
			'products' => $products,
			'customer' => $customer,
			'images' => $images
		);
	}


	public function hookOrderConfirmation($params){
		$vars = $this->prepareVars($params);
	  $this->smarty->assign($vars);
	  return $this->display(__FILE__, 'shoppilot.tpl');
	}

	public function install()
	{
	  return parent::install() &&	$this->registerHook('orderConfirmation');
	}

	public function uninstall()
	{
	  return parent::uninstall() &&
	  			 Configuration::deleteByName('SHOPPILOT') &&
	  			 Configuration::deleteByName('SHOPPILOT_TOKEN') &&
	  			 Configuration::deleteByName('SHOPPILOT_SECRET_KEY');
	}

	public function getContent()
	{
    $output = '';

    if (Tools::isSubmit('submit'.$this->name))
    {
      $token = strval(Tools::getValue('SHOPPILOT_TOKEN'));
      $secret_key = strval(Tools::getValue('SHOPPILOT_SECRET_KEY'));
      if (!$token  || empty($token) || !Validate::isGenericName($token) ||
      	  !$secret_key || empty($secret_key) || !Validate::isGenericName($secret_key))
      	$output .= $this->displayError( $this->l('Invalid Configuration value') );
      else
      {
        Configuration::updateValue('SHOPPILOT_TOKEN', $token);
        Configuration::updateValue('SHOPPILOT_SECRET_KEY', $secret_key);
        $output .= $this->displayConfirmation($this->l('Settings updated'));
      }
    }
    return $output.$this->displayForm();
	}

	public function displayForm()
	{
    // Get default Language
    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

    // Init Fields form array
    $fields_form[0]['form'] = array(
      'legend' => array(
        'title' => $this->l('Settings'),
      ),
      'input' => array(
        array(
          'type' => 'text',
          'label' => $this->l('Token'),
          'name' => 'SHOPPILOT_TOKEN',
          'size' => 50,
          'required' => true
        ),
        array(
          'type' => 'text',
          'label' => $this->l('Secret key'),
          'name' => 'SHOPPILOT_SECRET_KEY',
          'size' => 50,
          'required' => true
        )
      ),
      'submit' => array(
        'title' => $this->l('Save'),
        'class' => 'button'
        )
    );

    $helper = new HelperForm();

    // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;

    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit'.$this->name;
    $helper->toolbar_btn = array(
        'save' =>
        array(
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ),
        'back' => array(
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        )
    );

    // Load current value
    $helper->fields_value['SHOPPILOT_TOKEN'] = Configuration::get('SHOPPILOT_TOKEN');
    $helper->fields_value['SHOPPILOT_SECRET_KEY'] = Configuration::get('SHOPPILOT_SECRET_KEY');

    return $helper->generateForm($fields_form);
	}

}
