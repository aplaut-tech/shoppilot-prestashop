<?php
/*
* 2013 Shoppilot
*
*/

if (!defined('_PS_VERSION_'))
  exit;

class Shoppilot extends Module
{
	public function __construct()
	{
		$this->name = 'shoppilot';
		$this->tab = 'front_office_features';
		$this->version = '1.1';
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
    if(_PS_VERSION_ < "1.5.0.0") {
      global $smarty;
      $smarty->assign($vars);
    } else {
	    $this->context->smarty->assign($vars);
    }
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

    $conf = Configuration::getMultiple(array('SHOPPILOT_TOKEN', 'SHOPPILOT_SECRET_KEY'));

    return
      '<br /><fieldset><legend>'.$this->l('Settings').'</legend>
      <form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post">
      <label for="serial">'.$this->l('Token').' :</label>
      <div class="margin-form">
        <input type="text" name="SHOPPILOT_TOKEN" value="'.$conf['SHOPPILOT_TOKEN'].'" />
      </div>
      <label for="address">'.$this->l('Secret key').': </label>
      <div class="margin-form">
        <input type="text" name="SHOPPILOT_SECRET_KEY" value="'.$conf['SHOPPILOT_SECRET_KEY'].'" />
      </div>
      <input type="submit" name="submitshoppilot" class="button" value="'.$this->l('Save').'" />
      </form></fieldset>';

	}

}
