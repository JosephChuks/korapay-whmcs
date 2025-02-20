<?php

/**
 * Korapay WHMCS Payment Gateway Module Callback
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


// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$input = file_get_contents('php://input');
$webhookData = json_decode($input, true);


if (isset($webhookData['event']) && $webhookData['event'] === 'charge.success') {

    $invoiceId = $webhookData['data']['payment_reference'];
    $transactionId = $webhookData['data']['reference'];
    $status = $webhookData['data']['transaction_status'];
    $fee = (float) $webhookData['data']['fee'];

    // Check invoice
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['invoiceid']);

    // Check Transaction
    checkCbTransID($transactionId);

    // Convert to base currency
    $amt = (float) $webhookData['data']['amount'];
    $paymentAmount = $amt - $fee;

    if ($gatewayParams['convertto']) {
        $result = select_query("tblclients", "tblinvoices.invoicenum,tblclients.currency,tblcurrencies.code", array("tblinvoices.id" => $invoiceId), "", "", "", "tblinvoices ON tblinvoices.userid=tblclients.id INNER JOIN tblcurrencies ON tblcurrencies.id=tblclients.currency");
        $data = mysql_fetch_array($result);
        $invoice_currency_id = $data['currency'];

        $converto_amount = convertCurrency($paymentAmount, $gatewayParams['convertto'], $invoice_currency_id);
        $amount = format_as_currency($converto_amount);
    } else {
        $amount = number_format(floatval($paymentAmount), 2, '.', '');
    }

    // Log Payment
    if ($gatewayParams['gatewayLogs'] == 'on') {
        $output = "Transaction ref: " . $transactionId
            . "\r\nInvoice ID: " . $invoiceId
            . "\r\nStatus: succeeded";
        logTransaction($gatewayModuleName, $output, "Success");
    }

    // Add Invoice
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $amount,
        $fee,
        $gatewayModuleName
    );
} else {
    if ($gatewayParams['gatewayLogs'] == 'on') {
        $output = "Transaction ref: " . $transactionId
            . "\r\nInvoice ID: " . $invoiceId
            . "\r\nStatus: failed";
        logTransaction($gatewayModuleName, $output, "Failed");
    }
}
