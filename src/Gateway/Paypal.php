<?php

namespace Ajaxy\Payment\Gateway;

class Paypal extends \Ajaxy\Payment\Gateway{
    private $payer = null;
    private $items = array();
    private $apiContext = null;
    private $paid_url = null;
    private $cancel_url = null;
    private $currency = 'USD';


    public $customer_first_name;
    public $customer_last_name;
    public $customer_address1;
    public $customer_address2;
    public $customer_city;
    public $customer_state;
    public $customer_zip;
    public $customer_country;
    public $customer_email;

    /**
     * get paypal default settings
     *
     * @since  1.0.0
     * @date   2018-02-13
     * @return array
     */
    public function getDefaultSettings(){
        $defaults = array(
            'email' => '',
            'notify_url' => '',
            'return_url' => '',
            'cancel_url' => '',
            'currency_code' => 'USD',
            'lang' => '',
            'custom' => '',
            'logo_img' => '',
            'mode' => 'sandbox',
            'paymentaction' => 'sale',
            'invoice_prefix' => '',
        );
        return $defaults;
    }

    /**
     * get request uri
     *
     * @since  1.0.0
     * @date   2018-02-13
     * @param  boolean    $sandbox
     * @return string
     */
    public function getRequestUrl( $sandbox = false ) {
		$_args = http_build_query( array_filter( $this->getArgs() ), '', '&' );
		if ( $sandbox ) {
			return 'https://www.sandbox.paypal.com/cgi-bin/webscr?test_ipn=1';
		} else {
			return 'https://www.paypal.com/cgi-bin/webscr';
		}
	}

    public function getArgs( ) {
        $return_url = $this->getSetting( 'return_url' );
        if(empty($return_url)){
            $return_url = $this->getSetting('notify_url');
        }
		return array(
			'cmd'           => '_cart',
			'business'      => $this->getSetting( 'email' ),
			'no_note'       => 1,
			'currency_code' => $this->currency,
			'charset'       => 'utf-8',
			'rm'            => $this->isSSL() ? 2 : 1,
			'upload'        => 1,
			'return'        => $this->addArgs( $return_url, array( 'utm_nooverride' => '1' ) ),
			'cancel_return' => $this->getSetting( 'cancel_url' ),
			'page_style'    => $this->getSetting( 'page_style' ),
			'image_url'     => $this->getSetting( 'logo_img' ) ,
			'paymentaction' => $this->getSetting( 'paymentaction' ),
			'bn'            => 'Ajaxy_Payment',
			'invoice'       => $this->prepare( $this->getSetting( 'invoice_prefix' ) . $this->getOrderNumber(), 127 ),
			'custom'        => $this->getSetting( 'custom' ) ? htmlspecialchars( json_encode($this->getSetting( 'custom' )) ): '',
            //customer info
			'notify_url'    => $this->prepare( $this->getSetting('notify_url'), 255 ),
			'first_name'    => $this->prepare( $this->customer_first_name, 32 ),
			'last_name'     => $this->prepare( $this->customer_last_name, 64 ),
			'address1'      => $this->prepare( $this->customer_address1, 100 ),
			'address2'      => $this->prepare( $this->customer_address2, 100 ),
			'city'          => $this->prepare( $this->customer_city, 40 ),
			'state'         => $this->prepare( $this->customer_state, 40 ),
			'zip'           => $this->prepare( $this->customer_zip, 32 ),
			'country'       => $this->prepare( $this->customer_country, 2 ),
			'email'         => $this->prepare( $this->customer_email )
		);
	}

    public function addItem($id, $price, $quantity, $shipping = 0, $name = ''){
        $this->items[] = array(
			'item_name'   => html_entity_decode( trim($name) ? trim($name) : 'Item', ENT_NOQUOTES, 'UTF-8' ),
			'quantity'    => (int) $quantity,
			'amount'      => (float) $price,
			'shipping'    => $shipping,
			'item_number' => $id,
		);
        return $this;
    }

    public function getItems(){
        return $this->items;
    }

    /**
	 * Limit length of an arg.
	 *
	 * @param  string  $string
	 * @param  integer $limit
	 * @return string
	 */
	protected function prepare( $string, $limit = 127 ) {
		if ( strlen( $string ) > $limit ) {
			$string = substr( $string, 0, $limit - 3 ) . '...';
		}
		return $string;
	}


    public function getForm($sandbox = false){
        ?>
        <form action="<?php echo $this->getRequestUrl($sandbox); ?>" method="post">
            <?php foreach($this->getArgs() as $key => $value):
                if(trim($value) == '') continue;
                ?>
                <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>">
            <?php endforeach; ?>
            <?php foreach($this->getItems() as $index => $item): ?>
                <?php foreach($item as $key => $value):
                    if(trim($value) == '') continue;
                    ?>
                    <input type="hidden" name="<?php echo $key; ?>_<?php echo $index+1; ?>" value="<?php echo $value; ?>">
                <?php endforeach; ?>
            <?php endforeach; ?>
            <input type="submit" value="PayPal">
        </form>
        <?php
    }

    /**
     * Set the value of Customer First Name
     *
     * @param mixed customer_first_name
     *
     * @return self
     */
    public function setCustomerFirstName($customer_first_name)
    {
        $this->customer_first_name = $customer_first_name;

        return $this;
    }

    /**
     * Set the value of Customer Last Name
     *
     * @param mixed customer_last_name
     *
     * @return self
     */
    public function setCustomerLastName($customer_last_name)
    {
        $this->customer_last_name = $customer_last_name;

        return $this;
    }

    /**
     * Set the value of Customer Address
     *
     * @param mixed customer_address1
     *
     * @return self
     */
    public function setCustomerAddress1($customer_address1)
    {
        $this->customer_address1 = $customer_address1;

        return $this;
    }

    /**
     * Set the value of Customer Address
     *
     * @param mixed customer_address2
     *
     * @return self
     */
    public function setCustomerAddress2($customer_address2)
    {
        $this->customer_address2 = $customer_address2;

        return $this;
    }

    /**
     * Set the value of Customer City
     *
     * @param mixed customer_city
     *
     * @return self
     */
    public function setCustomerCity($customer_city)
    {
        $this->customer_city = $customer_city;

        return $this;
    }

    /**
     * Set the value of Customer State
     *
     * @param mixed customer_state
     *
     * @return self
     */
    public function setCustomerState($customer_state)
    {
        $this->customer_state = $customer_state;

        return $this;
    }

    /**
     * Set the value of Customer Zip
     *
     * @param mixed customer_zip
     *
     * @return self
     */
    public function setCustomerZip($customer_zip)
    {
        $this->customer_zip = $customer_zip;

        return $this;
    }

    /**
     * Set the value of Customer Country
     *
     * @param mixed customer_country
     *
     * @return self
     */
    public function setCustomerCountry($customer_country)
    {
        $this->customer_country = $customer_country;

        return $this;
    }

    /**
     * Set the value of Customer Email
     *
     * @param mixed customer_email
     *
     * @return self
     */
    public function setCustomerEmail($customer_email)
    {
        $this->customer_email = $customer_email;

        return $this;
    }

}

?>
