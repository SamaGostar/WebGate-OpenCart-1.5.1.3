<?php
//require_once(DIR_SYSTEM.'library/nuSoap/nusoap.php');
class ControllerPaymentZarinpalwg extends Controller {
	protected function index() {
		$this->language->load('payment/zarinpalwg');
    	$this->data['button_confirm'] = $this->language->get('button_confirm');
		
		$this->data['text_wait'] = $this->language->get('text_wait');
		$this->data['text_ersal'] = $this->language->get('text_ersal');
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/zarinpalwg.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/zarinpalwg.tpl';
		} else {
			$this->template = 'default/template/payment/zarinpalwg.tpl';
		}
		
		$this->render();		
}
public function confirm() {
		
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$this->load->library('encryption');
		
		$encryption = new Encryption($this->config->get('config_encryption'));
		
		
		
		
		$this->data['Amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);		//echo $this->data['Amount'];
			//$amount = intval($Amount) /$order_info['currency_value'];
			if($this->currency->getCode()=='RLS') {
		$this->data['Amount']=$this->data['Amount'] / 10;
	}
		
		$this->data['PIN']=$this->config->get('zarinpalwg_PIN');
		
		
		$this->data['ResNum'] = $this->session->data['order_id'];

		$this->data['return'] = $this->url->link('checkout/success', '', 'SSL');
		//$this->data['return'] = HTTPS_SERVER . 'index.php?route=checkout/success';
		
		$this->data['cancel_return'] = $this->url->link('checkout/payment', '', 'SSL');
		//$this->data['cancel_return'] = HTTPS_SERVER . 'index.php?route=checkout/payment';

		$this->data['back'] = $this->url->link('checkout/payment', '', 'SSL');

		$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);		//echo $this->data['Amount'];
			//$amount = intval($Amount) /$order_info['currency_value'];
			if($this->currency->getCode()=='RLS') {
		$amount=$amount / 10;
	}
	
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
	
	
	$Description = $order_info['comment'];
	
	
	
		$this->data['order_id'] = $encryption->encrypt($this->session->data['order_id']);
		$callbackUrl  =  $this->url->link('payment/zarinpalwg/callback', 'order_id=' . $this->data['order_id'], 'SSL');
		
		$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8')); 

	$result = $client->PaymentRequest(
						array(
								'MerchantID' 	=> $this->data['PIN'],
								'Amount' 	=> $amount,
								'Description' 	=> $Description,
								'Email' 	=> $Email,
								'Mobile' 	=> $Mobile,
								'CallbackURL' 	=> $callbackUrl
							)
	);

	//Redirect to URL You can do it also by creating a form
	if($result->Status == 100)
	{
		Header('Location: https://www.zarinpal.com/pg/StartPay/'.$result->Authority);
	} else {
		echo'ERR: '.$result->Status;
	}


	$PayPath = 'https://www.zarinpal.com/pg/StartPay/'.$result->Authority;
	$Status = $res->Status;
	
	if($Status == 100 )
	{

		$this->data['action'] = $PayPath;
		$json = array();
		$json['success']= $this->data['action'];	
	
		$this->response->setOutput(json_encode($json));
		
		} else {
			
			$this->CheckState($Status);
			//die();
		}

//
		
	
		
}

	public function CheckState($status) {
		$json = array();
		$json['error']= 'کد خطا :'.$status;
		$this->response->setOutput(json_encode($json));

	
	}	
			
		
	


function verify_payment($authority, $amount){

	if($authority){
		$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8')); 
		if ((!$client))
			{echo  "Error: can not connect to zarinpalwg.<br>";return false;}
		
		else {
			$this->data['PIN'] = $this->config->get('zarinpalwg_PIN');

		
		$res = $client->PaymentVerification(
						  	array(
									'MerchantID'	 => $data['PIN'],
									'Authority' 	 => $authority,
									'Amount'	 => $amount
								)
		);


	
		
			$this->CheckState($res->Status);
			
			if($res==100)
				return true;

			else {
				return false;
			}
		
		}
	} 
	
	else {
		return false;
	}
	
	
	return false;
}

	public function callback() {
		$this->load->library('encryption');

		$encryption = new Encryption($this->config->get('config_encryption'));
		
		//$order_id = $encryption->decrypt($this->request->get['order_id']);
		$MerchantID=$this->config->get('zarinpalwg_PIN');
	    if($_GET['Authority']!== ''){
		$authority = $_GET['Authority'];
		
//Your Order ID
          $order_id=$this->session->data['order_id'];
        $this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
	$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);		//echo $this->data['Amount'];
			//$amount = intval($Amount) /$order_info['currency_value'];
			if($this->currency->getCode()=='RLS') {
		$amount=$amount / 10;
	
			}
		$$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8')); 

				$res = $client->PaymentVerification(
						  	array(
									'MerchantID'	 => $MerchantID,
									'Authority' 	 => $authority,
									'Amount'	 => $amount
								)
		);
		
		$res = $client->verification($MerchantID,$amount,$au );
		
		//$Amount = $this->currency->format($order_info['total'], 'RLS', $order_info['value'], FALSE);
		
		
		
		$Ref = $res->RefID;
		$Status = $res->Status;
		if($Status == 100)// Your Peyment Code Only This Event
		{
				$this->model_checkout_order->confirm($order_id, $this->config->get('zarinpalwg_order_status_id'),'شماره رسيد ديجيتالي; : '.$Ref);
				
				$this->response->setOutput('<html><head><meta http-equiv="refresh" CONTENT="2; url=' . $this->url->link('checkout/success') . '"></head><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma;background: #EDEBFE; font-size: 14px; direction: rtl; text-align: right;">با تشکر پرداخت تکمیل شد.لطفا چند لحظه صبر کنید و یا  <a href="' . $this->url->link('checkout/success') . '"><b>اینجا کلیک نمایید</b></a></td><td>&nbsp;</td></tr><tr><td colspan="2"> شماره رسيد ديجيتالي:'.$Ref.'</td></tr></table></body></html>');
			}
		else {
			$this->response->setOutput('<html><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray;background: #EDEBFE; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">خطا در عملیات پردازش پرداخت:<br />کد خطا:'.$Status.'<br /><br /><a href="' . $this->url->link('checkout/cart').  '"><b>بازگشت به فروشگاه</b></a></td><td>&nbsp;</td></tr></table></body></html>');
		}
	}
		 else {
			$this->response->setOutput('<html><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray;background: #EDEBFE; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">.		بازگشت از عمليات پرداخت، خطا در انجام عملیات پرداخت ( پرداخت ناموق ) !<br /><br /><a href="' . $this->url->link('checkout/cart').  '"><b>بازگشت به فروشگاه</b></a></td><td>&nbsp;</td></tr></table></body></html>');
		}
	}
	
}
?>
