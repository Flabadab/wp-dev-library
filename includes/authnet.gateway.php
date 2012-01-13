<?php

wp_library_include('includes/wp_payment_gateway_shell.php');

class AuthNet_Gateway extends WP_Payment_Gateway_Shell {
	
	
	
	function __construct()
	{
		//parent::__construct();
		$this->apiUrl = ''; // add AuthNet api url

		$this->fields['general'] = array(
			'x_version'	=> array(
				'type'		=> 'hidden',
				'value'		=> '3.1'
			),
			'x_delim_data'	=> array(
				'type'		=> 'hidden',
				'value'		=> 'TRUE'
			),
			'x_delim_char'	=> array(
				'type'		=> 'hidden',
				'value'		=> '|'
			),
			'x_url'	=> array(
				'type'		=> 'hidden',
				'value'		=> 'FALSE'
			),
			'x_type'	=> array(
				'type'		=> 'hidden',
				'value'		=> 'AUTH_CAPTURE'
			),
			'x_method'	=> array(
				'type'		=> 'hidden',
				'value'		=> 'CC'
			),
			'x_relay_response'	=> array(
				'type'		=> 'hidden',
				'value'		=> 'FALSE'
			),
		);
	}
	
	function initCheckout($total)
	{
		$this->fields['general'] = array(
			'x_login'	=> array(
				'value'		=> '' // add AuthNet username
			),
			'x_tran_key'	=> array(
				'value'		=> '' // add AuthNet transaction key
			),
			'x_card_num'	=> array(
				'value'		=> $this->fields['payment']['cardNumber']['value']
			),
			'x_exp_date'	=> array(
				'value'		=> $this->fields['payment']['cardExpirationMonth']['value'].'/'.$this->fields['payment']['cardExpirationYear']['value']
			),
			'x_first_name'	=> array(
				'value'		=> $this->fields['billing']['firstName']['value']
			),
			'x_last_name'	=> array(
				'value'		=> $this->fields['billing']['lastName']['value']
			),
			'x_address'	=> array(
				'value'		=> $this->fields['billing']['address']['value']
			),
			'x_city'	=> array(
				'value'		=> $this->fields['billing']['city']['value']
			),
			'x_state'	=> array(
				'value'		=> $this->fields['billing']['state']['value']
			),
			'x_zip'	=> array(
				'value'		=> $this->fields['billing']['zip']['value']
			),
			'x_country'	=> array(
				'value'		=> $this->fields['billing']['country']['value']
			),
			'x_phone'	=> array(
				'value'		=> $this->fields['payment']['phone']['value']
			),
			'x_email'	=> array(
				'value'		=> $this->fields['payment']['email']['value']
			),
			'x_amount'	=> array(
				'value'		=> $total
			)
		);
	}
	
	function doSale()
	{
		if(!$this->_errors){
			$response = wp_remote_post($this->apiUrl, $sale_data);
			$this->handleResponse($response);
		}
	}
	
	protected function handleResponse($response)
	{
		echo 'not ready yet';
	}
	
	function getResponse(){
		
	}
}