<?php

namespace Ajaxy\Payment\Gateway\AlMashreq;

class Response extends \ArrayObject{

    protected $secureHash = '';


    public function __construct($secureHash, $post = null, \Psr\Log\LoggerInterface $logger = null){
        if(!$post){
            $post = $_REQUEST;
        }
        $this->secureHash = $secureHash;
        if(!$this->secureHash){
            throw new \Exception('You must pass the secure hash to the response to validate it');
        }
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
        $this->log('POST', $_post);
        parent::__construct((array)$_post);
    }

    /**
     * @param  responseCode $responseCode
     */
    public function getResultDescription($responseCode)
    {
        switch ($responseCode) {
            case "0": $result = "Transaction Successful"; break;
            case "?": $result = "Transaction status is unknown"; break;
            case "E": $result = "Referred"; break;
            case "1": $result = "Transaction Declined"; break;
            case "2": $result = "Bank Declined Transaction"; break;
            case "3": $result = "No Reply from Bank"; break;
            case "4": $result = "Expired Card"; break;
            case "5": $result = "Insufficient funds"; break;
            case "6": $result = "Error Communicating with Bank"; break;
            case "7": $result = "Payment Server detected an error"; break;
            case "8": $result = "Transaction Type Not Supported"; break;
            case "9": $result = "Bank declined transaction (Do not contact Bank)"; break;
            case "A": $result = "Transaction Aborted"; break;
            case "B": $result = "Fraud Risk Blocked"; break;
            case "C": $result = "Transaction Cancelled"; break;
            case "D": $result = "Deferred transaction has been received and is awaiting processing"; break;
            case "E": $result = "Transaction Declined - Refer to card issuer"; break;
            case "F": $result = "3D Secure Authentication failed"; break;
            case "I": $result = "Card Security Code verification failed"; break;
            case "L": $result = "Shopping Transaction Locked (Please try the transaction again later)"; break;
            case "M": $result = "Transaction Submitted (No response from acquirer)"; break;
            case "N": $result = "Cardholder is not enrolled in Authentication scheme"; break;
            case "P": $result = "Transaction has been received by the Payment Adaptor and is being processed"; break;
            case "R": $result = "Transaction was not processed - Reached limit of retry attempts allowed"; break;
            case "S": $result = "Duplicate SessionID (Amex Only)"; break;
            case "T": $result = "Address Verification Failed"; break;
            case "U": $result = "Card Security Code Failed"; break;
            case "V": $result = "Address Verification and Card Security Code Failed"; break;
            default: $result = "Unable to be determined";
        }
        return $result;
    }

    /**
     * @param  responseCode $avsResultCode
     */

    public function getAvsResultDescription($avsResultCode)
    {
        if ($avsResultCode != "") {
            switch ($avsResultCode) {
                case "Unsupported": $result = "AVS not supported or there was no AVS data provided"; break;
                case "X": $result = "Exact match - address and 9 digit ZIP/postal code"; break;
                case "Y": $result = "Exact match - address and 5 digit ZIP/postal code"; break;
                case "W": $result = "9 digit ZIP/postal code matched, Address not Matched"; break;
                case "S": $result = "Service not supported or address not verified (international transaction)"; break;
                case "G": $result = "Issuer does not participate in AVS (international transaction)"; break;
                case "C": $result = "Street Address and Postal Code not verified for International Transaction due to incompatible formats."; break;
                case "I": $result = "Visa Only. Address information not verified for international transaction."; break;
                case "A": $result = "Address match only"; break;
                case "Z": $result = "5 digit ZIP/postal code matched, Address not Matched"; break;
                case "R": $result = "Issuer system is unavailable"; break;
                case "U": $result = "Address unavailable or not verified"; break;
                case "E": $result = "Address and ZIP/postal code not provided"; break;
                case "B": $result = "Street Address match for international transaction. Postal Code not verified due to incompatible formats."; break;
                case "N": $result = "Address and ZIP/postal code not matched"; break;
                case "0": $result = "AVS not requested"; break;
                case "D": $result = "Street Address and postal code match for international transaction."; break;
                case "M": $result = "Street Address and postal code match for international transaction."; break;
                case "P": $result = "Postal Codes match for international transaction but street address not verified due to incompatible formats."; break;
                case "K": $result = "Card holder name only matches."; break;
                case "F": $result = "Street address and postal code match. Applies to U.K. only."; break;
                default: $result = "Unable to be determined";
            }
        } else {
            $result = "null response";
        }
        return $result;
    }

    /**
     * Get the order Response Code
     * @return string
     */
    public function getResponseCode(){
        return $this->offsetGet('vpc_TxnResponseCode');
    }

    /**
     * Get the total of the order
     * @return Float
     */
    public function getTotal(){
        return $this->offsetExists('vpc_Amount') ? (float)$this->offsetGet('vpc_Amount') : false;
    }

    /**
     * Get the order info
     * @return object
     */
    public function getCustom(){
        return $this->offsetExists('vpc_OrderInfo') ? json_decode($this->offsetGet('vpc_OrderInfo')) : null;
    }

    /**
     * @param  responseCode $cscResultCode
     */
    public function getCscResultDescription($cscResultCode)
    {
        if ($cscResultCode != "") {
            switch ($cscResultCode) {
                case "Unsupported": $result = "CSC not supported or there was no CSC data provided"; break;
                case "M": $result = "Exact code match"; break;
                case "S": $result = "Merchant has indicated that CSC is not present on the card (MOTO situation)"; break;
                case "P": $result = "Code not processed"; break;
                case "U": $result = "Card issuer is not registered and/or certified"; break;
                case "N": $result = "Code invalid or not matched"; break;
                default: $result = "Unable to be determined"; break;
            }
        } else {
            $result = "null response";
        }
        return $result;
    }

