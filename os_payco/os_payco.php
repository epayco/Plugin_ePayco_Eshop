<?php

/**
 * @version		3.3.3
 * @package		Joomla
 * @subpackage	EShop
 * @author  	ePayco
 * @copyright	Copyright 2011-2021 ePayco.co Todos los derechos reservados.
 * @license		GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die();

class os_epayco extends os_payment {
	function __construct($params, $config = array()) 
	{
		parent::__construct($params, $config);

		$this->setData('publicKey', $params->get("epayco_public_key"));
		$this->setData('pKey', $params->get("epayco_p_key"));
		$this->setData('customerId', $params->get("epayco_cust_id"));
		$this->setData('external', $params->get("p_external_request") == "0");
		$this->setData('test', $params->get("p_test_request") == "1");
		$this->setData('checkoutLang', $params->get("p_lang_request") == "es");
		
	}

	public function processPayment($data)
	{
		$siteUrl = EshopHelper::getSiteUrl();
		$returnUrl = $siteUrl . 'index.php?option=com_eshop&view=checkout&layout=complete&task=checkout.verifyPayment&payment_method=os_epayco&order_number=' . $data['order_number'];
		$confirmUrl = $returnUrl . '&confirmation=true';
		$countryInfo = EshopHelper::getCountry($data['payment_country_id']);
		$countryIso	= $countryInfo->iso_code_2;
		$amount=$data['total'];
		$tax=0;
		foreach($data["totals"] as $totales){
			if($totales['name'] == 'tax'){
				$tax+=$totales['value'];
			}
		}
		$base_tax=$amount-$tax;
		$this->setData('currencyCode', $data['currency_code']);
		$this->setData('invoiceNumber', $data['invoice_number']);
		$this->setData('country', $countryIso);
		$this->setData('orderNumber', $data['order_number']);
		$this->setData('total', $amount);
		$this->setData('base_tax', $base_tax);
		$this->setData('tax', $tax);
		$this->setData('confirmUrl', $confirmUrl);
		$this->setData('returnUrl', $returnUrl);
		$this->setData('billing_name', $data["payment_firstname"]." ".$data["payment_lastname"]);
		$this->setData('billing_addres',$data["payment_address_1"]);
		$this->setData('billing_email',$data["payment_email"]);

		$this->submitPost();
	}

	public function submitPost($url = null, $data = array())
	{
		if (empty($data))
        {
            $data = $this->data;
        }
		
		if($data["checkoutLang"]) {
			$msgEpaycoCheckout = '<span class="animated-points">Cargando métodos de pago</span>
			<br><small class="epayco-subtitle"> Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco</small>';
			$epaycoButtonImage ='<img src="https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color1.png">';
			
		}else{
			$msgEpaycoCheckout = '<span class="animated-points">Loading payment methods</span>
					   <br><small class="epayco-subtitle"> If they do not load automatically, click on the "Pay with ePayco" button</small>';
			$epaycoButtonImage ='<img src="https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color-Ingles.png">';
		}
		?>
			<style>
				.epayco-title{
					max-width: 900px;
					display: block;
					margin:auto;
					color: #444;
					font-weight: 700;
					margin-bottom: 25px;
				}
				.loader-container{
					position: relative;
					padding: 20px;
					color: #ff5700;
				}
				.epayco-subtitle{
					font-size: 14px;
				}
				.epayco-button-render{
					transition: all 500ms cubic-bezier(0.000, 0.445, 0.150, 1.025);
					transform: scale(1.1);
					box-shadow: 0 0 4px rgba(0,0,0,0);
				}
				.epayco-button-render:hover {
					transform: scale(1.2);
				}
				.animated-points::after{
					content: "";
					animation-duration: 2s;
					animation-fill-mode: forwards;
					animation-iteration-count: infinite;
					animation-name: animatedPoints;
					animation-timing-function: linear;
					position: absolute;
				}
				.animated-background {
					animation-duration: 2s;
					animation-fill-mode: forwards;
					animation-iteration-count: infinite;
					animation-name: placeHolderShimmer;
					animation-timing-function: linear;
					color: #f6f7f8;
					background: linear-gradient(to right, #7b7b7b 8%, #999 18%, #7b7b7b 33%);
					background-size: 800px 104px;
					position: relative;
					background-clip: text;
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				.loading::before{
					-webkit-background-clip: padding-box;
					background-clip: padding-box;
					box-sizing: border-box;
					border-width: 2px;
					border-color: currentColor currentColor currentColor transparent;
					position: absolute;
					margin: auto;
					top: 0;
					left: 0;
					right: 0;
					bottom: 0;
					content: " ";
					display: inline-block;
					background: center center no-repeat;
					background-size: cover;
					border-radius: 50%;
					border-style: solid;
					width: 30px;
					height: 30px;
					opacity: 1;
					-webkit-animation: loaderAnimation 1s infinite linear,fadeIn 0.5s ease-in-out;
					-moz-animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
					animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
				}
				@keyframes animatedPoints{
					33%{
						content: "."
					}
					66%{
						content: ".."
					}
					100%{
						content: "..."
					}
				}
				@keyframes placeHolderShimmer{
					0%{
					background-position: -800px 0
					}
					100%{
					background-position: 800px 0
					}
				}
				@keyframes loaderAnimation{
					0%{
						-webkit-transform:rotate(0);
						transform:rotate(0);
						animation-timing-function:cubic-bezier(.55,.055,.675,.19)
					}
					50%{
						-webkit-transform:rotate(180deg);
						transform:rotate(180deg);
						animation-timing-function:cubic-bezier(.215,.61,.355,1)
					}
					100%{
					-webkit-transform:rotate(360deg);
					transform:rotate(360deg)
					}
				}
			</style>
        	<div class="eshop-heading"> <h3> ePayco </h3> </div>
			<?php echo JHtml::_('script', 'https://checkout.epayco.co/checkout.js'); ?>
			<script type="text/javascript">
				function openCheckout() {
					var orderData = <?php echo json_encode($this->data); ?>;
					var lang;
					if(orderData.checkoutLang){
						lang = "ES";
					}else{
						lang = "EN";
					}
					var checkoutData = {
						name: "Order #" + orderData.orderNumber,
						description: "Order #" + orderData.orderNumber,
						//invoice: orderData.invoiceNumber,
						currency: orderData.currencyCode,
						amount: orderData.total,
						tax_base: orderData.base_tax,
						tax: orderData.tax,
						country: orderData.country,
						lang: lang,
						confirmation: orderData.confirmUrl,
						response: orderData.returnUrl,
						external: orderData.external.toString(),
						extra1:orderData.invoiceNumber,
						name_billing: orderData.billing_name,
          				address_billing: orderData.billing_addres,
						email_billing: orderData.billing_email
					};
					var checkoutHandler = ePayco.checkout.configure({
						key: orderData.publicKey,
						test: orderData.test
					});
					console.log(checkoutData);
					checkoutHandler.open(checkoutData);
				}

				//openCheckout();
			</script>
			<div class="loader-container">
				<div class="loading"></div>
			</div>
			<p style="text-align: center;" class="epayco-title">
			<?php echo $msgEpaycoCheckout; ?>
            </p> 
			<center>
				<button 
					style="padding: 0; background: none; border: none; cursor: pointer;text-align: center;" 
					class="epayco-button-render"
					onclick="openCheckout()">
					<?php echo $epaycoButtonImage; ?>
				</button>
			</center>
			<?php echo JHtml::_('script', 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js'); ?>
            <script type="text/javascript">
				window.onload = function() {
				    document.addEventListener("contextmenu", function(e){
				        e.preventDefault();
				    }, false);
				} 
				$(document).keydown(function (event) {
					if (event.keyCode == 123) {
						return false;
					} else if (event.ctrlKey && event.shiftKey && event.keyCode == 73) {        
						return false;
					}
				});
            </script>
    	<?php
	}

	public function verifyPayment()
	{	 
		$app = JFactory::getApplication();
		$input = $app->input;
		$isConfirmation = $input->get('confirmation', "false", 'string') == "true";
		$mdbRefPayco = $input->get('ref_payco', '000', 'string');
		if($isConfirmation){
			$paymentDetails=$_REQUEST;
			$paymentResponse = trim($paymentDetails['x_response']);
			$invoiceId = trim($paymentDetails['x_extra1']);
			$x_signature = trim($paymentDetails['x_signature']);
            $x_cod_transaction_state = (int)trim($paymentDetails['x_cod_transaction_state']);
            $x_ref_payco = trim($paymentDetails['x_ref_payco']);
            $x_transaction_id = trim($paymentDetails['x_transaction_id']);
            $x_amount = trim($paymentDetails['x_amount']);
            $x_currency_code = trim($paymentDetails['x_currency_code']);
            $x_test_request = trim($paymentDetails['x_test_request']);
            $x_approval_code = trim($paymentDetails['x_approval_code']);
            $x_franchise = trim($paymentDetails['x_franchise']);
		}else{
			$paymentDetails = $this->getTransactionDetails($mdbRefPayco);
			$paymentResponse = $paymentDetails->data->x_response;
			$invoiceId = $paymentDetails->data->x_extra1;
			$x_signature = $paymentDetails->data->x_signature;
            $x_cod_transaction_state = (int)$paymentDetails->data->x_cod_transaction_state;
			$x_ref_payco = $paymentDetails->data->x_ref_payco;
			$x_transaction_id = $paymentDetails->data->x_transaction_id;
			$x_amount = $paymentDetails->data->x_amount;
            $x_currency_code = $paymentDetails->data->x_currency_code;
            $x_test_request = $paymentDetails->data->x_test_request;
            $x_approval_code = $paymentDetails->data->x_approval_code;
            $x_franchise = $paymentDetails->data->x_franchise;

			$x_signature = $paymentDetails->data->x_signature;
		}
		$generatedSignature = $this->generateSignature($x_ref_payco, $x_transaction_id, $x_amount, $x_currency_code);
		$isTestTransaction = $x_test_request == 'TRUE' ? "yes" : "no";
		$isTestMode = $isTestTransaction == "yes" ? "true" : "false";
		$isTestPluginMode = $this->data['test'];
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('total,id')
			->from('#__eshop_orders')
			->where('invoice_number = ' . intval($invoiceId));
		$db->setQuery($query);
		$db->loadResult();
		$result = $db->loadObjectList();
		$order_total = $result[0]->total;
		if(floatval($order_total) == floatval($x_amount)){
			if($isTestPluginMode){
				$validation = true;
			}else{
				if($x_approval_code != "000000" && $x_cod_transaction_state == 1){
					$validation = true;
				}else{
					if($x_cod_transaction_state != 1){
						$validation = true;
					}else{
						$validation = false;
					}
				}
				
			}
		}else{
			 $validation = false;
		}	
		
		if($x_signature == $generatedSignature && $validation){
			$invoice_Id = $result[0]->id;
			$row = JTable::getInstance('Eshop', 'Order');
			$row->load($invoice_Id);
			$orderName= $row->order_number;
			$row->transaction_id = $x_ref_payco;
			$orderStatusName = EshopHelper::getOrderStatusName($row->order_status_id, JComponentHelper::getParams('com_languages')->get('site', 'en-GB'));
			
			switch ($x_cod_transaction_state) {
				case 1: {
					if($orderStatusName == 'Complete'
					 || $orderStatusName == 'Processing'){}else{
						EshopHelper::completeOrder($row);
					}
					$row->order_status_id = $this->getOrderStatusId($x_cod_transaction_state);
					$row->store();
					JPluginHelper::importPlugin('eshop');
					JFactory::getApplication()->triggerEvent('onAfterCompleteOrder', array($row));
					if (EshopHelper::getConfigValue('order_alert_mail')){
						EshopHelper::sendEmails($row);
					}
	
					if(!$isConfirmation){
						JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=complete'));
					}else{
						echo "Complete";
						exit();
					}
				}break;
				case 2: {
					$session = JFactory::getSession();
					$session->set('eshop_payment_error_reason', $_POST['x_response_reason_text']);
					if($orderStatusName == 'Canceled'
					   || $orderStatusName == 'Pending')
					{}else{EshopHelper::updateInventory($row);}
				   	$row->order_status_id = $this->getOrderStatusId($x_cod_transaction_state);
					$row->store();
					if(!$isConfirmation){
						$app->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=failure'));
					}else{
						echo "Canceled";
						exit();
					}
				}break;
				case 3: {
					if($orderStatusName != 'Processing'){
						EshopHelper::updateInventory($row,'-');
						$row->order_status_id = $this->getOrderStatusId($x_cod_transaction_state);
						$row->store();
					}
					if (EshopHelper::getConfigValue('order_alert_mail')){
						EshopHelper::sendEmails($row);
					}
	
					if(!$isConfirmation){
						JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=complete'));
					}else{
						echo "Processing";
						exit();
					}
				}break;
				case 4: {
					$session = JFactory::getSession();
					$session->set('eshop_payment_error_reason', $_POST['x_response_reason_text']);
					if($orderStatusName == 'Failed'
						|| $orderStatusName == 'Pending')
				 	{}else{EshopHelper::updateInventory($row);}
					$row->order_status_id = $this->getOrderStatusId($x_cod_transaction_state);
				 	$row->store();
					if(!$isConfirmation){
						$app->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=failure'));
					}else{
						echo "Failed";
						exit();
					}
				}break;
				case 6: {
					$session = JFactory::getSession();
					$session->set('eshop_payment_error_reason', $_POST['x_response_reason_text']);
					if($orderStatusName == 'Canceled Reversal'
						|| $orderStatusName == 'Pending')
				 	{}else{EshopHelper::updateInventory($row);}
					$row->order_status_id = $this->getOrderStatusId($x_cod_transaction_state);
				 	$row->store();
					if(!$isConfirmation){
						$app->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=failure'));
					}else{
						echo "Canceled Reversal";
						exit();
					}
				}break;
				case 9: {
					$session = JFactory::getSession();
					$session->set('eshop_payment_error_reason', $_POST['x_response_reason_text']);
					if($orderStatusName == 'Canceled Reversal'
						|| $orderStatusName == 'Pending')
				 	{}else{EshopHelper::updateInventory($row);}
					$row->order_status_id = $this->getOrderStatusId($x_cod_transaction_state);
				 	$row->store();
					if(!$isConfirmation){
						$app->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=failure'));
					}else{
						echo "Expired";
						exit();
					}
				}break;
				default: {
					$session = JFactory::getSession();
					$session->set('eshop_payment_error_reason', $_POST['x_response_reason_text']);
					if($orderStatusName == 'Failed'
						|| $orderStatusName == 'Pending')
				 	{}else{EshopHelper::updateInventory($row);}
					$row->order_status_id = $this->getOrderStatusId(4);
				 	$row->store();
					if(!$isConfirmation){
						$app->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=failure'));
					}else{
						echo "Failed";
						exit();
					}
				}break;
			}

			
		}else{
			$session = JFactory::getSession();
			$session->set('eshop_payment_error_reason', $_POST['x_response_reason_text']);
			if(!empty($result)){
				if($orderStatusName == 'Canceled'
				|| $orderStatusName == 'Pending')
		 		{}else{EshopHelper::updateInventory($row);}
				$row->order_status_id = $this->getOrderStatusId(4);
				$row->store();
			}
			
			if(!$isConfirmation){
				$app->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=failure'));
			}
		}
	}

	public function generateSignature($x_ref_payco, $trxId, $x_amount, $x_currency_code)
	{
		$x_signature = hash('sha256',
			trim($this->data["customerId"]).'^'
			.trim($this->data["pKey"]).'^'
			.$x_ref_payco.'^'
			.$trxId.'^'
			.$x_amount.'^'
			.$x_currency_code
		);

      return $x_signature;
	}

	public function getOrderStatusId($paymentStatus)
	{
		
		switch($paymentStatus){
			case 1: return 4;
			case 2: return 1;
			case 3: return 10;
			case 4: return 7;
			case 6: return 2;
			case 9: return 6;
			case 11: return 1;
			default: return 1;
		}
	}

	public function getTransactionDetails($x_ref_payco)
	{
		$url = "https://secure.epayco.co/validation/v1/reference/" . $x_ref_payco;
		$response = $this->apiService(
			$url,
			null,
			"GET"
		);
		return $response;
	}

	public function apiService($url, $data, $type, $cabecera = null)
	{
		$headers = [ "cache-control: no-cache", "accept: application/json", "content-type: application/json" ];

		try {
			if ($cabecera) {
				if (is_array($cabecera)) {
					$headers = array_merge($headers, $cabecera);
				} else {
					$headers[] = $cabecera;
				}
			}

			$jsonData = json_encode($data);
			if (function_exists('curl_init')) {
				$curl = curl_init();
				curl_setopt_array($curl, array(
					CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSLKEYPASSWD => '',
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 600,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => $type,
					CURLOPT_POSTFIELDS => "{$jsonData}",
					CURLOPT_HTTPHEADER => $headers
				));
	
				$resp = curl_exec($curl);
	
				if ($resp === false) {
						return array('curl_error' => curl_error($curl), 'curerrno' => curl_errno($curl));
				}
	
				curl_close($curl);
			}else{
				$resp =  @file_get_contents($url);
			}
			
			return json_decode($resp);
		} catch (\Exception $exception) {
			return $exception;
		}
	}
}