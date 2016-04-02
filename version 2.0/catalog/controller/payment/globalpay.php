<?php

/**
 * Plugin Name: Zenith GlobalPay OpenCart Payment Gateway
 * Plugin URI:  http://www.globalpay.com.ng
 * Description: Zenith GlobalPay Payment gateway allows you to accept payment on your OpenCart store via Visa Cards, Mastercards, Verve Cards, eTranzact, PocketMoni, Paga, Internet Banking, Bank Branch and Remita Account Transfer.
 * Author:      Oshadami Mike
 * Author URI:  http://www.oshadami.com
 * Version:     1.0
 * * */
class ControllerPaymentGlobalpay extends Controller {

    public function index() {
        $this->language->load('payment/globalpay');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $order_id = $this->session->data['order_id'];
        if ($order_info) {
            $data['globalpay_mercid'] = trim($this->config->get('globalpay_mercid'));
            $data['orderid'] = $this->session->data['order_id'];
            $data['returnurl'] = $this->url->link('payment/globalpay/callback', '', 'SSL');
            $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
            $data['totalAmount'] = html_entity_decode($data['total']);
            $data['payerName'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
            $data['payerEmail'] = $order_info['email'];
            $data['payerPhone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
            $data['customerId'] = $order_info['customer_id'];
            $mode = trim($this->config->get('globalpay_mode'));
            $data['button_confirm'] = $this->language->get('button_confirm');
            $currencyCode = "NGN";
            $uniqueRef = uniqid();
            $globalpayorderid = $uniqueRef . '_' . $data['orderid'];
            $globalpay_args_array = array();
            $globalpay_args = array(
                'merchantid' => $data['globalpay_mercid'],
                'currency' => $currencyCode,
                'amount' => $data['total'],
                'names' => $data['payerName'],
                'email_address' => $data['payerEmail'],
                'phone_number' => $data['payerPhone'],
                'merch_txnref' => $globalpayorderid
            );

            foreach ($globalpay_args as $key => $value) {
                $globalpay_args_array[] = "<input type='hidden' name='" . $key . "' value='" . $value . "'/>";
            }
            if ($mode == 'test') {
                $data['gateway_url'] = 'https://demo.globalpay.com.ng/globalpay_demo/Paymentgatewaycapture.aspx';
            } else if ($mode == 'live') {
                $data['gateway_url'] = 'https://www.globalpay.com.ng/Paymentgatewaycapture.aspx';
            }

            $data['globalpay_hidden_args'] = implode('', $globalpay_args_array);
        }

        //1 - Pending Status
        $message = 'Payment Status : Pending';
        $this->model_checkout_order->addOrderHistory($order_id, 1, $message, false);
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/globalpay.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/globalpay.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/globalpay.tpl', $data);
        }
    }

    function globalpay_transaction_details($txnref) {
        $paymentInfo = array();
        $mode = trim($this->config->get('globalpay_mode'));
        if ($mode == 'test') {
            $endpoint = 'https://demo.globalpay.com.ng/GlobalpayWebService_demo/service.asmx?wsdl';
            $namespace = 'https://www.eazypaynigeria.com/globalpay_demo/';
            $soap_action = 'https://www.eazypaynigeria.com/globalpay_demo/getTransactions';
        } else if ($mode == 'live') {
            $endpoint = 'https://www.globalpay.com.ng/globalpaywebservice/service.asmx?wsdl';
            $namespace = 'https://www.eazypaynigeria.com/globalpay/';
            $soap_action = 'https://www.eazypaynigeria.com/globalpay/getTransactions';
        }
        $soap_client = new nusoap_client($endpoint, TRUE);
        if ($soap_client->getError()) {
            $paymentInfo = FALSE;
            return;
        }

        $params = array(
            'merch_txnref' => $txnref,
            'channel' => '',
            'merchantID' => trim($this->config->get('globalpay_mercid')),
            'start_date' => '',
            'end_date' => '',
            'uid' => trim($this->config->get('globalpay_webservice_user')),
            'pwd' => trim($this->config->get('globalpay_webservice_password')),
            'payment_status' => ''
        );

        $this->log->add($this->id, 'Connecting to GlobalPay at ' . $endpoint);

        // Connect.
        $result = $soap_client->call(
                'getTransactions', array('parameters' => $params), $namespace, $soap_action, FALSE, TRUE
        );

        if ($soap_client->fault) {
            $this->log->add($this->id, 'Error looking transaction\n' . print_r($result, TRUE));
            $paymentInfo = FALSE;
            return;
        }

        $err = $soap_client->getError();
        if ($err) {
            $this->log->add($this->id, 'Error looking transaction\n' . print_r($err, TRUE));
            $paymentInfo = FALSE;
            return;
        }
        $xml = simplexml_load_string($result['getTransactionsResult']);
        $paymentInfo['amount'] = $xml->record->amount;
        $paymentInfo['payment_date'] = $xml->record->payment_date;
        $paymentInfo['channel'] = $xml->record->channel;
        $paymentInfo['status'] = $xml->record->payment_status;
        $paymentInfo['pmt_txnref'] = $xml->record->txnref;
        $paymentInfo['currency'] = $xml->record->field_values->field_values->field[2]->currency;
        $paymentInfo['description'] = $xml->record->payment_status_description;
        return $paymentInfo;
    }

    function updatePaymentStatus($order_id, $status, $description, $pmt_txnref) {
        switch ($status) {
            case "successful":
                $message = 'Payment Status : - Successful - GlobalPay Transaction Reference: ' . $pmt_txnref;
                $this->model_checkout_order->addOrderHistory($order_id, trim($this->config->get('globalpay_processed_status_id')), $message, true);
                break;
            default:
                //process a failed transaction
                $message = 'Payment Status : - Not Successful - Reason: ' . $description . ' - GlobalPay Transaction Reference: ' . $pmt_txnref;
                //1 - Pending Status
                $this->model_checkout_order->addOrderHistory($order_id, 1, $message, true);
                break;
        }
    }

    public function callback() {
        require_once 'lib/nusoap.php';
        $txnref = $_GET["txnref"];
        $response = $this->globalpay_transaction_details($txnref);
        $order_details = explode('_', $txnref);
        $order_id = $order_details[1];
        $data['order_id'] = $order_id;
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $data['status'] = $response['status'];
        $data['pmt_txnref'] = $response['pmt_txnref'];
        $data['description'] = $response['description'];
        $this->updatePaymentStatus($order_id, $data['status'], $data['description'], $data['pmt_txnref']);
        if (isset($this->session->data['order_id'])) {
            $this->cart->clear();
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['guest']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
        }

        $this->language->load('checkout/success');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('common/home'),
            'text' => $this->language->get('text_home'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/cart'),
            'text' => $this->language->get('text_basket'),
            'separator' => $this->language->get('text_separator')
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/checkout', '', 'SSL'),
            'text' => $this->language->get('text_checkout'),
            'separator' => $this->language->get('text_separator')
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/success'),
            'text' => $this->language->get('text_success'),
            'separator' => $this->language->get('text_separator')
        );

        $data['heading_title'] = $this->language->get('heading_title');

        $data['button_continue'] = $this->language->get('button_continue');
        $data['fail_continue'] = $this->url->link('checkout/checkout', '', 'SSL');
        $data['continue'] = $this->url->link('account/order/info', 'order_id=' . $data['order_id'], 'SSL');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['heading_title'] = $this->load->controller('common/heading_title');
        $data['footer'] = $this->load->controller('common/footer');
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/globalpay_success.tpl')) {
            return $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/globalpay_success.tpl', $data));
        } else {
            return $this->response->setOutput($this->load->view('default/template/payment/globalpay_success.tpl', $data));
        }
    }

}

?>