    /**
     * Parses the digital receipt
     * @return array
     */
    protected function parseDigitalReceipt()
    {
        $dReceipt = array(
            "amount"            => $this->offsetExists('vpc_Amount') ? $this->null2unknown($this->offsetGet('vpc_Amount')) : false,
            "locale"              => $this->offsetExists('vpc_Locale') ? $this->null2unknown($this->offsetGet('vpc_Locale')) : false,
            "batchNo"             => $this->offsetExists('vpc_BatchNo') ? $this->null2unknown($this->offsetGet('vpc_BatchNo')) : false,
            "command"             => $this->offsetExists('vpc_Command') ? $this->null2unknown($this->offsetGet('vpc_Command')) : false,
            "message"             => $this->offsetExists('vpc_Message') ? $this->null2unknown($this->offsetGet('vpc_Message')) : false,
            "version"             => $this->offsetExists('vpc_Version') ? $this->null2unknown($this->offsetGet('vpc_Version')) : false,
            "cardType"            => $this->offsetExists('vpc_Card') ? $this->null2unknown($this->offsetGet('vpc_Card')) : false,
            "orderInfo"           => $this->offsetExists('vpc_OrderInfo') ? $this->null2unknown($this->offsetGet('vpc_OrderInfo')) : false,
            "receiptNo"           => $this->offsetExists('vpc_ReceiptNo') ? $this->null2unknown($this->offsetGet('vpc_ReceiptNo')) : false,
            "merchantID"          => $this->offsetExists('vpc_Merchant') ? $this->null2unknown($this->offsetGet('vpc_Merchant')) : false,
            "authorizeID"         => $this->offsetExists('vpc_AuthorizeId') ? $this->null2unknown($this->offsetGet('vpc_AuthorizeId')) : false,
            "merchTxnRef"         => $this->offsetExists('vpc_MerchTxnRef') ? $this->null2unknown($this->offsetGet('vpc_MerchTxnRef')) : false,
            "transactionNo"    => $this->offsetExists('vpc_TransactionNo') ? $this->null2unknown($this->offsetGet('vpc_TransactionNo')) : false,
            "acqResponseCode"    => $this->offsetExists('vpc_AcqResponseCode') ? $this->null2unknown($this->offsetGet('vpc_AcqResponseCode')) : false,
            "txnResponseCode"    => $this->offsetExists('vpc_TxnResponseCode') ? $this->null2unknown($this->offsetGet('vpc_TxnResponseCode')) : false
        );
        return $dReceipt;
    }


    /**
     * Parses the 3d secure data
     * @return array
     */
    protected function parse3dSecureData()
    {
        $threeDSecure = array(
            "verType"             => $this->offsetExists('vpc_VerType')          ? $this->offsetGet('vpc_VerType')          : false,
            "verStatus"           => $this->offsetExists('vpc_VerStatus')        ? $this->offsetGet('vpc_VerStatus')        : false,
            "token"               => $this->offsetExists('vpc_VerToken')         ? $this->offsetGet('vpc_VerToken')         : false,
            "verSecurLevel"       => $this->offsetExists('vpc_VerSecurityLevel') ? $this->offsetGet('vpc_VerSecurityLevel') : false,
            "enrolled"            => $this->offsetExists('vpc_3DSenrolled')      ? $this->offsetGet('vpc_3DSenrolled')      : false,
            "xid"                 => $this->offsetExists('vpc_3DSXID')           ? $this->offsetGet('vpc_3DSXID')           : false,
            "acqECI"              => $this->offsetExists('vpc_3DSECI')           ? $this->offsetGet('vpc_3DSECI')           : false,
            "authStatus"          => $this->offsetExists('vpc_3DSstatus')        ? $this->offsetGet('vpc_3DSstatus')        : false
        );
        return $threeDSecure;
    }

    protected function null2unknown($data)
    {
        if (trim($data) == "") {
            return false;
        } else {
            return $data;
        }
    }


    /**
     * There was a valid response.
     * @param  array $posted Post data after wp_unslash
     */
    public function validate()
    {
        $authorised = false;

        $md5Hash = $this->secureHash;

        if(!$this->offsetExists('vpc_SecureHash')){
            return false;
        }

        $txnSecureHash = $this->offsetGet('vpc_SecureHash');
        $custom = json_decode($this->offsetGet('vpc_OrderInfo'));

        $DR = $this->parseDigitalReceipt();
        $ThreeDSecureData = $this->parse3dSecureData();

        $msg = array();
        $msg['class']   = 'error';
        $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

        if (strlen($md5Hash) > 0 && $this->offsetGet('vpc_TxnResponseCode') != "7" && $this->offsetGet('vpc_TxnResponseCode') !== false) {
            foreach ($this as $key => $value) {
                if ($key != "vpc_SecureHash" && strlen($value) > 0) {
                    $md5Hash .= $value;
                }
            }

            if (strtoupper($txnSecureHash) != strtoupper(md5($md5Hash)) && $DR["txnResponseCode"] != "0") {
                $this->log('Al Mashreq Bank: Invalid Payment' .strtoupper($txnSecureHash)." ".strtoupper(md5($md5Hash)).":".$md5Hash, $this);
                $authorised = false;
            } else {
                if ($DR["txnResponseCode"] == "0") {
                    $authorised = true;
                } else {
                    $authorised = false;
                }
            }
        } else {
            $authorised = false;
        }

        if ($authorised) {
            return true;
        } else {
            $this->log('Al Mashreq Bank: Payment Failed', $this);
            return false;
        }
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
                $this->logger->{$var}('AlMashreq', (array)$context);
            }else{
                $this->logger->debug('AlMashreq', (array)$context);
            }
        }
    }
}

?>
