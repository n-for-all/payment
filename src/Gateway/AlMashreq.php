<?php

namespace Ajaxy\Payment\Gateway;

class AlMashreq extends \Ajaxy\Payment\Gateway{
    private $payer = null;
    private $items = array();
    private $currency = 'USD';

    /**
     * get default settings
     *
     * @since  1.0.0
     * @date   2018-02-13
     * @return array
     */
    public function getDefaultSettings(){
        $defaults = array(
            'endpoint' => '',
            'notify_url' => '',
            'return_url' => '',
            'currency_code' => 'AED',
            'custom' => '',
            'mode' => 'sandbox',
            'access_code' => '',
            'merchant' => '',
            'secure_hash' => '',
            'order_number' => 0,
            'total' => 0,
        );
        return $defaults;
    }

    /**
     * Limit length of an arg.
     *
     * @param  string  $string
     * @param  integer $limit
     * @return string
     */
    protected function limit_length($string, $limit = 127)
    {
        if (strlen($string) > $limit) {
            $string = substr($string, 0, $limit - 3) . '...';
        }
        return $string;
    }

    /**
     * get request uri
     *
     * @since  1.0.0
     * @date   2018-02-13
     * @param  boolean    $sandbox
     * @return string
     */
    public function getRequestUrl( $sandbox = false, $args = true ) {
        $_args = '';
        if($args){
    		$_args = '?'.http_build_query( array_filter( $this->getArgs() ), '', '&' );
        }
        if ($sandbox) {
            return rtrim($this->getSetting('endpoint'), '/').'/vpcpay' . $_args;
        } else {
            return rtrim($this->getSetting('endpoint'), '/').'/vpcpay' . $_args;
        }
	}

    public function getArgs( ) {
        $return_url = $this->getSetting( 'return_url' );
        if(empty($return_url)){
            $return_url = $this->getSetting('notify_url');
        }
        $args = array(
                'vpc_Version'           => '1',
                'vpc_Locale'            => 'en',
                'vpc_Command'           => 'pay',
                'vpc_AccessCode'        => $this->getSetting('access_code'),
                'vpc_MerchTxnRef'       => $this->limit_length($this->getSetting('order_number'), 127),
                'vpc_Merchant'          => $this->getSetting('merchant'),
                'vpc_OrderInfo'         => $this->getSetting( 'custom' ) ? json_encode($this->getSetting( 'custom' )): '',
                'vpc_Amount'            => intval(100*(float)$this->getSetting('total')),
                'vpc_Currency'          => $this->getSetting('currency_code'),
                'vpc_ReturnURL'         => $return_url,
                'vpc_SecureHash'        => '',
                'vpc_SecureHashType'    => 'SHA256'
        );
        ksort($args);
        $hash = null;
        foreach ($args as $k => $v) {
            // Skip vpc_ keys that are not included in the hash calculation
            if (in_array($k, array('vpc_SecureHash', 'vpc_SecureHashType'))) {
                continue;
            }
            if ((strlen($v) > 0) && ((substr($k, 0, 4)=="vpc_") || (substr($k, 0, 5) =="user_"))) {
                $hash .= $k . "=" . $v . "&";
            }
        }
        $hash = rtrim($hash, "&");
        $args['vpc_SecureHash'] = strtoupper(hash_hmac("SHA256", $hash, pack('H*', $this->getSetting('secure_hash'))));

        //$args['vpc_SecureHash'] = strtoupper(hash_hmac("SHA256", $hash, pack('H*', $this->gateway->get_option('secure_hash'))));
        $args['vpc_SecureHashType'] = "SHA256";
        return $args;
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
        <form action="<?php echo $this->getRequestUrl($sandbox, false); ?>" method="get">
            <?php foreach($this->getArgs() as $key => $value):
                if(trim($value) == '') continue;
                ?>
                <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value) ; ?>">
            <?php endforeach; ?>
            <input type="submit" value="Pay Now">
        </form>
        <?php
    }
}

?>
