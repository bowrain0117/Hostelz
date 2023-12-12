<?php

namespace App\Models;

use Lib\BaseModel;

/*

Fields:

- status - 'active', 'deleted' (card deleted from our system and Stripe/etc.), 'deactivated' (after a payment failed)
- name - Optional.
- type - PaymentProcessor type.
- data - Used by the PaymentProcessor.

*/

class PaymentMethod extends BaseModel
{
    protected $table = 'payment_methods';

    public static $staticTable = 'payment_methods'; // just here so we can get the table name without needing an instance of the object

    // We aren't using timestamps for this model
    public $timestamps = false;

    protected $guarded = [];

    private $paymentProcessor;

    /* Misc */

    public function deactivate(): void
    {
        $this->status = 'deactivated';
        $this->save();
    }

    public function reactivate(): void
    {
        $this->status = 'active';
        $this->save();
    }

    public function delete()
    {
        if (! $this->getPaymentProcessor()->delete()) {
            // (we could do something here, but might as well just delete it either way. the error was already reported.)
        }

        $this->status = 'deleted';
        $this->save();

        return true;
    }

    /**
     * Returns a display-able name of this object.
     * Used when displaying log events, etc.
     */
    public function getDisplayName($longFormatName = false)
    {
        return $this->name != '' ? $this->name : $this->getPaymentProcessor()->getDisplayName();
    }

    public function getDisplayDetails()
    {
        return $this->getPaymentProcessor()->getDisplayDetails();
    }

    public function getPaymentProcessor()
    {
        if (! $this->paymentProcessor) {
            $getPaymentMethodData = function ($item) {
                return $this->data[$item] ?? null;
            };
            $setPaymentMethodData = function ($item, $value, $saveNow = true): void {
                $data = $this->data;
                $data[$item] = $value;
                $this->data = $data;
                if ($saveNow) {
                    $this->save();
                }
            };

            $className = '\\App\\PaymentProcessors\\' . $this->type . '\\PaymentProcessor';
            $this->paymentProcessor = new $className($getPaymentMethodData, $setPaymentMethodData);
        }

        return $this->paymentProcessor;
    }

    public function charge($amount, $description = '', $currency = 'USD')
    {
        return $this->getPaymentProcessor()->charge($amount, $description, $currency);
    }

    /* Accessors & Mutators */

    public function getDataAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setDataAttribute($value): void
    {
        $this->attributes['data'] = ($value ? json_encode($value) : '');
    }

    /* Static */

    // This creates a new payment method (a saved credit card) from the info entered by the user.

    public static function createNewFromInput($paymentProcessorType, User $user, $requestInput)
    {
        $paymentMethod = new self(['status' => 'active', 'user_id' => $user->id, 'type' => $paymentProcessorType]);
        $paymentMethod->data = [];

        $paymentProcessor = $paymentMethod->getPaymentProcessor();

        $customerInfo = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->username,
            // (add more here like address, etc. if needed by the payment processor)
        ];

        // (This will also save the $paymentMethod record if it's successful.)
        $errorMessage = $paymentProcessor->setPaymentInfoFromInput($customerInfo, $requestInput);

        if ($errorMessage !== null) {
            logWarning('warning', "Couldn't add credit card.  Error: $errorMessage");

            return $errorMessage;
        }

        return $paymentMethod;
    }

    public static function sortPaymentMethods($paymentMethods)
    {
        return $paymentMethods->sort(function ($a, $b) {
            if ($a->status != $b->status) {
                if ($a->status == 'active') {
                    return -1;
                }
                if ($b->status == 'active') {
                    return 1;
                }
            }
            if ($a->precedence != $b->precedence) {
                return $a->precedence <=> $b->precedence;
            }

            // Sort the rest based on id so older ones are first
            return $a->id <=> $b->id;
        });
    }

    public static function maintenanceTasks($timePeriod): void
    {
        /*
        $output = '';

        switch($timePeriod) {
            case 'daily':
                $output .= "\nReset viewsToday.\n";
            	self::where('viewsToday', '>', 0)->update([ 'viewsToday' => 0 ]);
            	break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

    	return $output;
    	*/
    }

    /* Scopes */

    /* Relationships */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
