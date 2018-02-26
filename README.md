# Ajaxy Payment

```php
//using paypal
__URL__ = 'http://www.example.com';
$paypal = new \Ajaxy\Payment\Gateway\Paypal(array(
    'email' => '',
    'custom' => array('user_id' => ''),
    'notify_url' => __URL__.'/validate_ipn.php',
    'return_url' => __URL__,
    'cancel_return' => __URL__
));

$paypal->addItem('test1', 10, 1);
$paypal->addItem('test2', 10, 3);
//print the form
$paypal->getForm(true);

//or echo the url
echo '<a href="'.$paypal->getRequestUrl(false).'">Pay</a>';

//using al mashreq bank
__URL__ = "http://www.example.com";
_SECURE_HASH_ = ''; //should be sent by the bank


$almashreq = new \Ajaxy\Payment\Gateway\AlMashreq(array(

    'notify_url' => __URL__.'/validate_mh.php',
    'return_url' => __URL__.'/validate_mh.php',

    'order_number' => __ORDER__NO__,

    //pass any order info to the payment, you will receive this back
    'custom' => array('test' => 1),

    //the order total, maximum of 2 decimal places
    'total' => '321',

    //sandbox or live
    'mode' => 'sandbox',

    //merchant details, those details are sent by the bank
    'endpoint' => 'https://migs.mastercard.com.au',
    'access_code' => '',
    'merchant' => '',
    'currency_code' => 'AED',
    'secure_hash' => _SECURE_HASH_,

));

//print the form
$almashreq->getForm(true);

//or echo the url
echo '<a href="'.$almashreq->getRequestUrl(false, true).'">Pay</a>';

```

## validation

```php

//al mashreq
$response = new \Ajaxy\Payment\Gateway\AlMashreq\Response(_SECURE_HASH_, null);
if($response->validate()){
    $custom = $response->getCustom();
    print_r($custom);
    echo 'payment is valid';
}else{
    $code = $response->getResponseCode();
    echo $response->getResultDescription($code);
}

//paypal
$ipn = new \Ajaxy\Payment\Gateway\Paypal\IPN('php://input', true);
if($ipn->validate()){
    echo "valid";
}else{
    echo "failed";
}

//you can also pass a psr4 logger to log the response

use \Ajaxy\Logger\Logger;
use \Ajaxy\Logger\Handler\Stream;

$log = new Logger();
$log->addHandler(new Stream(dirname(__FILE__).'/log/'));
$ipn = new \Ajaxy\Payment\Gateway\Paypal\IPN('php://input', true, $log);

$response = new \Ajaxy\Payment\Gateway\AlMashreq\Response(_SECURE_HASH_, null, $log);


```
