<?php

namespace App\PaymentProcessors\Stripe;

use App\PaymentProcessors\PaymentProcessorBaseClass;
use Carbon\Carbon;
use Exception;

/*

This can be tested with 4242424242424242 as the card number.
Or 4000000000000002 for a declined card (can't add it at all).
Or 4000000000000341 for a card that you can add to the customer, but fails charges.


paymentMethodData format (data stored in the PaymentMethod object):
    - customerToken
    - 'cardID', 'brand', 'last4', 'expiration'

*/

class PaymentProcessor extends PaymentProcessorBaseClass
{
    private $client;

    // Customer cache
    private $cachedCustomer;

    private $cachedCustomerToken;

    public const STRIPE_API_VERSION = '2018-01-23'; // See https://stripe.com/docs/upgrades

    public function __construct($getPaymentMethodData, $setPaymentMethodData)
    {
        \Stripe\Stripe::setApiKey(config('paymentProcessors.stripe.secret'));
        \Stripe\Stripe::setApiVersion(self::STRIPE_API_VERSION);

        parent::__construct($getPaymentMethodData, $setPaymentMethodData);
    }

    public function getDisplayName()
    {
        return '************' . $this->getPaymentMethodData('last4');
    }

    public function getDisplayDetails()
    {
        return $this->getPaymentMethodData('brand') .
            ' | Expiration: ' . $this->getPaymentMethodData('expiration');
    }

    public function refreshDataFromProvider()
    {
        $customer = $this->getCustomer();
        if (! $customer) {
            return false;
        }

        // We currently only use the first card (we just have one card per Stripe "customer").
        $cardData = reset($customer->sources->data);
        if (! $cardData) {
            return false;
        } // probably no cards on file

        $cardDataAsArray = getArrayFromObjectProperties($cardData, [
            'cardID' => 'id',
            'brand' => 'brand',
            'last4' => 'last4',
        ]);
        $cardDataAsArray['expiration'] = Carbon::create(
            $cardData->exp_year,
            $cardData->exp_month,
            isset($cardData->exp_day) ? $cardData->exp_day : 1
        )->format('Y-m-d');

        $itemNumber = 0;
        foreach ($cardDataAsArray as $key => $value) {
            $isLastItem = ++$itemNumber == count($cardDataAsArray);
            $this->setPaymentMethodData($key, $value, $isLastItem);
        }

        return true;
    }

    /**
     * Returns null if success, otherwise an error message.
     */
    public function setPaymentInfoFromInput($customerInfo, $requestInput)
    {
        if (empty($requestInput['stripeNewCardToken'])) {
            return 'Card information not received.';
        }

        $customer = $this->getCustomer();

        try {
            if (! $customer) {
                // Data to store about the user in Stripe (just for our reference)
                $metaData = [
                    'user_id' => $customerInfo['user_id'],
                    'name' => $customerInfo['name'],
                ];

                // Create a new customer and also add the card
                $customer = \Stripe\Customer::create([
                    'email' => $customerInfo['email'],
                    'metadata' => $metaData,
                    'source' => $requestInput['stripeNewCardToken'],
                ]);

                if (! $customer || ! $customer->id) {
                    // failed to create the customer (shouldn't happen)
                    return "Couldn't create the payment account for this user.";
                }

                $this->setPaymentMethodData('customerToken', $customer->id, false);

                // Update our customer cache
                $this->cachedCustomer = $customer;
                $this->cachedCustomerToken = $customer->id;
            } else {
                // Delete existing card if any

                // Add a card to an existing customer
                $result = $customer->sources->create(['source' => $newCardToken]);
            }
        } catch (\Stripe\Error\Card $e) {
            // The card was declined
            $body = $e->getJsonBody();

            return $body['error']['message'];
        }

        // Update our info about the card
        $this->refreshDataFromProvider();

        return null; // success
    }

    public function delete()
    {
        $customer = $this->getCustomer();
        if (! $customer) {
            return false;
        }

        $cardID = $this->getPaymentMethodData('cardID');
        if (! $cardID) {
            logError('Missing cardID.');

            return false;
        }

        $response = null;

        try {
            $response = $customer->sources->retrieve($cardID)->delete();
        } catch (Exception $e) {
            // Errors are handled below.
        }

        if (! $response || ! $response->deleted) {
            logError("Couldn't remove the card from Stripe.");

            return false;
        }

        return true;
    }

    public function charge($amount, $description = '', $currency = 'USD')
    {
        if (! $amount) {
            throw new Exception('Empty charge amount.');
        }

        try {
            $result = \Stripe\Charge::create([
                'amount' => $amount * 100,
                'currency' => $currency,
                'description' => $description,
                'customer' => $this->getPaymentMethodData('customerToken'),
            ], [
                // 'idempotency_key' => "...",
            ]);
        } catch (\Stripe\Error\Card $e) {
            // The card was declined
            $body = $e->getJsonBody();

            return [
                'succeeded' => false,
                'cardDeclined' => true, // We may do specific things when the card was declined.
                'errorMessage' => $body['error']['message'],
            ];
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid customer token, etc.
            $body = $e->getJsonBody();
            logError('error', $body['error']['message']);

            return [
                'succeeded' => false,
                'errorMessage' => $body['error']['message'],
            ];
        }
        // catch (\Stripe\Error\RateLimit $e)
        // catch (\Stripe\Error\ApiConnection $e)
        // catch (\Stripe\Error\Base $e)

        if ($result && $result->status == 'succeeded') {
            return [
                'succeeded' => true,
                'transactionID' => $result->id,
            ];
        }

        logError('error', 'Unknown charge error.', $result);

        return [
            'succeeded' => false,
            'errorMessage' => 'Unknown charge error.',
        ];
    }

    /**
     * This gets the Stripe "customer" record.
     */
    private function getCustomer()
    {
        $customerToken = $this->getPaymentMethodData('customerToken');
        if ($customerToken == '') {
            return null;
        }

        if ($this->cachedCustomerToken == $customerToken) {
            return $this->cachedCustomer; // cached
        }

        $this->cachedCustomerToken = $customerToken;

        try {
            return $this->cachedCustomer = \Stripe\Customer::retrieve($customerToken);
        } catch (Exception $e) {
            logError($e->getMessage());

            return null;
        }
    }
}
