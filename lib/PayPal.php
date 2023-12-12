<?php

namespace Lib;

use Exception;

/*
    sandbox:
    personal: suppor_1258497692_per@hostelz.com/258497597
    business: suppor_1258588743_biz@hostelz.com/258665227
    another one: paypaltesting@hostelz.com / XvurT15L7p

    api reference:
    https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/howto_api_reference#wpp_ec_api_reference

    sample code:
    https://www.x.com/developers/paypal/documentation-tools/paypal-code-samples

    * Accepting Payments *
    2.9% plus $0.30 per transaction.

    - Express Checkout - https://www.x.com/developers/paypal/products/express-checkout - uses API calls. better, but more complicated?  can pay with cc.
        - recurring? free, but requires paypal account (no option to pay extra to allow that).  skip shipping address?
        - https://www.x.com/sites/default/files/pp_expresscheckout_integrationguide.pdf

    * PayPal Payments Standard - https://www.x.com/developers/paypal/products/paypal-payments-standard - free. "Buy Now" / "Add to Cart" / "Subscribe" buttons with simple html code. can accept CCs.
        - free recurring if account, or $20/month for add-on to accept recurring w/out paypal account. (https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=merchant/erp_overview)
        - https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_html_landing
        - https://cms.paypal.com/cms_content/US/en_US/files/developer/PP_WebsitePaymentsStandard_IntegrationGuide.pdf
        - instant payment notification (IPN) -

    - PayPal Payments Pro - customer enters CC info on your own website. $30/month. na.

    */

/*
$r = paypalCommand('GetTransactionDetails',array('TRANSACTIONID'=>'0PG02164WE416394D'));
print_r($r);

$r = paypalCommand('TransactionSearch',array('STARTDATE'=>'2009-01-01T00:00:00Z','ENDDATE'=>'2010-01-01T00:00:00Z'));
print_r($r);
*/

class PayPal
{
    public static $password = '';

    public static $sandboxTestMode = false;

    public static function command($methodName, $nvpArray)
    {
        if (self::$sandboxTestMode) {
            // sandbox testing mode
            $API_UserName = urlencode('suppor_1258588743_biz_api1.hostelz.com');
            $API_Password = urlencode('1258588748');
            $API_Signature = urlencode('Am-gY4FnYnZBASjRtLkB6OrYwLsCAF2L7IVmCyVucQJL6XFnd-OrQgpa');
            $API_Endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
        } else {
            if (self::$password == '') {
                throw new Exception('Password not set.');
            }
            $API_UserName = urlencode(config('custom.paypalUsername'));
            $API_Password = urlencode(self::$password);
            $API_Signature = urlencode(config('custom.paypalSignature'));
            $API_Endpoint = 'https://api-3t.paypal.com/nvp';
        }

        $version = urlencode('51.0');

        $nvpStr = '';
        foreach ($nvpArray as $key=>$v) {
            $nvpStr .= '&' . urlencode($key) . '=' . urlencode($v);
        }

        // Set the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set the API operation, version, and API signature in the request.
        $nvpreq = "METHOD=$methodName&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr";

        // Set the request as a POST FIELD for curl.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        // Get response from the server.
        $httpResponse = curl_exec($ch);

        if (! $httpResponse) {
            logWarning("$methodName failed: " . curl_error($ch) . '(' . curl_errno($ch) . ')');

            return false;
        }

        // Extract the response details.
        $httpResponseAr = explode('&', $httpResponse);

        $httpParsedResponseAr = [];
        foreach ($httpResponseAr as $i => $value) {
            $tmpAr = explode('=', $value);
            if (count($tmpAr) > 1) {
                $httpParsedResponseAr[$tmpAr[0]] = urldecode($tmpAr[1]);
            }
        }

        if ((0 == count($httpParsedResponseAr)) || ! array_key_exists('ACK', $httpParsedResponseAr)) {
            logWarning("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");

            return false;
        }

        return $httpParsedResponseAr;
    }

    public static function balance()
    {
        $result = self::command('GetBalance', []);

        return @$result['L_AMT0'];
    }

    /*
    payments - array of arrays containing { email, amount, id, note }
    */

    public static function massPay($emailSubject, $payments)
    {
        /*
    	EMAILSUBJECT - (Optional) The subject line of the email that PayPal sends when the transaction is completed. The subject line is the same for all recipients. Character length and limitations: 255 single-byte alphanumeric characters.
    	L_EMAILn -
    	L_AMTn - amount. cannot mix currencies in a single MassPayRequest.
    	L_UNIQUEIDn
    	L_NOTEn
    	*/

        $commands = ['RECEIVERTYPE' => 'EmailAddress', 'CURRENCYCODE' => 'USD', 'EMAILSUBJECT' => $emailSubject];

        foreach ($payments as $num => $payment) {
            $commands["L_EMAIL$num"] = $payment['email'];
            $commands["L_AMT$num"] = $payment['amount'];
            $commands["L_UNIQUEID$num"] = $payment['id'];
            $commands["L_NOTE$num"] = isset($payment['note']) ? $payment['note'] : '';
        }
        $result = self::command('MassPay', $commands);

        if (strtoupper($result['ACK']) == 'SUCCESS' || strtoupper($result['ACK']) == 'SUCCESSWITHWARNING') {
            return true; // success
        } else {
            logWarning('MassPay failed: ' . print_r($result, true));

            return false;
        }
    }
}
