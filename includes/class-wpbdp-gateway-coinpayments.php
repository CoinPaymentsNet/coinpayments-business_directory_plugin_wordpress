<?php

class WPBDP__Gateway__Coinpayments extends WPBDP__Payment_Gateway
{

    public function get_id()
    {
        return 'coinpayments';
    }

    public function get_title()
    {
        return _x('CoinPayments.NET', 'coinpayments', 'business-directory-plugin');
    }

    public function get_integration_method()
    {
        return 'form';
    }

    public function get_settings()
    {
        return array(
            array('id' => 'client-id', 'name' => _x('Client ID', 'coinpayments', 'business-directory-plugin'), 'type' => 'text'),
            array('id' => 'webhooks',
                'name' => _x('Webhooks', 'coinpayments', 'business-directory-plugin'),
                'type' => 'select',
                'description' => _x('This controls the plugins mode after checkout.', 'coinpayments', 'business-directory-plugin'),
                'default' => '',
                'options' => array(
                    '' => '-- ' . _x('Enable to use webhooks', 'coinpayments', 'business-directory-plugin') . ' --',
                    'Yes' => _x('Enabled', 'coinpayments', 'business-directory-plugin'),
                    'No' => _x('Disabled', 'coinpayments', 'business-directory-plugin'),
                )
            ),
            array('id' => 'client-secret', 'name' => _x('Client Secret', 'coinpayments', 'business-directory-plugin'), 'type' => 'text'),
        );
    }

    public function supports_currency($currency)
    {
        return true;
    }

    public function validate_settings()
    {
        $client_id = trim($this->get_option('client-id'));
        $webhooks = trim($this->get_option('webhooks'));

        $errors = array();

        if (!$client_id) {
            $errors[] = _x('Client ID is missing.', 'coinpayments', 'business-directory-plugin');
        }

        if ($webhooks == 'Yes') {
            try {
                $coinpayments = $this->get_coinpayments_api();
                if (!$coinpayments->check_webhook($this->get_id())) {
                    $errors[] = _x('Invalid CoinPayments.NET credentials.', 'coinpayments', 'business-directory-plugin');
                }
            } catch (Exception $e) {
                $errors[] = _x($e->getMessage(), 'coinpayments', 'business-directory-plugin');
            }
        }

        return $errors;
    }

    public function process_payment($payment)
    {
        $args = array(
            'payment_id' => $payment->id,
            'payment_key' => $payment->payment_key,
            'listing_id' => $payment->listing_id,
            'amount' => $payment->amount,
            'description' => $payment->summary,
            'currency' => $payment->currency_code,
        );
        $args = array_merge($args, $payment->get_payer_details());
        $invoice = $this->create_coinpayments_invoice($args);
        if (!empty($invoice['id'])) {

            $payment->gateway = $this->get_id();
            $payment->save();

            $coinpayments_args = array(
                'invoice-id' => $invoice['id'],
                'success-url' => $payment->checkout_url,
                'cancel-url' => $payment->checkout_url,
            );
            $coinpayments_args = http_build_query($coinpayments_args, '', '&');
            $redirect_url = sprintf('%s/%s/?%s', WPBDP_Gateway_Coinpayments_API_Handler::CHECKOUT_URL, WPBDP_Gateway_Coinpayments_API_Handler::API_CHECKOUT_ACTION, $coinpayments_args);
        } elseif (!empty($invoice['error'])) {
            $error_msg = _x('Can\'t create CoinPayments.NET invoice!', 'coinpayments', 'business-directory-plugin');
            return array('result' => 'failure', 'error' => $error_msg);
        }

        return array('result' => 'success', 'redirect' => $redirect_url);
    }

    public function process_postback()
    {
        @ob_clean();

        $signature = $_SERVER['HTTP_X_COINPAYMENTS_SIGNATURE'];
        $content = file_get_contents('php://input');

        $coinpayments = $this->get_coinpayments_api();

        $request_data = json_decode($content, true);

        if ($coinpayments->check_data_signature($signature, $content, $this->get_id()) && isset($request_data['invoice']['invoiceId'])) {
            $invoice_str = $request_data['invoice']['invoiceId'];
            $invoice_str = explode('|', $invoice_str);

            $host_hash = array_shift($invoice_str);
            $invoice_id = array_shift($invoice_str);

            if ($host_hash == md5(get_site_url())) {
                $payment = WPBDP_Payment::objects()->get($invoice_id);
                $payment->gateway_tx_id = $request_data['invoice']['id'];
                if ($request_data['invoice']['status'] == 'Pending') {
                    $payment->status = 'pending';
                } elseif ($request_data['invoice']['status'] == 'Completed') {
                    $payment->status = 'completed';
                } elseif ($request_data['invoice']['status'] == 'Cancelled') {
                    $payment->status = 'canceled';
                }
                $payment->save();
            }
        }
    }

    protected function create_coinpayments_invoice($args = array())
    {
        $coinpayments = $this->get_coinpayments_api();

        $invoice_id = sprintf('%s|%s', md5(get_site_url()), $args['payment_id']);

        try {

            $currency_code = $args['currency'];
            $coin_currency = $coinpayments->get_coin_currency($currency_code);

            $amount = intval(number_format($args['amount'], $coin_currency['decimalPlaces'], '', ''));
            $display_value = $args['amount'];

            $invoice = $coinpayments->create_invoice($invoice_id, $coin_currency['id'], $amount, $display_value);
            if ($this->get_option('webhooks') == 'Yes') {
                $invoice = array_shift($invoice['invoices']);
            }
        } catch (Exception $e) {
        }

        return $invoice;
    }

    protected function get_coinpayments_api()
    {
        if (!class_exists('WPBDP_Gateway_Coinpayments_API_Handler')) {
            require_once dirname(__FILE__) . '/class-wpbdp-gateway-coinpayments-api-handler.php';
        }

        $client_id = trim($this->get_option('client-id'));
        $webhooks = trim($this->get_option('webhooks'));
        $client_secret = trim($this->get_option('client-secret'));

        return new WPBDP_Gateway_Coinpayments_API_Handler($client_id, $webhooks, $client_secret);
    }
}