<?php

namespace Ajaxy\Payment\Gateway\Paypal;

class IPN extends \ArrayObject{

    const LIVE = 'https://ipnpb.paypal.com/cgi-bin/webscr';
    const SANDBOX = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

    private $sandbox = false;
    private $logger = false;

    public function __construct($post = 'php://input', $sandbox = false, \Psr\Log\LoggerInterface $logger = null){
        if(is_array($post)){
            $_post = $post;
        }else{
            $_post = (string)$post;
            $raw_post_data = file_get_contents($_post);
            $raw_post_array = explode('&', $raw_post_data);
            $_post = array();
            foreach ($raw_post_array as $keyval) {
                $keyval = explode ('=', $keyval);
                if (count($keyval) == 2) $_post[$keyval[0]] = urldecode($keyval[1]);
            }
        }
        $this->logger = $logger;
        $this->sandbox = $sandbox;
        parent::__construct((array)$_post);
    }

    /**
     * Returns the seller email
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return string
     */
    public function getSellerEmail(){
        return $this->offsetGet('business');
    }

    /**
     * Returns the buyer email
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return string
     */
    public function getBuyerEmail(){
        return $this->offsetGet('payer_email');
    }

    /**
     * Returns the purchase total
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return float
     */
    public function getPaymentDate(){
        return new \DateTime($this->offsetGet('payment_date'));
    }

    /**
     * Check if the payment is completed
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return boolean
     */
    public function isCompleted(){
        return $this->offsetGet('payment_status') == 'Completed';
    }

    /**
     * Check if the payment is denied
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return boolean
     */
    public function isDenied(){
        return $this->offsetGet('payment_status') == 'Denied';
    }

    /**
     * Check if the payment has failed
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return boolean
     */
    public function hasFailed(){
        return $this->offsetGet('payment_status') == 'Failed';
    }

    /**
     * Check if the payment has failed
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return boolean
     */
    public function isProcessed(){
        return $this->offsetGet('payment_status') == 'Processed';
    }

    /**
     * Check if the payment is pending, in such case getPaymentType should not return instant
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return boolean
     */
    public function isPending(){
        return $this->offsetGet('payment_status') == 'Pending';
    }

    /**
     * return the payment status
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return string
     */
    public function getPaymentStatus(){
        return $this->offsetGet('payment_status');
    }

    /**
     * return the payment type
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return string
     */
    public function getPaymentType(){
        return $this->offsetGet('payment_type');
    }

    /**
     * This is only valid if the payment is pending
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return string
     */
    public function getPendingReason(){
        return !$this->offsetExists('pending_reason') ? :(string)$this->offsetGet('pending_reason');
    }

    /**
     * Returns the customer info
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return string
     */
    public function getCustomerInfo(){
        $info = array(
            'first_name' => $this->offsetGet('first_name'),
            'last_name' => $this->offsetGet('last_name'),
            'email' => $this->getBuyerEmail(),
            'address' => array(
                'name' => $this->offsetGet('address_name'),
                'street' => $this->offsetGet('address_street'),
                'city' => $this->offsetGet('address_city'),
                'state' => $this->offsetGet('address_state'),
                'zip' => $this->offsetGet('address_zip'),
                'country' => $this->offsetGet('address_country')
            )
        );
        return $info;
    }

    /**
     * Check whether this is a sandbox request or a live payment
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return boolean
     */
    public function isTest(){
        return (int)$this->offsetGet('test_ipn') == 1;
    }

    /**
     * Returns the purchase total
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return float
     */
    public function getTotal(){
        return (float)$this->offsetGet('mc_gross');
    }

    /**
     * Returns the purchase total Shipping
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return float
     */
    public function getTotalShipping(){
        return (float)$this->offsetGet('mc_shipping');
    }

    /**
     * Returns the purchase total Items
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return float
     */
    public function getTotalItems(){
        return (int)$this->offsetGet('num_cart_items');
    }

    /**
     * Returns the custom values passed along with the request
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return any
     */
    public function getCustom(){
        return $this->offsetExists('custom') ? json_decode(urldecode($this->offsetGet('custom')), true): null;
    }

    /**
     * Returns the currency
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return any
     */
    public function getCurrency(){
        return $this->offsetGet('mc_currency');
    }

    /**
     * Returns all ipn purchased items
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return array of items
     */
    public function getItems(){
        $mc = $this->getTotalItems();

        $mc_gross = [];
        $mc_items = [];
        for($key = 1; $key <= $mc; $key ++){
            if($this->offsetExists('mc_gross_'.$key)){
                $mc_gross[$key] = $this->offsetGet('mc_gross_'.$key);
            }
            $item = array('quantity' => 0, 'shipping' => 0, 'price' => 0, 'name' => '', 'number' => '');
            if($this->offsetExists('quantity'.$key)){
                $item['quantity'] = (float)$this->offsetGet('quantity'.$key);
            }
            if($this->offsetExists('mc_gross_'.$key)){
                $item['price'] = (float)$this->offsetGet('mc_gross_'.$key);
            }
            if($this->offsetExists('item_name'.$key)){
                $item['name'] = (string)$this->offsetGet('item_name'.$key);
            }
            if($this->offsetExists('item_number'.$key)){
                $item['number'] = (string)$this->offsetGet('item_number'.$key);
            }
            if($this->offsetExists('mc_shipping'.$key)){
                $item['shipping'] = (float)$this->offsetGet('mc_shipping'.$key);
            }
            $mc_items[] = $item;
        }
        return $mc_items;
    }

    /**
     * Returns the transaction Id
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return string
     */
    public function getTransactionId(){
        return $this->offsetGet('txn_id');
    }

    /**
     * Returns true or false if the IPN is a valid ipn
     *
     * you will need to validate the below after you receive a valid IPN notification.
     *
     * check whether the payment_status is Completed
     * check that txn_id has not been previously processed
     * check that receiver_email is your Primary PayPal email
     * check that payment_amount/payment_currency are correct
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @return boolean
     */
    public function validate(){
        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        $_post = $this->getArrayCopy();
        foreach ($_post as $key => $value) {
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
        }

        $ch = curl_init($this->sandbox ? self::SANDBOX: self::LIVE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        // In wamp-like environments that do not come bundled with root authority certificates,
        // please download 'cacert.pem' from "https://curl.haxx.se/docs/caextract.html" and set
        // the directory path of the certificate as shown below:
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
        if ( !($res = curl_exec($ch)) ) {
            $this->log('error', curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        // inspect IPN validation result and act accordingly
        if (strcmp ($res, "VERIFIED") == 0) {
            $this->log('info', (array)$this);
            return true;
        } else if (strcmp ($res, "INVALID") == 0) {
            $this->log('error', $res);
        }
        return false;
    }

    /**
     * Log the debug
     *
     * @since  1.0.0
     * @date   2018-02-19
     * @param  string     $type
     * @param  array     $context
     * @return null
     */
    public function log($type, $context){
        if($this->logger){
            $var = 'payment'.ucfirst($type);
            if(is_callable(array($this->logger, $var))){
                $this->logger->{$var}('IPN', (array)$context);
            }else{
                $this->logger->debug('IPN', (array)$context);
            }
        }
    }
}

?>
