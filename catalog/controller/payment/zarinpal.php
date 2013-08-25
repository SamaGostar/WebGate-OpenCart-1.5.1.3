<?php
require_once(DIR_SYSTEM.'library/nuSoap/nusoap.php');
class ControllerPaymentZarinpal extends Controller {
	
	private $RefId = 0;
	
	protected function index() {
		$this->language->load('payment/zarinpal');
    	$this->data['button_confirm'] = $this->language->get('button_confirm');
		
		$this->data['text_wait'] = $this->language->get('text_wait');
		$this->data['text_ersal'] = $this->language->get('text_ersal');
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/zarinpal.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/zarinpal.tpl';
		} else {
			$this->template = 'default/template/payment/zarinpal.tpl';
		}
		
		$this->render();		
	}
	
	public function confirm() {
			
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$this->load->library('encryption');
		$encryption = new Encryption($this->config->get('config_encryption'));
		
		//$this->data['Amount'] = $this->currency->format($order_info['total'], 'TMN', $order_info['value'], FALSE);
		
		$this->data['Amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		
		//$this->data['Amount']=$this->data['Amount']\10;
	
		$this->data['PIN']=$this->config->get('zarinpal_PIN');
		
		$this->data['ResNum'] = $this->session->data['order_id'];

		$this->data['return'] = $this->url->link('checkout/success', '', 'SSL');
		//$this->data['return'] = HTTPS_SERVER . 'index.php?route=checkout/success';
		
		$this->data['cancel_return'] = $this->url->link('checkout/payment', '', 'SSL');
		//$this->data['cancel_return'] = HTTPS_SERVER . 'index.php?route=checkout/payment';

		$this->data['back'] = $this->url->link('checkout/payment', '', 'SSL');
		
		//$client = new SoapClient("https://www.zarinpal.com/pg/services/WebGate/wsdl");
		$client = new nusoap_client('https://de.zarinpal.com/pg/services/WebGate/wsdl', true);	
		
		if((!$client)){
			$json = array();
			$json['error']= "Can not connect to ZarinPal.<br>";	
		
			$this->response->setOutput(json_encode($json));
		}
	
		$amount = intval($this->data['Amount'])/$order_info['currency_value'];
		if($this->currency->getCode()=='RLS') {
			$amount = $amount/10;
		}
		//$callbackUrl = $this->url->https('payment/zarinpal/callback&order_id=' . $encryption->encrypt($this->session->data['order_id']));
		//$callbackUrl = HTTPS_SERVER . 'index.php?route=payment/zarinpal/callback&order_id=' . $encryption->encrypt($this->session->data['order_id']);
		$this->data['order_id'] = $encryption->encrypt($this->session->data['order_id']);
		//$callbackUrl = $this->url->link('payment/zarinpal/callback&order_id=' . $encryption->encrypt($this->session->data['order_id']));
		$callbackUrl  =  $this->url->link('payment/zarinpal/callback', 'order_id='. $this->data['order_id'], 'SSL');
		
		//$res=$client->PaymentRequest($this->data['PIN'], $amount, $callbackUrl, urlencode(' خريد شماره: '.$order_info['order_id']) );
		$parameters = array(
						array(
								'MerchantID' 	=> $this->data['PIN'],
								'Amount' 		=> $amount,
								'Description' 	=> 'خريد شماره: '.$order_info['order_id'],
								'Email' 		=> '',
								'Mobile' 		=> '',
								'CallbackURL' 	=> $callBackUrl
							)
						);
		$res = $client->call('PaymentRequest', $parameters);
		if($res->Status == 100){
			$this->data['action'] = 'https://www.zarinpal.com/pg/StartPay/' . $res->Authority;
			$json = array();
			$json['success']= $this->data['action'];	
			$this->response->setOutput(json_encode($json));
		} else {
			echo'ERR: '.$res->Status;
			$this->CheckState($res->Status);
			//die();
		}
	}

	public function CheckState($status) {
		$json = array();
		switch($status){
		
			case "-1" :
				$json['error']="اطلاعات ارسالی ناقص می باشند";
				break;
			case "-2" :
				$json['error']="وب سرويس نا معتبر می باشد";
				break;
			case "0" :
			 $json['error']="عمليات پرداخت طی نشده است";
				break;
			case "1" :
				break;
			case "-11" :
				$json['error']="مقدار تراکنش تطابق نمی کند";
				break;
				
			case "-12" :
				$json['error']="زمان پرداخت طی شده و کاربر اقدام به پرداخت صورتحساب ننموده است";
				break;
			
			default :
				$json['error']= "خطای نامشخص;";
				break;
		}	
		
		$this->response->setOutput(json_encode($json));
	}

	function verify_payment($authority, $amount){

		if($authority){
			//$client = new SoapClient("http://pg.zarinpal.com/services/WebGate/wsdl");
			 $client = new nusoap_client('https://de.zarinpal.com/pg/services/WebGate/wsdl', true);	
			if ((!$client)){
				echo  "Error: can not connect to ZarinPal.<br>";return false;
			} else {
				$this->data['PIN'] = $this->config->get('zarinpal_PIN');
				//$res = $client->PaymentVerification($this->data['PIN'], $authority ,$amount);
				$parameters = array(
										array(
												'MerchantID'	 => $this->data['PIN'],
												'Authority' 	 => $authority,
												'Amount'		 => $amount
										)
									);
				$res = $client->call('PaymentVerification', $parameters);
			
				$this->CheckState($res->Status);
				
				if($res->Status==100){
					$this->RefId = $res->RefId;
					return true;
				} else {
					echo'ERR: '.$res->Status;
					return false;
				}
			}
		} else {
			return false;
		}
		return false;
	}

	public function callback() {
		$this->load->library('encryption');

		$encryption = new Encryption($this->config->get('config_encryption'));
		$au = $this->request->get['Authority'];
		$status  = $this->request->get['Status'];
		$order_id = $encryption->decrypt($this->request->get['order_id']);
		$MerchantID=$this->config->get('zarinpal_PIN');
		
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		//$Amount = $this->currency->format($order_info['total'], 'RLS', $order_info['value'], FALSE);
		
		$Amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);		//echo $this->data['Amount'];
		
		$amount = $Amount/$order_info['currency_value'];
		if ($order_info && $status == "OK" ) {
			if(($this->verify_payment($au, $amount))) {
				$this->model_checkout_order->confirm($order_id, $this->config->get('zarinpal_order_status_id'), 'شماره سند تراکنش: '. $this->RefId);
				
				$this->response->setOutput('<html><head><meta http-equiv="refresh" CONTENT="2; url=' . $this->url->link('checkout/success') . '"></head><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">با تشکر پرداخت شما با شماره تراکنش '. $this->RefId .' تکمیل شد.لطفا چند لحظه صبر کنید و یا  <a href="' . $this->url->link('checkout/success') . '"><b>اینجا کلیک نمایید</b></a></td><td>&nbsp;</td></tr></table></body></html>');
			}
		} else {
			$this->response->setOutput('<html><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">.<br /><br /><a href="' . $this->url->link('checkout/cart').  '"><b>بازگشت به فروشگاه</b></a></td><td>&nbsp;</td></tr></table></body></html>');
		}
	}
	
}
?>
