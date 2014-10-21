<?php

class PayPalController extends Controller
{

    public function actionBuy()
    {

        // set
        $paymentInfo = array(
            'Order' => array(
                'theTotal' => 0.00,
                'description' => 'Some payment description here',
                'quantity' => '1'
            )
        );

        $paypal = Yii::app()->PayPalExpress;

        try {

            // call paypal
            $result = $paypal->SetExpressCheckout($paymentInfo);

            if (!Yii::app()->Paypal->isCallSucceeded($result)) {
                $error = $paypal->apiLive ?
                            'We were unable to process your request. Please try again later' :
                            $result['L_LONGMESSAGE0'];
                throw new Exception($error);
            }

            // redirect user to paypal
            $this->redirect($paypal->paypalUrl . $result['TOKEN'], true);

        } catch (Exception $e) {

            echo $e->getMessage();
            Yii::app()->end();

        }

    }

    public function actionConfirm()
    {

        $token = trim(Yii::app()->request->getParam('token'));
        $payerId = trim(Yii::app()->request->getParam('PayerID'));

        $paypal = Yii::app()->PayPalExpress;

        try {

            // get settled transaction details from PayPal server
            $checkoutDetails = $paypal->GetExpressCheckoutDetails($token);

            if (!$paypal->isCallSucceeded($checkoutDetails)) { //Detect errors
                $error = $paypal->apiLive ?
                    'We were unable to process your request. Please try again later' :
                    $checkoutDetails['L_LONGMESSAGE0'];
                throw new Exception($error);
            }

            $paymentInfo = array(
                'TOKEN' => $token,
                'PAYERID' => $payerId,
                'ORDERTOTAL' => 0.00
            );

            // proceed PayPal payment transaction
            $paymentResult = Yii::app()->Paypal->DoExpressCheckoutPayment($paymentInfo);

            if (!$paypal->isCallSucceeded($paymentResult)) { //Detect errors
                $error = $paypal->apiLive ?
                    'We were unable to process your request. Please try again later' :
                    $checkoutDetails['L_LONGMESSAGE0'];
                throw new Exception($error);
            }

            $this->render('confirm');

        } catch (Exception $e) {

            echo $e->getMessage();
            Yii::app()->end();

        }
    }

    public function actionCancel()
    {
        //The token of the cancelled payment typically used to cancel the payment within your application
        $token = trim(Yii::app()->request->getParam('token'));
        $this->render('cancel');
    }

    public function actionDirectPayment()
    {
        $paymentInfo = array('Member' =>
            array(
                'first_name' => 'name_here',
                'last_name' => 'lastName_here',
                'billing_address' => 'address_here',
                'billing_address2' => 'address2_here',
                'billing_country' => 'country_here',
                'billing_city' => 'city_here',
                'billing_state' => 'state_here',
                'billing_zip' => 'zip_here'
            ),
            'CreditCard' =>
            array(
                'card_number' => 'number_here',
                'expiration_month' => 'month_here',
                'expiration_year' => 'year_here',
                'cv_code' => 'code_here'
            ),
            'Order' =>
            array('theTotal' => 1.00)
        );

        /*
         * On Success, $result contains [AMT] [CURRENCYCODE] [AVSCODE] [CVV2MATCH]
         * [TRANSACTIONID] [TIMESTAMP] [CORRELATIONID] [ACK] [VERSION] [BUILD]
         *
         * On Fail, $ result contains [AMT] [CURRENCYCODE] [TIMESTAMP] [CORRELATIONID]
         * [ACK] [VERSION] [BUILD] [L_ERRORCODE0] [L_SHORTMESSAGE0] [L_LONGMESSAGE0]
         * [L_SEVERITYCODE0]
         */

        $result = Yii::app()->Paypal->DoDirectPayment($paymentInfo);

        //Detect Errors
        if (!Yii::app()->Paypal->isCallSucceeded($result)) {
            if (Yii::app()->Paypal->apiLive === true) {
                //Live mode basic error message
                $error = 'We were unable to process your request. Please try again later';
            } else {
                //Sandbox output the actual error message to dive in.
                $error = $result['L_LONGMESSAGE0'];
            }
            echo $error;
        } else {
            //Payment was completed successfully, do the rest of your stuff
        }

        Yii::app()->end();
    }

}