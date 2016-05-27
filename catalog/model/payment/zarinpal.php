<?php

class ModelPaymentZarinpal extends Model
{
    public function getMethod()
    {
        $this->load->language('payment/zarinpal');

        if ($this->config->get('zarinpal_status')) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = [];

        if ($status) {
            $method_data = [
                'code'         => 'zarinpal',
                'title'        => $this->language->get('text_title'),
                'sort_order'   => $this->config->get('zarinpal_sort_order'),
              ];
        }

        return $method_data;
    }
}
