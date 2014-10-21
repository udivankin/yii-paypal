<?php

/**
 * PayPalExpress.php
 *
 * https://github.com/udivankin/yii-paypal
 *
 * @author STDev <yii@st-dev.com>
 * @author allx <alexey@udivankin.ru>
 * @copyright 2014 allx <alexey@udivankin.ru>
 * @license released under dual license BSD License and LGP License
 * @package PayPal
 * @version 1.0
 */
class PayPalExpress extends CApplicationComponent
{

    /**
      # PayPal credentials (Username, Password, Signature, PaypalURL and PaypalEND) for both
     */
    public $credentials;

    /**
      # The url (relative to base url) to return the customer after a successful payment
     */
    public $apiLive;

    /**
      # The url (relative to base url) to return the customer after a successful payment
     */
    public $returnUrl;

    /**
      # The url (relative to base url) to return the customer if he/she cancels the payment
     */
    public $cancelUrl;

    /**
      # Default currency to use;
     */
    public $currency = 'GBP';

    /**
      # Default description to use;
     */
    public $defaultDescription = '';

    /**
      # Default Quantity to use;
     */
    public $defaultQuantity = '1';

    /**
      USE_PROXY: Set this variable to TRUE to route all the API requests through proxy.
      like define('USE_PROXY',TRUE);
     */
    public $useProxy = false;
    public $proxyHost = '127.0.0.1';
    public $proxyPort = '80';

    /* Define the PayPal URL. This is the URL that the buyer is
    first sent to to authorize payment with their paypal account
    change the URL depending if you are testing on the sandbox
    or going to the live PayPal site
    For the sandbox, the URL is
    https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
    For the live site, the URL is
    https://www.paypal.com/webscr&cmd=_express-checkout&token=
    */
    public $paypalUrl;

    /**
      # Version: this is the API version in the request.
      # It is a mandatory parameter for each API request.
      # The only supported value at this time is 2.3
     */
    public $version = '3.0';

    public function init()
    {
        parent::init();
        //set return and cancel urls
        $this->returnUrl = Yii::app()->createAbsoluteUrl($this->returnUrl);
        $this->cancelUrl = Yii::app()->createAbsoluteUrl($this->cancelUrl);
        $this->paypalUrl = $this->credentials[$this->apiLive ? 'live' : 'sandbox']['paypalUrl'];
    }

    public function DoDirectPayment($paymentInfo = array())
    {
        /**
         * Get required parameters from the web form for the request
         */
        $nvpRequest = array(
            'PAYMENTACTION' => 'Sale',
            'IPADDRESS' => $_SERVER['REMOTE_ADDR'],
            'AMT' => $paymentInfo['Order']['theTotal'],
            'CREDITCARDTYPE' => $paymentInfo['CreditCard']['credit_type'],
            'ACCT' => $paymentInfo['CreditCard']['card_number'],
            'EXPDATE' => str_pad($paymentInfo['CreditCard']['expiration_month'], 2, '0', STR_PAD_LEFT) . $paymentInfo['CreditCard']['expiration_year'],
            'CVV2' => $paymentInfo['CreditCard']['cv_code'],
            'FIRSTNAME' => $paymentInfo['Member']['first_name'],
            'LASTNAME' => $paymentInfo['Member']['last_name'],
            'STREET' => $paymentInfo['Member']['billing_address'],
            'STREET2' => $paymentInfo['Member']['billing_address2'],
            'CITY' => $paymentInfo['Member']['billing_city'],
            'STATE' => $paymentInfo['Member']['billing_state'],
            'ZIP' => $paymentInfo['Member']['billing_city'],
            'COUNTRYCODE' => $paymentInfo['Member']['billing_country'],
            'CURRENCYCODE' => $this->currency
        );
        /**
         *  Make the API call to PayPal, using API signature.
         *  The API response is stored in an associative array called $resArray
         *  Contains 'TRANSACTIONID,AMT,AVSCODE,CVV2MATCH, or Error Codes'
         */
        return $this->hash_call("doDirectPayment", $nvpRequest);
    }

