<?php
class Uaudio_CurrencyLayer_Model_Currency_Import_Currencylayer extends Mage_Directory_Model_Currency_Import_Abstract {

    protected $_url = 'http://www.apilayer.net/api/live?access_key={{ACCESS_KEY}}&format=1&currencies={{CURRENCY_TO}}';
    protected $_messages = array();

     /**
     * HTTP client
     *
     * @var Varien_Http_Client
     */
    protected $_httpClient;

    public function __construct() {
        $this->_httpClient = new Varien_Http_Client();
    }

    protected function _convert($currencyFrom, $currencyTo, $retry=0) {
        $url = str_replace(['{{ACCESS_KEY}}', '{{CURRENCY_TO}}'], [Mage::getStoreConfig('uaudio/currency_layer/access_key'), $currencyTo], $this->_url);

        try {
            $response = $this->_httpClient
                ->setUri($url)
                ->setConfig(['timeout' => Mage::getStoreConfig('currency/webservicex/timeout')])
                ->request('GET')
                ->getBody();

            $json = json_decode($response);

            if(!$json || $json->success != true) {
                $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $this->_url);
                return null;
            }
            if(!isset($json->quotes->{'USD'.$currencyTo})) {
                $this->_messages[] = Mage::helper('directory')->__('Cannot convert USD to %s currency.', $currencyTo);
                return null;
            }
            return (float) $json->quotes->{'USD'.$currencyTo};
        } catch (Exception $e) {
            if( $retry == 0 ) {
                $this->_convert($currencyFrom, $currencyTo, 1);
            } else {
                $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $this->_url);
            }
        }
    }
}
