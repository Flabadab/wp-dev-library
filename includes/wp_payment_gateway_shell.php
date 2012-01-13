<?php
/**
 * General class to handle payment transactions
 * 
 * @author adamd
 * TODO: get purchase details
 * TODO: create form (show fields, purchase details)
 * TODO: validate input (respond if needed)
 * TODO: do payment
 * TODO: get response
 * TODO: handle response
 * TODO: record purchase details
 * TODO: display confirmation
 * 
 */
wp_library_include('common-functions.php');

abstract class WP_Payment_Gateway_Shell
{
	protected $_errors;
  	protected $_jqErrors;
  	protected $_billing;
  	protected $_shipping;
  	protected $_payment;
  	protected $_taxRate;
	
	private $apiUrl;
	
	/**
	 * Array of fields to display
	 * @var array
	 */
	protected $fields = array();
	
	/**
	 * Array of accepted Credit Card Types
	 * @var array
	 */
	private $cardTypes = array();
	
	
  	/**
   	 * Prepare the gateway to process a transaction.
   	 * Perhaps api credentials need to be set or other pre-sales setup
   	 * 
   	 * @param decimal saleData amount to charge to credit card
   	 */
  	public abstract function initCheckout($saleData);
  	
	/**
	 * Construct class by setting field data, and cardTypes
	 */
	protected function __construct()
	{
		wp_library_include( 'HtmlRenderer.php' );
		
		$this->cardTypes = array(
			'MasterCard' 		=> 'mastercard',
			'Visa'				=> 'visa',
			'American Express'	=> 'amex',
			'Discover'			=> 'discover'
		);
		
		$this->fields['billing'] = array(
      		'firstName' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'alpha|1|50'
			),
      		'lastName' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'alpha|1|50'
			),
      		'address' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'alphanumeric|2|150'
			),
      		'address2' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> false,
				'validation'	=> 'alphanumeric|2|150'
			),
      		'city' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'alpha|1|50'
			),
      		'state' => array(
				'type'		=> 'select',
				'value'		=> '',
				'options'	=> listStates(),
				'required' 	=> true,
				'validation'	=> 'select'
			),
      		'zip' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'numeric|5|13'
			),
      		'country' => array(
				'type'		=> 'select',
				'value'		=> '',
				'options'	=> listCountries(),
				'required' 	=> true,
				'validation'	=> 'select'
			),
    	);
    
    	$this->fields['shipping'] = array(
      		'firstName' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'alpha|1|50'
			),
      		'lastName' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'alpha|1|50'
			),
      		'address' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'alphanumeric|2|150'
			),
      		'address2' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> false,
				'validation'	=> 'alphanumeric|2|150'
			),
      		'city' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'alpha|1|50'
			),
      		'state' => array(
				'type'		=> 'select',
				'value'		=> '',
				'options'	=> listStates(),
				'required' 	=> true,
				'validation'	=> 'select'
			),
      		'zip' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'validation'	=> 'numeric|5|13'
			),
      		'country' => array(
				'type'		=> 'select',
				'value'		=> '',
				'options'	=> listCountries(),
				'required' 	=> true,
				'validation'	=> 'select'
			),
    	);
    
    	$this->fields['payment'] = array(
      		'cardType' => array(
				'type'		=> 'select',
				'value'		=> '',
    			'options'	=> $this->cardTypes,
				'required' 	=> true,
				'sanitize'	=> 'select'
			),
      		'cardNumber' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'sanitize'	=> 'numeric|16|19'
			),
      		'cardExpirationMonth' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'sanitize'	=> 'numeric|1|2'
			),
      		'cardExpirationYear' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'sanitize'	=> 'numeric|2|4'
			),
      		'securityId' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'sanitize'	=> 'numeric|3|5'
			),
      		'phone' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'sanitize'	=> 'numeric|10|20'
			),
      		'email' => array(
				'type'		=> 'text',
				'value'		=> '',
				'required' 	=> true,
				'sanitize'	=> 'email|1|75'
			),
      		'password' => array(
				'type'		=> 'password',
				'value'		=> '',
				'required' 	=> true,
				'sanitize'	=> 'password|5|50'
			),
      		'password2' => array(
				'type'		=> 'password',
				'value'		=> '',
				'required' 	=> true,
				'sanitize'	=> 'password|5|50'
			),
    	);
	}
	
	public function getFields() {
		
		return $this->fields;
	}
	
