<?php
/*
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          http://recaptcha.net/plugins/php/
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin/create
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
 * AUTHORS:
 *   Mike Crawford
 *   Ben Maurer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class CaptchaResponse
{
    var $isValid;
    var $error;
    
    public function __construct($isValid, $error=null)
    {
        $this->isValid = $isValid;
        $this->error = $error;
    }
}

class captcha extends baseModule
{
    /**
     * The reCAPTCHA server URL's
     */
    private static $RECAPTCHA_API_SERVER = "http://www.google.com/recaptcha/api";
    private static $RECAPTCHA_API_SECURE_SERVER = "https://www.google.com/recaptcha/api";
    private static $RECAPTCHA_VERIFY_SERVER = "www.google.com";
    
    /**
     * Encodes the given data into a query string format
     * @param array $data array of string elements to be encoded
     * @return string encoded request
     */
    private static function _qsencode($data)
    {
        $req = '';
        foreach($data as $key=>$value)
           $req .= $key.'='.urlencode(stripslashes($value)).'&';
           
        // Cut last &
        return substr($req, 0, strlen($req)-1);
    }
    
    /**
     * Submits an HTTP POST to a reCAPTCHA server
     * @param array $data
     * @return array response
     */
    private static function _httpPost($data)
    {
        $host = self::$RECAPTCHA_VERIFY_SERVER;
	    $port = 80;
	    $path = '/recaptcha/api/verify';
	    $req = self::_qsencode($data);
	    
	    $http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;
        
        if(false == ($fs = @fsockopen($host, $port, $errno, $errstr, 10)))
        {
            kLog('captcha', 'Could not open socket to '.$host);
            return null;
        }
	    
        $response = '';
        fwrite($fs, $http_request);
        while(!feof($fs))
            $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);
        
        $response = explode("\r\n\r\n", $response, 2);
        
        return $response;
    }
    
    public static function checkCaptcha($privateKey)
    {
        $remoteip = $_SERVER['REMOTE_ADDR'];
        $challenge = getArgv('recaptcha_challenge_field');
        $response = getArgv('recaptcha_response_field');
        
        // Private key is required
        if($privateKey == null || $privateKey == '')
        {
            kLog('captcha', 'To use Captcha you must get an API key from <a href=\'https://www.google.com/recaptcha/admin/create\'>https://www.google.com/recaptcha/admin/create</a>');
            return new CaptchaResponse(false, 'invalid-site-private-key');
        }
        
        // Discard spam submissions
        if($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0)
            return new CaptchaResponse(false, 'incorrect-captcha-sol');
        
        $response = self::_httpPost(array(
            'privatekey' => $privateKey,
            'remoteip' => $remoteip,
            'challenge' => $challenge,
            'response' => $response
        ));
        
        if($response == null)
            return new CaptchaResponse(false, 'recaptcha-not-reachable');
        
        $answers = explode("\n", $response[1]);
        if($answers[0] == 'true')
            return new CaptchaResponse(true);
        else
            return new CaptchaResponse(false, $answers[1]);
    }
}

?>