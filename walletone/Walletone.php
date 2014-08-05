<?php
namespace walletone;

class Walletone {

    /**
     * @var
     */
    protected $amount;

    /**
     * @var
     */
    protected $paymentNo;

    /**
     * @var
     */
    protected $currency;

    /**
     * @var
     */
    protected $locale;

    /**
     * @var
     */
    protected $description;

    /**
     * @var
     */
    protected $options;

    /**
     * @var
     */
    protected $customParams = array();

    /**
     * @var null
     */
    protected static $_instance = null;

    /**
     *  List Available currency list ..
     *
     * @var array
     */
    protected $availableCurrency = array(
        'EUR' => 978,
        'USD' => 840,
        'RUB' => 643,
    );

    public $postParams        = array();

    const EVENT_SET_MERCHANT_OPTIONS = 'EVENT_SET_MERCHANT_OPTIONS';

    const MERCHANT_URL               = 'https://www.walletone.com/checkout/default.aspx';


    /**
     * @return Walletone|null
     */
    public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Set UP WMI Options                                                 *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     */

    public function getMerchantKey() {
        list($options) = \App_Tools::loadModels('options');

        if(! $merchantKEY = $options->getOption('WMI_KEY'))
            throw new Exception('invalid merchant key');

        return $merchantKEY;
    }

    /**
     *  Set required post fields ...
     *
     * @throws Exception
     */
    public function getPackPostArray() {
        list($options) = \App_Tools::loadModels('options');

        # Set Merchant ID
        if(! $merchantID = $options->getOption('WMI_MERCHANT_ID'))
            throw new Exception('invalid merchant id');
        else
            $this->postParams['WMI_MERCHANT_ID'] = $merchantID;

        # Set Amount
        if(! $this->getAmount())
            throw new Exception('invalid amount ');
        else
            $this->postParams['WMI_PAYMENT_AMOUNT'] = $this->getAmount();

        # Set currency
        if(! $this->getCurrency())
            throw new Exception('invalid currency');
        else
            $this->postParams['WMI_CURRENCY_ID'] = $this->getCurrency();

        # Set description
        if( $this->getDescription() )
            $this->postParams['WMI_DESCRIPTION'] = $this->getDescription();

        # Set payment NO
        if( $this->getPaymentNo() )
            $this->postParams['WMI_PAYMENT_NO'] = $this->getPaymentNo();


        # Set locale
        if( $this->getLocale() )
            $this->postParams['WMI_CULTURE_ID'] = $this->getLocale();

        # Set custom params ...
        if( $this->getCustomParams() ) {
            foreach( $this->getCustomParams() as $param => $value ) {

                if(! $this->postParams[$param])
                    $this->postParams[$param] = $value;
            }
        }

        # set success || fail url ...
        if(! $optionsApplication = $this->getOptions())
            throw new Exception('invalid options');
        else
            if(! $optionsApplication['success_url'] || !$optionsApplication['fail_url'])
                if(APPLICATION_ENV == 'development')
                    throw new Exception('Please check you configuration file');
                else
                    throw new Exception('invalid options');

            $this->postParams['WMI_SUCCESS_URL']    = 'http://'.$_SERVER['HTTP_HOST'] . $optionsApplication['success_url'];
            $this->postParams['WMI_FAIL_URL']       = 'http://'.$_SERVER['HTTP_HOST'] . $optionsApplication['fail_url'];


        $this->generateSignature();

        return $this->postParams;
    }

    /**
     *  Add key field to post params ...
     *
     * @return $this
     * @throws Exception
     */
    public function generateSignature() {
        if( ! $this->postParams)
            throw new Exception('Please pack first post params');

        $fields = $this->postParams;
        foreach($fields as $name => $val) {
            if (is_array($val)) {
                usort($val, "strcasecmp");
                $fields[$name] = $val;
            }
        }

        uksort($fields, "strcasecmp");
        $fieldValues = "";

        foreach($fields as $value) {
            if (is_array($value))  {
                foreach($value as $v){
                    $v = iconv("utf-8", "windows-1251", $v);
                    $fieldValues .= $v;
                }
            } else {
                $value = iconv("utf-8", "windows-1251", $value);
                $fieldValues .= $value;
            }
        }


        $signature = base64_encode(pack("H*", md5($fieldValues . $this->getMerchantKey())));

        $this->postParams["WMI_SIGNATURE"] = $signature;

        return $this;
    }


    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * $_POST Settings                                                     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     */

    public function setAmount($amount) {
        if(! is_numeric($amount))
            throw new Exception('you amount aren`t numeric');

        $this->amount = number_format($amount, 2);

        return $this;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function setPaymentNo($paymentNo) {
        if(! is_numeric($paymentNo))
            throw new Exception('you payment number aren`t numeric');

        $this->paymentNo = $paymentNo;

        return $this;
    }

    public function getPaymentNo() {
        return $this->paymentNo;
    }

    public function setCurrency($currency) {
        if(! in_array($currency, $this->availableCurrency))
            throw new \Exception('that currency do not supported');

        $this->currency = $currency;

        return $this;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function setLocale($locale) {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale() {
        return $this->locale;
    }

    public function getOptions() {
        if( null === $this->options ) {
            $options        = \Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOptions();
            $this->options  = $options['wmi'];
        }

        return $this->options;
    }

    public function setDescription($description) {
        $this->description = 'BASE64:' . base64_encode($description);

        return $this;
    }

    public function getDescription($encoded = true) {
        if( $encoded)
            return $this->description;

        $description = preg_replace("/BASE64:/", '', $this->description);
        return base64_decode($description);
    }

    public function setCustomParam($param, $value) {
        if(! $this->customParams[$param]) {
            $this->customParams[$param] = $value;

            return $this;
        } else {
            throw new Exception('params already set');
        }
    }

    public function getCustomParams() {
        return $this->customParams;
    }

    public function getAvailableCurrency() {
        return $this->availableCurrency;
    }

    public function getPostParams() {
        return $this->postParams;
    }


    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Redirect user to ...                                               *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     */

    public function request() {
        echo $this->getGeneratedHtmlForm(
            $this->getPackPostArray()
        );
    }

    public function getGeneratedHtmlForm($fields) {
        if(! $fields)
            throw new Exception('invalid fields');

        ob_start();
            print "<form action='".self::MERCHANT_URL."' id='payment-w1' accept-charset='UTF-8' method='post'>";

            foreach($fields as $key => $val) {
              if (is_array($val))
                  foreach($val as $value) {
                      print '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
                 }
              else
                  print '<input type="hidden" name="'.$key.'" value="'.$val.'"/>';
            }

            print '<input type="submit" style="display: none"></form>';
            print '<script type="text/javascript">document.getElementById("payment-w1").submit()</script>';

        $form = ob_get_contents();
        ob_end_clean();

        return $form;
    }


    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Processing Payment                                                 *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     */

    public function printAnswer($result, $description) {
        print "WMI_RESULT=" . strtoupper($result) . "&";
        print "WMI_DESCRIPTION=" .urlencode($description);
        exit();
    }

}