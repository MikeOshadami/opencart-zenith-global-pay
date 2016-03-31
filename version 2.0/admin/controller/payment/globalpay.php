<?php

/**
 * Plugin Name: Zenith GlobalPay OpenCart Payment Gateway
 * Plugin URI:  http://www.globalpay.com.ng
 * Description: Zenith GlobalPay Payment gateway allows you to accept payment on your OpenCart store via Visa Cards, Mastercards, Verve Cards, eTranzact, PocketMoni, Paga, Internet Banking, Bank Branch and Remita Account Transfer.
 * Author:      Oshadami Mike
 * Author URI:  http://www.oshadami.com
 * Version:     1.0
 */
class ControllerPaymentGlobalpay extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('payment/globalpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('globalpay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['entry_mercid'] = $this->language->get('entry_mercid');
        $data['entry_debug'] = $this->language->get('entry_debug');
        $data['entry_test'] = $this->language->get('entry_test');
        $data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $data['entry_processed_status'] = $this->language->get('entry_processed_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['globalpay_mercid'])) {
            $data['error_mercid'] = $this->error['globalpay_mercid'];
        } else {
            $data['error_mercid'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/globalpay', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['action'] = $this->url->link('payment/globalpay', 'token=' . $this->session->data['token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['globalpay_mercid'])) {
            $data['globalpay_mercid'] = $this->request->post['globalpay_mercid'];
        } else {
            $data['globalpay_mercid'] = $this->config->get('globalpay_mercid');
        }

        if (isset($this->request->post['globalpay_debug'])) {
            $data['globalpay_debug'] = $this->request->post['globalpay_debug'];
        } else {
            $data['globalpay_debug'] = $this->config->get('globalpay_debug');
        }

        if (isset($this->request->post['globalpay_pending_status_id'])) {
            $data['globalpay_pending_status_id'] = $this->request->post['globalpay_pending_status_id'];
        } else {
            $data['globalpay_pending_status_id'] = $this->config->get('globalpay_pending_status_id');
        }

        if (isset($this->request->post['globalpay_processed_status_id'])) {
            $data['globalpay_processed_status_id'] = $this->request->post['globalpay_processed_status_id'];
        } else {
            $data['globalpay_processed_status_id'] = $this->config->get('globalpay_processed_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['globalpay_geo_zone_id'])) {
            $data['globalpay_geo_zone_id'] = $this->request->post['globalpay_geo_zone_id'];
        } else {
            $data['globalpay_geo_zone_id'] = $this->config->get('globalpay_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['globalpay_status'])) {
            $data['globalpay_status'] = $this->request->post['globalpay_status'];
        } else {
            $data['globalpay_status'] = $this->config->get('globalpay_status');
        }

        if (isset($this->request->post['globalpay_sort_order'])) {
            $data['globalpay_sort_order'] = $this->request->post['globalpay_sort_order'];
        } else {
            $data['globalpay_sort_order'] = $this->config->get('globalpay_sort_order');
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('payment/globalpay.tpl', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'payment/globalpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['globalpay_mercid']) {
            $this->error['globalpay_mercid'] = $this->language->get('error_mercid');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

}

?>