<?php
/**
 * Form Validation
 *
 * Checks for (and processes) submitted form $_POST and $_FILE data. Allowing
 * callbacks for custom processing and verification.
 *
 * @package		MicroMVC
 * @author		David Pennington
 * @copyright	(c) 2011 MicroMVC Framework
 * @license		http://micromvc.com/license
 * 
 * @changes
 * 	2011-08-10	non-required fields don't trigger validation errors for numeric, match, etc if empty values
 ********************************** 80 Columns *********************************
 */
class Validation
{

	// The array of errors (if any)
	public $errors = array();

	// The text to put before an error
	public $error_prefix = '<div class="error form_error">';

	// The text to put after an error
	public $error_suffix = '</div>';

	// Should we add a token to each form to prevents CSFR? (requires Session class)
	public $token = TRUE;
	
	/**
	 * Save post values for perusal
	 * @var unknown_type
	 */
	private $values = array();
	/**
	 * Retrieve specified value (from post, after validation has been run)
	 * @param string $key
	 * @param string $section {optional} if given, get from subsection of post
	 */
	public function value($key, $section = NULL){
		//CANNOT USE v(), since it modifies the actual value - in this case, POST
		
		if( NULL === $section ) {
			return kv($_POST, $key);
		}
		
		return kv($_POST, $section, $key);
	}//--	fn	values
	
	/**
	 * Run the given post data through the field rules to ensure it is valid.
	 *
	 * @param array $fields and matching rules
	 * @return boolean
	 */
	public function run(array $fields)
	{
		
		#region -------- use wp_nonce instead
		if(empty($_POST))
		{
			$this->create_token();

			return FALSE;
		}
		
		
		// First, validate the token
		$this->validate_token();
		
		//added nonce validation field to form html in wp_mvc.php
		
		//validate nonce if post
		
		#endregion -------- use wp_nonce instead

		foreach($fields as $section_id => $section):
		foreach($section as $field => $rules) {
			
			$rules = explode('|', $rules);
			
			// Fetch the post data
			$data = $this->value($field, $section_id);
			
			// Skip empty fields that are not required
			if( ! in_array('required', $rules)  AND  ( NULL === $data )) continue;
			
			//If the data is a non-empty string
			if(is_string($data) AND $data)
			{
				$data = trim($data); // Auto-trim
			}

			foreach($rules as $rule)
			{
				$params = NULL;

				//Check for extra functions params like "rule[my_params]"
				if (strpos($rule, '[') !== FALSE)
				{
					//Fetch the public function arguments
					preg_match('/([a-z0-9_]+)\[(.*?)\]/i', $rule, $matches);

					//Fetch the rule name
					$rule = $matches[1];

					//Get the params
					$params = $matches[2];
				}

				if(method_exists($this, $rule))
				{
					$result = $this->$rule($field, $data, $params);
				}
				elseif(function_exists($rule))
				{
					$result = $rule($data);
				}
				else
				{
					throw new Exception (sprintf(Lang::get('validation_rule_not_found'), $rule));
				}

				// Rules return boolean false on failure
				if($result === FALSE) break;

				// Rules return boolean true on success
				if($result !== TRUE)
				{
					// All other rules return data
					$data = $result;
				}
			}

			// Commit any changes (for later access)
			$_POST[$section_id][$field] = $data;
			
		}// foreach fields in section
		endforeach;//	foreach sections

		// If there were no problems
		if( ! $this->errors) return TRUE;

		// Create a new form token
		$this->create_token();
	}


	/**
	 * Print the errors from the form validation check
	 *
	 * @return string
	 */
	public function display_errors($prefix = '', $suffix = '')
	{
		if(empty($this->errors)) return;

		$output = '';
		foreach($this->errors as $error)
		{
			$output .= ($prefix ? $prefix : $this->error_prefix)
					. $error
					. ($suffix ? $suffix : $this->error_suffix). "\n";
		}

		return $output;
	}


	/**
	 * Return the error (if any) for a given field
	 *
	 * @param $field
	 * @param boolean $prefix TRUE to wrap error
	 * @return string
	 */
	public function error($field, $prefix = TRUE)
	{
		if( ! empty($this->errors[$field]))
		{
			if($prefix)
			{
				return $this->error_prefix . $this->errors[$field] . $this->error_suffix;
			}
			return $this->errors[$field];
		}
	}
	
	/**
	 * Set a validation error message
	 *
	 * @param string $field name of the form element
	 * @param string $error to set
	 */
	public function set_error($field, $error)
	{
		$this->errors[$field] = $error;
	}

	
	#region ====================== abt additions ==============================
	
	private static $msg_prefix = '<div class="%s">';
	private static $msg_suffix = "</div>\n";
	
	/**
	 * Generic call to output a message in a container (prefix, suffix)
	 * @param string $msg what to output
	 * @param string $style css styles to apply
	 */
	static function msg($msg, $style = 'msg'){
		return sprintf(self::$msg_prefix, $style) . $msg . self::$msg_suffix;
	}//--	fn	msg
	
