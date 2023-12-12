<?php

namespace App\PaymentProcessors;

class PaymentProcessorBaseClass
{
    // Used for accessing the storage/retrieval of the data related to this credit card, etc.
    public $callbackToGetPaymentMethodData;

    public $callbackToSetPaymentMethodData;

    public function __construct($callbackToGetPaymentMethodData, $callbackToSetPaymentMethodData)
    {
        $this->callbackToGetPaymentMethodData = $callbackToGetPaymentMethodData;
        $this->callbackToSetPaymentMethodData = $callbackToSetPaymentMethodData;
    }

    public function getPaymentMethodData($item)
    {
        $function = $this->callbackToGetPaymentMethodData;

        return $function($item);
    }

    public function setPaymentMethodData($item, $value, $saveNow = true): void
    {
        $function = $this->callbackToSetPaymentMethodData;
        $function($item, $value, $saveNow);
    }
}