//	TODO: generic response handling
	protected abstract function handleResponse($response);
	
	public function getErrors() {
    	if(!is_array($this->_errors)) {
      		$this->_errors = array();
    	}
    	return $this->_errors;
  	}

  	public function getJqErrors() {
    	if(!is_array($this->_jqErrors)) {
      		$this->_jqErrors = array();
    	}
    	return $this->_jqErrors;
  	}

  	public function clearErrors() {
    	$this->_errors = array();
    	$this->_jqErrors = array();
  	}
	
	/**
	 * Function handles sanitization and storage of posted data
	 * one section at a time
	 * @param $fields
	 * @param $section
	 */
	private function handlePost($fields, $section)
	{
//		Cycle Through Fields and pull submitted values for validation
		foreach($this->fields[$section] as $key => $opts)
		{
			// Pull Validation Requirements
			$sanParts = explode('|', $sanVars);
			$sanType = $sanParts[0]; // text, select, checkbox, radio, textarea, etc.
			$sanMin = ($sanParts[2]) ? $sanParts[1] : 0; // Min characters allowed
			$sanMax = ($sanParts[2]) ? $sanParts[2] : $sanParts[1]; // Max Characters allowed
			$sanReq = $opts['required'];
			
			// Check if required and empty - throw error
			if($sanReq && (!array_key_exists($key, $fields) || empty($fields[$key]) || $fields[$key] == ''))
			{
				$this->addErr($key, $section, ' is required');
			} else {
				if(strlen($fields[$key]) < $sanMin){
					$this->addErr($key, $section, ' must be at least '.$sanMin.' characters long');
				}
				if(strlen($fields[$key]) > $sanMax){
					$this->addErr($key, $section, ' cannot exceed '.$sanMax.' characters in length');
				}
				
				/**
				 * case matching for validation of input.  Decided to throw exceptions instead of stripping chars
				 */
				switch($sanType){
					case 'alpha':
						if(preg_match('/^[a-zA-Z \-]+$/', $fields[$key])){
							$this->addErr($key, $section, ' should only be letters and spacers (` `, `-`, `_`) only');
						}
						break;
					case 'numeric':
						if(preg_match('/^[\d\-]+$/')){
							$this->addErr($key, $section, ' should be numbers and `-` only');
						}
						break;
					case 'alphanumeric':
						if(preg_match('^/[\w\-]+$/', $fields[$key])){
							$this->addErr($key, $section, ' should only be letters, numbers and spacers (` `, `-`, `_`) only');
						}
						break;
					case 'email':
						if(!validEmail($fields[$key])){
							$this->addErr($key, $section, ' is not a valid email address');
						}
						break;
					case 'select':
						$options = $opts['options'];
						if(!in_array($fields[$key], $options)){
							$this->addErr($key, $section, ' is an invalid option, please select a value from the drop-down');
						}
						break;
					default:
//						Nothing to see here, move along
						break;
				}
			}
		}
	}
	
	function addError($key, $section, $message)
	{
			$keyName = ucwords(preg_replace('/([A-Z])/', " $1", $key));
			$this->_errors[$section.' '.$key] = $keyName . $message;
	}
	/**
	Validate an email address.
	Provide email address (raw input)
	Returns true if the email address has the email 
	address format and the domain exists.
	*/
	function validEmail($email)
	{
   		$isValid = true;
   		$atIndex = strrpos($email, "@");
   		if (is_bool($atIndex) && !$atIndex)
   		{
      		$isValid = false;
   		}
   		else
   		{
      		$domain = substr($email, $atIndex+1);
      		$local = substr($email, 0, $atIndex);
      		$localLen = strlen($local);
      		$domainLen = strlen($domain);
      		if ($localLen < 1 || $localLen > 64)
      		{
         		// local part length exceeded
         		$isValid = false;
      		}
      		else if ($domainLen < 1 || $domainLen > 255)
      		{
         		// domain part length exceeded
         		$isValid = false;
      		}
      		else if ($local[0] == '.' || $local[$localLen-1] == '.')
      		{
         		// local part starts or ends with '.'
         		$isValid = false;
      		}
      		else if (preg_match('/\\.\\./', $local))
      		{
         		// local part has two consecutive dots
         		$isValid = false;
      		}
      		else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      		{
         		// character not valid in domain part
         		$isValid = false;
      		}
      		else if (preg_match('/\\.\\./', $domain))
      		{
         		// domain part has two consecutive dots
         		$isValid = false;
      		}
      		else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local)))
      		{
         		// character not valid in local part unless 
         		// local part is quoted
         		if (!preg_match('/^"(\\\\"|[^"])+"$/',
             		str_replace("\\\\","",$local)))
         		{
            		$isValid = false;
         		}
      		}
      		/*if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      		{
         		// domain not found in DNS
         		$isValid = false;
      		}*/
   		}
   		return $isValid;
	}
	
	/**
	 * Function to build sections and fields for form
	 */
	function buildForm(){
		/**
		 * Cycle through field sections
		 */
		foreach($fields as $section => $options)
		{
			/**
			 * Cycle through 
			 */
		}
	}
}