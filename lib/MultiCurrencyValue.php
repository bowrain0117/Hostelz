<?php

namespace Lib;

use Exception;

class MultiCurrencyValue
{
    private $values = []; // (ex. 'USD' => 5.32, 'EUR' => 10.55)

    public function __construct($values = [])
    {
        $this->values = $values;
    }

    public function isValid($logWarnings = true, $canBeZero = false)
    {
        foreach ($this->values as $currency => $value) {
            if (! $canBeZero && floatval($value) == 0.0) {
                if ($logWarnings) {
                    logNotice("Value of 0 for '$currency'.");
                } // isn't necessarily a bad thing

                return false;
            }
            if (! Currencies::isKnownCurrencyCode($currency, false)) {
                if ($logWarnings) {
                    logWarning("Unknown currency code '$currency'.");
                }

                return false;
            }
        }

        return true;
    }

    public function getValue($currency = '', $formatted = false, $convertFromOtherCurrenciesAsNeeded = true, $saveConversions = true)
    {
        if ($currency == '') {
            // Currency defaults to the first currency in our values array.
            reset($this->values);
            $currency = key($this->values);
        }

        if (array_key_exists($currency, $this->values)) {
            $convertedValue = $this->values[$currency];
        } else {
            if (! $convertFromOtherCurrenciesAsNeeded) {
                throw new Exception("No value set for currency $currency. Maybe use convertFromOtherCurrenciesAsNeeded?");
            }

            $knownCurrencyValue = reset($this->values);
            if ($knownCurrencyValue === false) {
                throw new Exception("Can't get a value because no values have been set.");
            }
            $knownCurrency = key($this->values);

            $convertedValue = Currencies::convert($knownCurrencyValue, $knownCurrency, $currency);
            if ($saveConversions) {
                $values[$currency] = $convertedValue;
            }
        }

        return $formatted ? Currencies::format($convertedValue, $currency) : $convertedValue;
    }

    public function multiplyBy($multiple)
    {
        foreach ($this->values as $key => $value) {
            $this->values[$key] = $value * $multiple;
        }
    }
}