	/**
	 * Print a success message
	 * @author jeremys
	 * 
	 * @param string $msg
	 */
	static function success_message($msg){
		return self::msg($msg, 'updated success');
	}//--	fn	success_message
	/**
	 * Print a fail message
	 * @author jeremys
	 * 
	 * @param string $msg
	 */
	static function error_message($msg){
		return self::msg($msg, 'error form_error');
	}//--	fn	error_message
	
	
	/**
	 * Since the rules list and fields list are very similar, initialize one from other
	 * 
	 * @param array $rules the list of rules
	 * @param bool $has_sections {optional} if false pad as though had sections
	 * @param string/bool $submit {optional} if omitted, will create a default submit button;give as string to set the submit 'label', give as false to explicitly prevent adding submit button
	 */
	static function fields_from_rules($rules, $has_sections = false, $submit = NULL){
		//fake sections
		if( ! $has_sections ){
			$rules = array( 'default'=>$rules );
		}
		
		
		foreach($rules as $section_id => &$section){
			foreach($section as $field => &$rule){
				$rule = array('validation'=>$rule);
			}// foreach rules
		}// foreach sections
		
		if( NULL === $submit ) {
			$rules['buttons'] = array();
			$rules['buttons']['submit'] = array('type'=>'submit', 'value'=> 'Submit');
		}
		else if ( false !== $submit ){
			$rules['buttons'] = array();
			$rules['buttons']['submit'] = array('type'=>'submit', 'value'=> $submit);
		}
			
		return $rules; //as fields!
	}//--	fn	fields_from_rules
	
	/**
	 * Since the rules list and fields list are very similar, initialize one from other
	 * 
	 * @param array $fields the list of rules
	 * @param bool $has_sections {optional} if false pad as though had sections
	 */
	static function rules_from_fields($fields, $has_sections = TRUE){
		//fake sections
		if( ! $has_sections ){
			$fields = array( $fields );
		}
		
		//loop through each section
		foreach($fields as $section_id => &$section){
			//loop through all fields in section
			foreach($section as $field_id => &$field){
				//check if field has validation set, if so, keep it
				if(isset($field['validation'])){
					$field = $field['validation'];
				}
				//otherwise, clear it
				else {
					unset($fields[$section_id][$field_id]); 
				}
			}
		}
			
		return $fields;	//as rules!
	}//--	fn	fields_from_rules
	
	#endregion ====================== abt additions ==============================
	

	
	
	
	#endregion ====================== Validation Methods ==============================
	
	/**
	 * String
	 *
	 * @param string $field name of the form element
	 * @param mixed $word to validate
	 * @return boolean
	 */
	public function string($field, $data)
	{
		if($data = trim(str($data))) return $data;
		$this->errors[$field] = sprintf(Lang::get('validation_required'), $field);
		return FALSE;
	}


	/**
	 * Required (not empty)
	 *
	 * @param string $field name of the form element
	 * @param mixed $word to validate
	 * @return boolean
	 */
	public function required($field, $data)
	{
		if( ! empty($data) || ( is_numeric($data) && 0 === (int)$data ) ) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_required'), $field);
		return FALSE;
	}


	/**
	 * Set (even if empty)
	 *
	 * @param string $field name of the form element
	 * @param mixed $word to validate
	 * @return boolean
	 */
	public function set($field, $data)
	{
		if(isset($_POST[$field]))return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_set'), $field);
		return FALSE;
	}


	/**
	 * Alphabetic
	 *
	 * @param string $field name of the form element
	 * @param mixed $word to validate
	 * @return boolean
	 */
	public function alpha($field, $word)
	{
		if(preg_match("/^([a-zA-Z])*$/i",$word))return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_alpha'), $field);
		return FALSE;
	}


	/**
	 * Alphabetic and Numeric
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @return boolean
	 */
	public function alpha_numeric($field, $data)
	{
		if(preg_match("/^([a-zA-Z0-9])*$/i", $data)) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_alpha_numeric'), $field);
		return FALSE;
	}


	/**
	 * Numeric digits (0-9) only
	 *
	 * @param string $field name of the form element
	 * @param mixed $number to validate
	 * @return boolean
	 */
	public function digits_only($field, $number)
	{
		//if(is_numeric($number)) return TRUE;
		if(ctype_digit($number) || empty($number) ) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_numeric'), $field);
		return FALSE;
	}

	/**
	 * Number - different than numeric
	 *
	 * @param string $field name of the form element
	 * @param mixed $number to validate
	 * @return boolean
	 */
	public function numeric($field, $number)
	{
		//if(is_numeric($number)) return TRUE;
		if(is_numeric($number) || empty($number) ) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_numeric'), $field);
		return FALSE;
	}
	
	#region -------------- ABT custom methods -----------------------
	
	/**
	 * bool - only allow TRUE/FALSE or 1/0
	 *
	 * @param string $field name of the form element
	 * @param mixed $value to validate
	 * @return boolean
	 */
	public function bool($field, $value)
	{
		if( TRUE === $value || FALSE === $value || 1 === $value || 0 === $value || '1' === $value || '0' === $value )return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_bool'), $field);
		return FALSE;
	}