    public function SetExpressCheckout($paymentInfo = array())
    {
        $nvpRequest = array(
            'AMT' => $paymentInfo['Order']['theTotal'],
            'PAYMENTACTION' => 'Sale',
            'CURRENCYCODE' => $this->currency,
            'RETURNURL' => $this->returnUrl,
            'CANCELURL' => $this->cancelUrl,
            'EMAIL' => $paymentInfo['Email'],
            'NOSHIPPING' => 1, // PayPal does not display shipping address fields whatsoever.
            'DESC' => $paymentInfo['Order']['description'] ?: $this->defaultDescription,
            'QTY' => $paymentInfo['Order']['quantity'] ?: $this->defaultQuantity
        );
        return $this->hash_call("SetExpressCheckout", $nvpRequest);
    }

    public function GetExpressCheckoutDetails($token)
    {
        return $this->hash_call("GetExpressCheckoutDetails", array('TOKEN' => $token));
    }

    public function DoExpressCheckoutPayment($paymentInfo = array())
    {
        $nvpRequest = array(
            'TOKEN' => $paymentInfo['TOKEN'],
            'PAYERID' => $paymentInfo['PAYERID'],
            'PAYMENTACTION' => 'Sale',
            'AMT' => $paymentInfo['ORDERTOTAL'],
            'CURRENCYCODE' => $this->currency,
            'IPADDRESS' => $_SERVER['SERVER_NAME']
        );
        return $this->hash_call("DoExpressCheckoutPayment", $nvpRequest);
    }

    public function APIError($errorNo, $errorMsg, $resArray)
    {
        $resArray['Error']['Number'] = $errorNo;
        $resArray['Error']['Number'] = $errorMsg;
        return $resArray;
    }

    public function isCallSucceeded($resArray)
    {
        $ack = strtoupper($resArray["ACK"]);
        return ($ack === "SUCCESS" || $ack === 'SUCCESSWITHWARNING'); //Detect Errors
    }

    public function hash_call($methodName, $nvpRequest)
    {
        $API_UserName = $this->credentials[$this->apiLive ? 'live' : 'sandbox']['username'];
        $API_Password = $this->credentials[$this->apiLive ? 'live' : 'sandbox']['password'];
        $API_Signature = $this->credentials[$this->apiLive ? 'live' : 'sandbox']['signature'];
        $API_Endpoint = $this->credentials[$this->apiLive ? 'live' : 'sandbox']['endPoint'];

        //setting the curl parameters.
        set_time_limit(30);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        if ($this->useProxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost . ":" . $this->proxyPort);
        }

        // NVPRequest for submitting to server
        $nvpBase = array(
            'METHOD' => $methodName,
            'VERSION' => $this->version,
            'PWD' => $API_Password,
            'USER' => $API_UserName,
            'SIGNATURE' => $API_Signature
        );

        //setting the merged $nvpBase + $nvpRequest array as POST FIELD to curl
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($nvpBase + $nvpRequest));

        //getting response from server
        $response = curl_exec($ch);

        //convert NVPResponse to an Associative Array
        $nvpResponseArray = array();
        parse_str($response, $nvpResponseArray);

        if (curl_errno($ch)) {
            $nvpResponseArray = $this->APIError(curl_errno($ch), curl_error($ch), $nvpResponseArray);
        } else {
            curl_close($ch);
        }

        return $nvpResponseArray;
    }

    /**
     * This function helps to refund the transaction by payerId and transactionId
     * TransactionId returned by {@see DoExpressCheckoutPayment}
     * @link https://developer.paypal.com/docs/classic/api/merchant/RefundTransaction_API_Operation_NVP/
     *
     * @param array $paymentInfo
     * @return array
     */
    public function RefundTransaction($paymentInfo = array())
    {
        $nvpRequest = array(
            'PAYERID' => $paymentInfo['PAYERID'],
            'TRANSACTIONID' => $paymentInfo['TRANSACTIONID'],
            'CURRENCYCODE' => $this->currency,
            'REFUNDTYPE' => 'Full'
        );
        return $this->hash_call("RefundTransaction", $nvpRequest);
    }

}