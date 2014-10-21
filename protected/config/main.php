<?php
return array(
//Place the Paypal configuration array inside your 'components' definitions
	// application components
	'components' => array(
            'PayPalExpress' => array(
                'class' => 'PayPalExpress',
                'apiLive' => true,
                'credentials' => array(
                    'live' => array(
                        'username' => 'john_api1.yoursite.com',
                        'password' => 'YOURLIVEPASSWORD',
                        'signature' => 'BiPC9BjkCyDWQXbSkoZcdqH3hpacAWUAWDllpGf-7eXpqUFawIOBVRoe',
                        'paypalUrl' => 'https://www.paypal.com/webscr&cmd=_express-checkout&useraction=commit&token=',
                        'endPoint' => 'https://api-3t.paypal.com/nvp'
                    ),
                    'sandbox' => array(
                        'username' => 'john_api1.yoursite.com',
                        'password' => 'YOURSANDBOXPASSWORD',
                        'signature' => 'AFcWxV21C7AWDFg34l31AbL82231J630xSfUIuawddaGHHIASad7689r',
                        'paypalUrl' => 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&useraction=commit&token=',
                        'endPoint' => 'https://api-3t.sandbox.paypal.com/nvp'
                    )
                ),
                'currency' => 'GBP',
                'returnUrl' => 'book/paypal-confirm/', //regardless of url management component
                'cancelUrl' => 'book/paypal-error/', //regardless of url management component
            )
	)
);