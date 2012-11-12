<?php
/**
 * @connect_module_class_name payu
 * @package DynamicModules
 * @subpackage Payment
 */
	
#	include 'class.payu.sc.php';
define("__ENCODE__", "windows-1251");  # utf-8

	class payu extends PaymentModule {

		#var $type = PAYMTD_TYPE_ONLINE;
		var $language = 'rus';
		var $default_logo = '/published/SC/html/img/logo-payu.png';
		var $prUrl = "https://secure.payu.ua/order/lu.php";#"; 
		

		var $useSSL = false;
	
		function _initVars(){
			
			$this->title = payu::_ENC("PayU payment system");
			$this->description = payu::_ENC("Платежный агрегатор PayU");

			$this->sort_order = 1;
	
			$this->Settings = array( 
					'CONF_PAYU_MERCHANT',
					'CONF_PAYU_SECRET_KEY',
					'CONF_PAYU_DEBUG_MODE',
					'CONF_PAYU_LU_URL',
					'CONF_PAYU_CURRENCY',
					'CONF_PAYU_BACK_REF',
					'CONF_PAYU_VAT',
					'CONF_PAYU_LANGUAGE',					
				);
		}
	
		function _initSettingFields(){
	
		
		
				$this->SettingsFields['CONF_PAYU_MERCHANT'] = array(
					'settings_value' 			=> "XXXXXXXX",
					'settings_title' 			=> payu::_ENC("Merchant ID"),
					'settings_description' 		=> payu::_ENC("Идентификатор мерчанта"),
					'settings_html_function' 	=> 'setting_TEXT_BOX(0,',
					'sort_order' 				=> 1
				);
				$this->SettingsFields['CONF_PAYU_SECRET_KEY'] = array(
					'settings_value' 			=> "*********",
					'settings_title' 			=> payu::_ENC("Merchant secret key"),
					'settings_description' 		=> payu::_ENC("Секретный ключ мерчанта"),
					'settings_html_function' 	=> 'setting_TEXT_BOX(0,',
					'sort_order' 				=> 1
				);
				$this->SettingsFields['CONF_PAYU_DEBUG_MODE'] = array(
					'settings_value' 			=> "*********",
					'settings_title' 			=> payu::_ENC("Debug mode"),
					'settings_description' 		=> payu::_ENC("Режим отладки"),
					'settings_html_function' 	=> 'setting_SELECT_BOX(payu::_getDebugMode(),',
					'sort_order' 				=> 1
				);

				
				$this->SettingsFields['CONF_PAYU_LU_URL'] = array(
					'settings_value' 			=> "https://secure.payu.ua/order/lu.php",
					'settings_title' 			=> payu::_ENC("LiveUpdate URL"),
					'settings_description' 		=> payu::_ENC("Ссылка LiveUpdate (default : https://secure.payu.ua/order/lu.php)"),
					'settings_html_function' 	=> 'setting_TEXT_BOX(0,',
					'sort_order' 				=> 1
				);
				$this->SettingsFields['CONF_PAYU_CURRENCY'] = array(
					'settings_value' 			=> "UAH",
					'settings_title' 			=> payu::_ENC("Валюта мерчанта "),
					'settings_description' 		=> payu::_ENC("UAH"),
					'settings_html_function' 	=> 'setting_TEXT_BOX(0,', #'setting_CURRENCY_SELECT(',
					'sort_order' 				=> 1
				);
				$this->SettingsFields['CONF_PAYU_BACK_REF'] = array(
					'settings_value' 			=> "NO",
					'settings_title' 			=> payu::_ENC("Ссылка возврата клиента "),
					'settings_description' 		=> payu::_ENC("Если оставить значение NO, клиент останется в системе PayU<br>".
												   "Если сделать поле пустым - Клиент вернется по дефолтной ссылке"),
					'settings_html_function' 	=> 'setting_TEXT_BOX(0,',
					'sort_order' 				=> 1
				); 
				$this->SettingsFields['CONF_PAYU_VAT'] = array(
					'settings_value' 			=> "0",
					'settings_title' 			=> payu::_ENC("НДС"),
					'settings_description' 		=> payu::_ENC("Если 0 - без НДС"),
					'settings_html_function' 	=> 'setting_TEXT_BOX(0,',
					'sort_order' 				=> 1
				);
				$this->SettingsFields['CONF_PAYU_LANGUAGE'] = array(
					'settings_value' 			=> "RU",
					'settings_title' 			=> payu::_ENC("Язык страницы"),
					'settings_description' 		=> payu::_ENC("Доступны ( RU, EN, RO, DE, FR, IT, ES )"),
					'settings_html_function' 	=> 'setting_TEXT_BOX(0,',
					'sort_order' 				=> 1
				);
		}

		function payment_process( $order ){
		
		$cart = cartGetCartContent();
			foreach ( $cart['cart_content'] as $item )
			{
					$price = PaymentModule::_convertCurrency( $item['costUC'], 0, $this->_getSettingValue('CONF_PAYU_CURRENCY'));
					if ($price == 0) $price = $item['costUC']; 
					$d['ORDER_PNAME'][] = payu::_DEC( $item['name'] ); # Array with data of goods
					$d['ORDER_QTY'][] = $item['quantity']; # Array with data of counts of each goods 
					$d['ORDER_PRICE'][] = $price; # round( $price, 2 ); # Array with prices of goods
					$d['ORDER_VAT'][] = 0; #$data['VAT'];# Array with VAT of each goods  => from settings
					$d['ORDER_PCODE'][] = $item['productID']; # Array with codes of goods
					$d['ORDER_PINFO'][] = ""; # Array with additional data of goods
			}

		$this->prUrl = $this->_getSettingValue('CONF_PAYU_LU_URL');


		$bill = &$order['billing_info'];
		$forSend = array (
					'ORDER_REF' => "", # Uniqe order 
					'ORDER_DATE' => date("Y-m-d H:i:s"), # Date of paying ( Y-m-d H:i:s ) 
					'ORDER_PNAME' => $d['ORDER_PNAME'], # Array with data of goods
					'ORDER_PCODE' => $d['ORDER_PCODE'], # Array with codes of goods
					#'ORDER_PINFO' => $d['ORDER_PINFO'], # Array with additional data of goods
					'ORDER_PRICE' => $d['ORDER_PRICE'], # Array with prices of goods
					'ORDER_QTY' => $d['ORDER_QTY'], # Array with data of counts of each goods 
					'ORDER_VAT' => $d['ORDER_VAT'], # Array with VAT of each goods
					'ORDER_SHIPPING' => $order["shipping_cost"], # Shipping cost
					'PRICES_CURRENCY' => $this->_getSettingValue('CONF_PAYU_CURRENCY')  # Currency
				  );


				$coock = base64_encode(json_encode( $forSend ));
				SetCookie("payuform", $coock, time()+600);
	
			return 1;
		}

		function after_processing_html( $_OrderID )
		{	
			$data = @json_decode( base64_decode($_COOKIE['payuform']), true );

			if ( !$data ) return false;

			$data['ORDER_REF'] = $_SERVER['HTTP_HOST'].'_'.$_OrderID.'_'.md5( time() );

			$payu  = new PayUCLS( $this->_getSettingValue('CONF_PAYU_MERCHANT'), $this->_getSettingValue('CONF_PAYU_SECRET_KEY') );

			$payu->update( $data )->debug( $this->_getSettingValue('CONF_PAYU_DEBUG_MODE') );

			$result_url = $this->_getSettingValue('CONF_PAYU_BACK_REF');
			if ($result_url !== "NO") $payu->data['BACK_REF'] = ($result_url !== "") ? $result_url : htmlentities($this->getTransactionResultURL('success'),ENT_QUOTES,'utf-8');

			$form = $payu->getForm();


			$statusID = "3";
			
			$order = ordGetOrder( $_OrderID );
			ostSetOrderStatusToOrder( $_OrderID, $statusID, payu::_ENC("Оплата через PayU"), 0, true);

			return $form ;
		}

	public static function _getDebugMode()
	{
		return array(
					array('title'=> payu::_ENC("Выберите режим"), 'value'=>''),
					array('title'=> payu::_ENC("Вкл"),	'value'=>'1'),
					array('title'=> payu::_ENC("Выкл"),	'value'=>'0')
					);
	}

	public function _ENC( $str )
	{
		if ( __ENCODE__  ==  "utf-8" ) return $str;
		return iconv( "utf-8", __ENCODE__,  $str );
	}

	public function _DEC( $str )
	{
		if ( __ENCODE__  ==  "utf-8" ) return $str;
		return iconv( __ENCODE__, "utf-8",  $str );
	}
}
?>
