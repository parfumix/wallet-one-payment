walletOnePayment
================

## Wallet One Payment

``` 
    try {
        $walletInstance = \Walletone\Walletone::getInstance();
        
        $walletInstance->setAmount('AMOUNT')
                        ->setCurrency('CURRENCY_ID')
                          ->setLocale('LOCALE')
                            ->setDescription('DESCRIPTION')
                              ->setPaymentNo('PAYMENT_NUMBER');
        
        $walletInstance->request();
    
    } catch (\Walletone\Exception $e) { }
```
