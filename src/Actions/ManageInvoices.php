<?php

namespace Vrajroham\LaravelBitpay\Actions;

use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Exceptions\RefundCancellationException;
use BitPaySDK\Model\Invoice\Buyer;
use BitPaySDK\Model\Invoice\Invoice;
use BitPaySDK\Model\Invoice\Refund;
use Vrajroham\LaravelBitpay\Constants\WebhookAutoPopulate;


trait ManageInvoices
{
    /**
     * Get BitPay Invoice instance.
     *
     * @link https://bitpay.com/api/#rest-api-resources-invoices-resource
     *
     * @param float|null  $price    float The amount for which the invoice will be created.
     * @param string|null $currency string three digit currency code used to compute the invoice bitcoin amount.
     *
     * @return Invoice
     */
    public static function Invoice(float $price = null, string $currency = null): Invoice
    {
        return new Invoice($price, $currency);
    }

    /**
     * @return Buyer
     */
    public static function Buyer(): Buyer
    {
        return new Buyer();
    }

    /**
     * Create a BitPay invoice.
     *
     * @link https://bitpay.com/api/#rest-api-resources-invoices-create-an-invoice
     *
     * @param $invoice Invoice An Invoice object with request parameters defined.
     *
     * @return Invoice $invoice A BitPay generated Invoice object.
     * @throws BitPayException BitPayException class
     */
    public static function createInvoice(Invoice $invoice): Invoice
    {
        $thisInstance = new self();

        try {
            if (empty($invoice->getNotificationURL()) &&
                in_array(WebhookAutoPopulate::For_Invoices, $thisInstance->config['auto_populate_webhook'])) {
                $invoice->setNotificationURL(route('laravel-bitpay.webhook.capture'));
            }
        } catch (\Throwable $exception) {
            // Misconfiguration or route macro not in use
        }

        return $thisInstance->client->createInvoice($invoice);
    }

    /**
     * Retrieve a BitPay invoice by its id.
     *
     * @link https://bitpay.com/api/#rest-api-resources-invoices-retrieve-an-invoice
     *
     * @param $invoiceId string The id of the invoice to retrieve.
     *
     * @return Invoice A BitPay Invoice object.
     * @throws BitPayException BitPayException class
     */
    public static function getInvoice(string $invoiceId): Invoice
    {
        return (new self())->client->getInvoice($invoiceId);
    }

    /**
     * Retrieve a collection of BitPay invoices.
     *
     * @link https://bitpay.com/api/#rest-api-resources-invoices-retrieve-invoices-filtered-by-query
     *
     * @param $dateStart string The start of the date window to query for invoices. Format YYYY-MM-DD.
     * @param $dateEnd   string The end of the date window to query for invoices. Format YYYY-MM-DD.
     * @param $status    string|null The invoice status you want to query on.
     * @param $orderId   string|null The optional order id specified at time of invoice creation.
     * @param $limit     int|null Maximum results that the query will return (useful for paging results).
     * @param $offset    int|null Number of results to offset (ex. skip 10 will give you results starting with the 11th result).
     *
     * @return Invoice[] A list of BitPay Invoice objects.
     * @throws BitPayException BitPayException class
     */
    public static function getInvoices(
        string $dateStart,
        string $dateEnd,
        string $status = null,
        string $orderId = null,
        int    $limit = null,
        int    $offset = null
    ): array {
        return (new self())->client->getInvoices($dateStart, $dateEnd, $status, $orderId, $limit, $offset);
    }

    /**
     * Get BitPay refund instance.
     *
     * @return Refund
     */
    public static function Refund(): Refund
    {
        return new Refund();
    }

    /**
     * Create a BitPay refund.
     *
     * @link https://bitpay.com/api/#rest-api-resources-invoices-refund-an-invoice
     *
     * @param $invoice          Invoice A BitPay invoice object for which a refund request should be made.  Must have
     *                          been obtained using the merchant facade.
     * @param $refundEmail      string The email of the buyer to which the refund email will be sent
     * @param $amount           float The amount of money to refund. If zero then a request for 100% of the invoice
     *                          value is created.
     * @param $currency         string The three digit currency code specifying the exchange rate to use when
     *                          calculating the refund bitcoin amount. If this value is "BTC" then no exchange rate
     *                          calculation is performed.
     *
     * @return bool True if the refund was successfully created, false otherwise.
     * @throws BitPayException BitPayException class
     */
    public static function createRefund(
        Invoice $invoice,
        string  $refundEmail,
        float   $amount,
        string  $currency
    ): bool {
        return (new self())->client->createRefund($invoice, $refundEmail, $amount, $currency);
    }

    /**
     * Retrieve all refund requests on a BitPay invoice.
     *
     * @link https://bitpay.com/api/#rest-api-resources-invoices-retrieve-all-refund-requests-on-an-invoice
     *
     * @param $invoice  Invoice The BitPay invoice having the associated refunds.
     *
     * @return Refund[] An array of BitPay refund object with the associated Refund object updated.
     * @throws BitPayException BitPayException class
     */
    public static function getRefunds(Invoice $invoice): array
    {
        return (new self())->client->getRefunds($invoice);
    }

    /**
     * Retrieve a previously made refund request on a BitPay invoice.
     *
     * @link https://bitpay.com/api/#rest-api-resources-invoices-retrieve-a-refund-request
     *
     * @param $invoice  Invoice The BitPay invoice having the associated refund.
     * @param $refundId string The refund id for the refund to be updated with new status.
     *
     * @return Refund A BitPay refund object with the associated Refund object updated.
     * @throws BitPayException BitPayException class
     */
    public static function getRefund(Invoice $invoice, string $refundId): Refund
    {
        return (new self())->client->getRefund($invoice, $refundId);
    }

    /**
     * Cancel a previously submitted refund request on a BitPay invoice.
     *
     * @link https://bitpay.com/api/#rest-api-resources-invoices-cancel-a-refund-request
     *
     * @param $invoiceId string The refund id for the refund to be canceled.
     * @param $refund    Refund The BitPay invoice having the associated refund to be canceled.
     *                   Must have been obtained using the merchant facade.
     *
     * @return bool True if the refund was successfully canceled, false otherwise.
     * @throws RefundCancellationException RefundCancellationException class
     */
    public static function cancelRefund(string $invoiceId, Refund $refund): bool
    {
        return (new self())->client->cancelRefund($invoiceId, $refund);
    }

    // TODO: Awaiting upstream merge: https://github.com/bitpay/php-bitpay-client-v2/pull/69
//    /**
//     * Request the last BitPay Invoice webhook to be resent.
//     *
//     * @link https://bitpay.com/api/#rest-api-resources-invoices-request-a-webhook-to-be-resent
//     *
//     * @param string $invoiceId The id of the invoice for which you want the last webhook to be resent.
//     * @param string $invoiceToken The resource token for the `invoiceId` you want the webhook to be resent.
//     *
//     * @return bool True if the webhook has been resent for the current invoice status, false otherwise.
//     * @throws  \BitPaySDK\Exceptions\BitPayException BitPayException class
//     */
//    public static function requestInvoiceWebhook(string $invoiceId, string $invoiceToken): bool
//    {
//        return (new self())->client->requestInvoiceWebhook($invoiceId, $invoiceToken);
//    }
}