	/**
	 * Date
	 *
	 * @param string $field name of the form element
	 * @param mixed $value to validate
	 * @return boolean
	 */
	public function date($field, $value)
	{
		if( empty($value) )return TRUE;	//ignore empty values
		
		$result = date_parse($value);
		if( 0 === $result['error_count'] )return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_date'), $field);
		return FALSE;
	}
	
	/**
	 * Alphabetic and Numeric
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @return boolean
	 */
	public function simple_chars($field, $data)
	{
		if(preg_match("/^([a-zA-Z0-9-_+\/&])*$/i", $data)) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_simple_chars'), $field);
		return FALSE;
	}
	
	/**
	 * Currency
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @return boolean
	 * 
	 * @source http://stackoverflow.com/questions/354044/what-is-the-best-u-s-currency-regex/354365#354365
	 */
	public function currency($field, $data)
	{
		if( empty($data) )return TRUE;	//ignore empty values
		
		if(preg_match("/^-?(?:0|[1-9]\d{0,2}(?:,?\d{3})*)(?:\.\d+)?$/i", $data)) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_currency'), $field);
		return FALSE;
	}
	
	/**
	 * Currency
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @return boolean
	 * 
	 * @source http://stackoverflow.com/questions/354044/what-is-the-best-u-s-currency-regex/354365#354365
	 */
	public function percent($field, $data)
	{
		if( empty($data) )return TRUE;	//ignore empty values
		
		if(preg_match("/(?!^0*$)(?!^0*\.0*$)^\d{1,2}(\.\d{1,2})?$/i", $data)) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_percent'), $field);
		return FALSE;
	}
	
	#endregion -------------- ABT custom methods -----------------------
	
	/**
	 * Match one field against another
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @param string $field2 name of the other form element
	 * @return boolean
	 */
	public function matches($field, $data, $field2)
	{
		if (isset($_POST[$field2]) AND $data === $_POST[$field2]) return TRUE;
		$this->errors[$field] = sprintf(lang::get('validation_matches'), $field, $field2);
		return FALSE;
	}


	/**
	 * Minimum Length
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @param int $length of the string
	 * @return boolean
	 */
	public function min_length($field, $data, $length)
	{
		if(mb_strlen($data) >= $length) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_min_length'), $field, $length);
		return FALSE;
	}


	/**
	 * Maximum Length
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @param int $length of the string
	 * @return boolean
	 */
	public function max_length($field, $data, $length)
	{
		if(mb_strlen($data)<=$length)return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_max_length'), $field, $length);
		return FALSE;
	}


	/**
	 * Exact Length
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @param int $length of the string
	 * @return boolean
	 */
	public function exact_length($field, $data, $length)
	{
		if(mb_strlen($data) == $length) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_exact_length'), $field, $length);
		return FALSE;
	}


	/**
	 * Check to see if the email entered is valid
	 *
	 * @param string $field name of the form element
	 * @param mixed $email to validate
	 * @return boolean
	 */
	public function valid_email($field, $email)
	{
		if(preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email) || empty($email) ) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_valid_email'), $field);
		return FALSE;
	}


	/**
	 * Tests a string for characters outside of the Base64 alphabet
	 * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
	 *
	 * @param string $field name of the form element
	 * @param mixed $data to validate
	 * @return boolean
	 */
	public function valid_base64($field, $data)
	{
		if(!preg_match('/[^a-zA-Z0-9\/\+=]/', $data)) return TRUE;
		$this->errors[$field] = sprintf(Lang::get('validation_valid_base64'), $field);
		return FALSE;
	}

	#endregion ====================== Validation Methods ==============================
	
	
	
	
	
	#region ====================== Tokens and Nonces ==============================
	
	/**
	 * Each time a form is created we will create a token
	 * then when the user submits that form we will check
	 * to make sure the tokens match.
	 */
	public function create_token()
	{
		if($this->token AND class_exists('session', FALSE))
		{
			Session::token();
		}
	}


	/**
	 * Validate the form token
	 *
	 * @return boolean
	 */
	public function validate_token()
	{
		if( ! $this->token OR ! class_exists('session', FALSE))
		{
			return TRUE;
		}
		
		if(Session::token(post('token'))) return TRUE;
		$this->errors['token'] = sprintf(Lang::get('validation_invalid_token'), 'token');
		return FALSE;
	}
	
	/**
	 * use WP nonce instead of session token (default nonce field name if none given)
	 * @author jeremys
	 * @since WP_Library 0.2.1
	 */
	public function create_nonce($action, $nonce_field = NULL){
		//use WP nonce instead of session token (default nonce field name if none given)
		wp_nonce_field($action, v($nonce_field, 'mvc_nonce'));
	}//--	fn	create_nonce
	
	/**
	 * use WP nonce instead of session token (default nonce field name if none given)
	 * @author jeremys
	 * @since WP_Library 0.2.1
	 */
	public function validate_nonce($action, $nonce_field = NULL){
		return wp_verify_nonce(v($nonce_field, 'mvc_nonce'), $action);
	}

	#endregion ====================== Tokens and Nonces ==============================
	
}

// END
