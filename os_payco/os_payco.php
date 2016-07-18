<?php
/**
 * @version		1.3.8
 * @package		Joomla
 * @subpackage	EShop
 * @author  	Giang Dinh Truong
 * @copyright	Copyright (C) 2012 Ossolution Team
 * @license		GNU/GPL, see LICENSE.php
 */
// no direct access
defined('_JEXEC') or die();

class os_payco extends os_payment
{
	/**
	 * Constructor functions, init some parameter
	 *
	 * @param object $config        	
	 */
	public function __construct($params)
	{
        $config = array(
            'type' => 0,
            'show_card_type' => false,
            'show_card_holder_name' => false
        );

        parent::__construct($params, $config);

		$this->mode = $params->get('payco_mode');
		if ($this->mode)
        {
            $this->url = 'https://secure.payco.co/payment.php';
        }
		else
        {
            $this->url = 'https://secure2.payco.co/payment.php';
        }
		$this->setData('p_cust_id_cliente', $params->get('payco_id'));
		$key=$params->get('payco_key');
		$id=$params->get('payco_id');
		$p_key=sha1($key.$id);
		$this->setData('p_key',$p_key);
		$this->setData('p_currency_code',  $params->get('payco_currency', 'COP'));
		$this->setData('lc', 'ES');
        $this->setData('charset', 'utf-8');
	}
	/**
	 * Process Payment
	 *
	 * @param array $params        	
	 */
	public function processPayment($data)
	{
		$siteUrl = JUri::root();
		
		$data['x_description'] = JText::sprintf('ESHOP_PAYMENT_FOR_ORDER', $data['order_id']);
        $testing = $this->mode ? "FALSE" : "TRUE";
        $countryInfo = EshopHelper::getCountry($data['payment_country_id']);
       	

        $countProduct = 1;
        $descripcion="";
		foreach ($data['products'] as $product)
		{
			$descripcion.=$product['product_name'].',';
			$countProduct++;
		}
		$descripcion=substr($descripcion,0,strlen($descripcion)-1);


		$authnetValues = array(
			
			'p_amount'=>round($data['total'], 2),
			'p_id_factura'=>$data['order_id'],
			'p_description'=>$descripcion,
			'p_tax'=>'0',
			'p_amount_base'=>'0',
			'p_test_request'=>$testing,
			'p_customer_ip'=>$_SERVER['REMOTE_ADDR'],
			'p_url_respuesta'=>$siteUrl . 'index.php?option=com_eshop&task=checkout.verifyPayment&payment_method=os_payco',
			'p_billing_tipo_doc'=>'CC',
			'p_billing_document'=>' ',
			'p_billing_name'=>$data['payment_firstname'],
			'p_billing_lastname'=>$data['payment_lastname'],
			'p_billing_email'=>$data['payment_email'],
			'p_billing_city'=>$data['payment_city'],
			'p_billing_state'=>$data['payment_zone_name'],
			'p_billing_country'=>$countryInfo->iso_code_2,
			'p_billing_address'=>$data['payment_address_1'],
			'p_billing_phone'=>$data['phone'],
			
			);


        foreach ($authnetValues as $key => $value)
        {
            $this->setData($key, $value);
        }


		$this->submitPost();
	}

	/**
	 * Validate the post data from paypal to our server
	 *
	 * @return string
	 */
	protected function validate()
	{
		$errNum = "";
		$errStr = "";
		$urlParsed = parse_url($this->url);
		$host = $urlParsed['host'];
		$path = $urlParsed['path'];
		$postString = '';
		$response = '';
		foreach ($_POST as $key => $value)
		{
			$this->postData[$key] = $value;
			$postString .= $key . '=' . urlencode(stripslashes($value)) . '&';
		}
		$postString .= 'cmd=_notify-validate';
		$fp = fsockopen($host, '80', $errNum, $errStr, 30);
		if (!$fp)
		{
			return false;
		}
		else
		{
			fputs($fp, "POST $path HTTP/1.1\r\n");
			fputs($fp, "Host: $host\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: " . strlen($postString) . "\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $postString . "\r\n\r\n");
			while (!feof($fp))
			{
				$response .= fgets($fp, 1024);
			}
			fclose($fp);
		}
        $extraData = "\nIPN Response from Paypal Server:\n " . $response;
        $this->logGatewayData($extraData);
		if ($this->mode)
		{
			if (eregi("VERIFIED", $response))
            {
                return true;
            }
			else
            {
                return false;
            }
		}
		else
		{
			return true;
		}
	}
	/**
	 * Process payment
	*/
	public function verifyPayment()
	{
		$ret = true;//$this->validate();
		$currency = new EshopCurrency();
		
		if ($_POST['x_respuesta'] == 'Aceptada' || $_POST['x_respuesta'] == 'Pendiente' )
        {
            $row = JTable::getInstance('Eshop', 'Order');
            $row->load($_POST['x_id_factura']);
            $row->transaction_id = $_POST['x_transaction_id'];
            if($_POST['x_respuesta'] == 'Aceptada'){
            	$row->order_status_id = EshopHelper::getConfigValue('complete_status_id');
           		$row->store();
           		EshopHelper::completeOrder($row);
            	JPluginHelper::importPlugin('eshop');
            	$dispatcher = JDispatcher::getInstance();
            	$dispatcher->trigger('onAfterCompleteOrder', array($row));
            }
            //Send confirmation email here
            if (EshopHelper::getConfigValue('order_alert_mail'))
            {
                EshopHelper::sendEmails($row);
            }

            JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=complete'));
        }
        else
        {
            $session = JFactory::getSession();
            $session->set('eshop_payment_error_reason', $_POST['x_response_reason_text']);
            JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_eshop&view=checkout&layout=failure'));
        }

	}
}