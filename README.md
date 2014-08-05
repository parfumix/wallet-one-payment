walletOnePayment
================

## Requirements

-   PHP 5 >= 5.3.0

## Example

```php
try {
    $walletInstance = \Walletone\Walletone::getInstance();
    
    $walletInstance->setAmount('AMOUNT') # set amount 
                    ->setCurrency('CURRENCY_ID') # currency id. 
                      ->setLocale('LOCALE') # locale Ex: ru_RU | en_EN
                        ->setDescription('DESCRIPTION') # description to store in logs 
                          ->setPaymentNo('PAYMENT_NUMBER'); # local payment id 
    
    $walletInstance->request(); # redirecting user to payment page ...

} catch (\Walletone\Exception $e) { }
```


### Process Payment

Need to create an url to get an $_POST request from walletOne server with transatction status 
    
```php
    $walletInstance = \Walletone\Walletone::getInstance();

    if (! isset($_POST["WMI_SIGNATURE"]))
        $walletInstance->printAnswer("Retry", 'empty wmi signature');

    if (! isset($_POST["WMI_PAYMENT_NO"]))
        $walletInstance->printAnswer("Retry", 'empty WMI_PAYMENT_NO');

    if (! isset($_POST["WMI_ORDER_STATE"]))
        $walletInstance->printAnswer("Retry", 'empty WMI_ORDER_STATE');

    foreach ($_POST as $name => $value) {
        if ($name !== "WMI_SIGNATURE") $params[$name] = $value;
    }

    uksort($params, "strcasecmp");
    $values = "";

    foreach ($params as $value) {
        $value = iconv("utf-8", "windows-1251", $value);
        $values .= $value;
    }

    $signature = base64_encode(pack("H*", md5($values . $walletInstance->getMerchantKey())));

    if ($signature == $_POST["WMI_SIGNATURE"]) {
        if (strtoupper($_POST["WMI_ORDER_STATE"]) == "ACCEPTED") {
            $walletInstance->printAnswer("Ok", "Order #" . $_POST["WMI_PAYMENT_NO"] . " paid!");
        } else {
            $walletInstance->printAnswer("Retry", "wrong status " . $_POST["WMI_ORDER_STATE"]);
        }
    } else {
        $walletInstance->printAnswer("Retry", "invalid signature " . $_POST["WMI_SIGNATURE"]);
    }
```
