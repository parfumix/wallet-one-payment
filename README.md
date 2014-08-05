walletOnePayment
================

## Example


```php
try {
    $walletInstance = \Walletone\Walletone::getInstance();
    
    $walletInstance->setAmount('AMOUNT')
                    ->setCurrency('CURRENCY_ID')
                      ->setLocale('LOCALE')
                        ->setDescription('DESCRIPTION')
                          ->setPaymentNo('PAYMENT_NUMBER');
    
    $walletInstance->request(); # redirecting user to payment page ...

} catch (\Walletone\Exception $e) { }
```
