<?php

/**
* 
*/
class ReCaptcha
{
	public $enabled;
	private $site_key;
	private $secret_key;
	private $config = array(
		'api' => 'https://www.google.com/recaptcha/api.js',
		'container' => '<div class="g-recaptcha" data-sitekey="%site_key"></div>',
		'verify_api' => 'https://www.google.com/recaptcha/api/siteverify'
	);

	/**
	 * Creates the ReCaptcha object.
	 *
	 * If the captcha is set to be disabled, the verify() method will always return true.
	 */
	function __construct($enabled, $site = '', $secret = ''){
		if(!isset($enabled) OR !is_bool($enabled))
			throw new Exception('Invalid data was provided. Parameter enabled must be boolean');

		if($enabled){
			if(!isset($site) OR !is_string($site) OR !isset($secret) OR !is_string($secret))
			throw new Exception('Invalid data was provided. Check your site_key and your secret_key');

			$this->enabled = TRUE;
			$this->site_key = $site;
			$this->secret_key = $secret;
		}else{
			$this->enabled = FALSE;
		}
	}

	/**
	 * Returns the API url or an empty string if the captcha is disabled.
	 */
	function getApiURL(){
		return $this->enabled ? $this->config['api'] : '';
	}

	/**
	 * Returns the captcha container or an empty string if the captcha is disabled.
	 */
	function getContainer(){
		return $this->enabled ? str_replace('%site_key', $this->site_key, $this->config['container']) : '';
	}

	/**
	 * Checks whether the used has passed the captcha correctly.
	 *
	 * Returns true if they have or the captcha is disabled, and false if the captcha was failed
	 */
	function verify($response = null){
		if($this->enabled){
			if(is_null($response) AND isset($_POST['g-recaptcha-response'])) $response = $_POST['g-recaptcha-response'];
			if(is_null($response)) throw new Exception('No response was provided and no POST "g-recaptcha-response" variable was found.');

		    $data = array(
		        'secret' => $this->secret_key,
		        'response' => $response,
		        'remoteip' => $_SERVER['REMOTE_ADDR']
		    );

		    $options = array(
		        'http' => array(
		            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		            'method'  => 'POST',
		            'content' => http_build_query($data),
		        ),
		    );

		    $context = stream_context_create($options);
		    $result = file_get_contents($this->config['verify_api'], false, $context);
		    $result = json_decode($result, TRUE);
    		return $result['success'];
		}else{
			//If the captcha is disabled we can safely return true
			return true;
		}
	}
}

?>