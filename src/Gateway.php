<?php

namespace Ajaxy\Payment;

abstract class Gateway{

    private $order_number = null;

    public final function __construct($settings){
        $this->settings = array_merge($this->getDefaultSettings(), $settings);
    }

    abstract public function getDefaultSettings();
    abstract public function getForm($sandbox = false);

    protected function getSetting($name){
        if(isset($this->settings[$name])){
            return $this->settings[$name];
        }
        return '';
    }

    protected function isSSL() {
    	if ( isset($_SERVER['HTTPS']) ) {
    		if ( 'on' == strtolower($_SERVER['HTTPS']) )
    			return true;
    		if ( '1' == $_SERVER['HTTPS'] )
    			return true;
    	} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
    		return true;
    	}
    	if ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https' ) {
    		return true;
    	}
    	return false;
    }

    protected function addArgs($url_string, $args){
		// first we will remove the var (if it exists)
		// test if url has variables (contains "?")
		if(strpos($url_string,"?") !== false){
			$start_pos = strpos($url_string, "?");
			$url_vars = substr($url_string, $start_pos+1);
			$query = explode("&", $url_vars);

            $query_array = array();
			$url_string = substr($url_string, 0, $start_pos);
			foreach($query as $value){
				list($var_name, $var_value)= explode("=", $value);
                $query_array[$var_name] = $var_value;
			}
            $query_array = array_replace($query_array, $args);
		}else{
            $query_array = $args;
        }

        return $url_string.'?'.http_build_query($query_array, '', '&');
	}

    /**
     * Get the value of Order Number
     *
     * @return mixed
     */
    public function getOrderNumber()
    {
        return $this->order_number;
    }

    /**
     * Set the value of Order Number
     *
     * @param mixed order_number
     *
     * @return self
     */
    public function setOrderNumber($order_number)
    {
        $this->order_number = $order_number;

        return $this;
    }

}

?>
