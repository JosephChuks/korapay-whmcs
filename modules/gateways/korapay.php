<?php

/**
 * Korapay WHMCS Payment Gateway Module Configuration
 *
 * This Payment Gateway module allows you to integrate Korapay payment solutions with the
 * WHMCS platform.
 *
 * For more information, please refer to the online documentation: 
 * https://developers.korapay.com/
 * 
 * 
 * @author Joseph Chuks <info@josephchuks.com>
 *
 * @copyright Copyright (c) Joseph Chuks 2024
 */



if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


function korapay_MetaData()
{
    return array(
        'DisplayName' => 'Korapay',
        'APIVersion' => '1.0',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function korapay_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Korapay',
        ),
        'publicKey' => array(
            'FriendlyName' => 'Public Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter public key here',
        ),

        'currency' => array(
            'FriendlyName' => 'Currency',
            'Type' => 'dropdown',
            'Options' => array(
                'NGN' => 'Nigerian Naira',
                'KES' => 'Kenyan Shilling',
                'GHS' => 'Ghana Cedis',
            ),
            'Description' => 'Choose currency',
        ),

        'channels' => array(
            'FriendlyName' => 'Payment channels',
            'Type' => 'dropdown',
            'Options' => array(
                'all' => 'All',
                'card' => 'Card Only',
                'pay_with_bank' => 'Bank App Only',
                'bank_transfer' => 'Bank Transfer Only',
                'mobile_money' => 'Mobile Money only',
            ),
            'Description' => 'Hold ctrl to select multiple',
        ),

        'defaultChannel' => array(
            'FriendlyName' => 'Defaul Payment Channel',
            'Type' => 'dropdown',
            'Options' => array(
                'card' => 'Card Payment',
                'pay_with_bank' => 'Bank App Payment',
                'bank_transfer' => 'Bank Transfer',
                'mobile_money' => 'Mobile Money',
            ),
            'Description' => 'Choose default payment channel',
        ),
        
        'transactionFees' => array(
            'FriendlyName' => 'Merchant Pay Transaction Fees',
            'Type' => 'dropdown',
            'Options' => array(
                'false' => 'No',
                'true' => 'Yes'
            ),
            'Description' => 'Select Yes to Pay transaction fees or No for customer to pay',
        ),

        'payButtonText' => array(
            'FriendlyName' => 'Pay Button Text',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'Pay Now',
            'Description' => 'Text to display on your payment button',
        ),


        'gatewayLogs' => array(
            'FriendlyName' => 'Gateway logs',
            'Type' => 'yesno',
            'Description' => 'Select to enable gateway logs',
            'Default' => '0'
        ),
    );
}


function korapay_link($params)
{

    // Gateway Configuration Parameters
    $publicKey = $params['publicKey'];
    $payButtonText = $params['payButtonText'];
    $transactionFees = $params['transactionFees'];
    $defaultChannel = !empty($params['defaultChannel']) ? $params['defaultChannel'] : 'card';
    $selectedChannels = $params['channels'];
    if($selectedChannels === 'all'){
        $channels = ["card", "bank_transfer", "pay_with_bank", "mobile_money"];
    } else if ($selectedChannels === 'card'){
        $channels = ["card"];
    } else if ($selectedChannels === 'bank_transfer'){
        $channels = ["bank_transfer"];
    } else if ($selectedChannels === 'pay_with_bank'){
        $channels = ["pay_with_bank"];
    }else if ($selectedChannels === 'mobile_money'){
        $channels = ["mobile_money"];
    } else {
        $channels = ["card", "bank_transfer", "pay_with_bank", "mobile_money"];
    }
    

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];

    // System Parameters
    $systemUrl = $params['systemurl'];
    $moduleName = $params['paymentmethod'];
    $returnUrl = "'.$systemUrl.'viewinvoice.php?id='.$invoiceId.'";
    $redirectUrl = $systemUrl . 'modules/gateways/callback/' . $moduleName . '.php';


    $htmlOutput = '<script src="https://korablobstorage.blob.core.windows.net/modal-bucket/korapay-collections.min.js"></script>';
    $htmlOutput .= '<form>';
    $htmlOutput .= '<button type="button" id="start-payment-button" 
    style="cursor: pointer;
    position: relative;
    background-color: #ff9b00;
    color: #12122c;
    max-width: 100%;
    padding: 7.5px 16px;
    font-weight: 500;
    font-size: 14px;
    border-radius: 4px;
    border: none;
    transition: all .1s ease-in;
    vertical-align: middle;" onclick="payKorapay()">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <rect x="3" y="6" width="18" height="12" rx="2" ry="2" />
  <line x1="3" y1="10" x2="21" y2="10" />
  <line x1="3" y1="14" x2="21" y2="14" />
    </svg>&nbsp;
' . $payButtonText . '
    </button>
    <button type="button" id="paySuccess"
    style="cursor: pointer;
    display:none;
    position: relative;
    background-color: #ff9b00;
    color: #12122c;
    max-width: 100%;
    padding: 7.5px 16px;
    font-weight: 500;
    font-size: 14px;
    border-radius: 4px;
    border: none;
    transition: all .1s ease-in;
    vertical-align: middle;" disabled>
    Please Wait...
    </button>
    ';
    $htmlOutput .= '</form>';
    $htmlOutput .= '<script>
        function payKorapay() {
            window.Korapay.initialize({
            key: "' . $publicKey . '",
            reference: "' . $invoiceId . '",
            amount: ' . $amount . ', 
            currency: "' . $currencyCode . '",
            customer: {
              name: "' . $firstname . ' ' . $lastname . '",
              email: "' . $email . '",
            },
            narration: "' . $description . '",
            channels: ' . json_encode($channels) . ',
            default_channel: "' . $defaultChannel . '",
            merchant_bears_cost: ' . $transactionFees . ',
            notification_url: "' . $redirectUrl . '",
            metadata: {
                consumer_id: "' . $invoiceId . '",
                consumer_mac: "' . $_SERVER['REMOTE_ADDR'] . '",
              },
            onClose: function () {
              location.href = "viewinvoice.php?id='.$invoiceId.'";
            },
            onSuccess: function (data) {
            document.getElementById("paySuccess").style.display = "block";
            document.getElementById("start-payment-button").style.display = "none";
            setTimeout(function () {
              location.href = "viewinvoice.php?id='.$invoiceId.'";
              }, 5000);
            },
            onFailed: function (data) {
              alert("Transaction failed! Please try again");
              location.href = "viewinvoice.php?id='.$invoiceId.'";
            }
            
            });
          }
        </script>';


    return $htmlOutput;
}
