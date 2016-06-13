<?php 
class ModelPaymentZARINPALZG extends Model {

  	public function getMethod() {
		$this->load->language('payment/zarinpalzg');

		if ($this->config->get('zarinpalzg_status')) {
      		  	$status = TRUE;
      	} else {
			$status = FALSE;
		}
		
		$method_data = array();
	
		if ($status) {  
      		$method_data = array( 
        		'code'         => 'zarinpalzg',
        		'title'      => $this->language->get('text_title'),
				'sort_order' => $this->config->get('zarinpalzg_sort_order')
      		);
    	}
   
    	return $method_data;
  	}
}
?>
