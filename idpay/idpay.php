<?php
/**
 * IDPay payment gateway
 *
 * @developer JMDMahdi
 * @publisher IDPay
 * @copyright (C) 2018 IDPay
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 *
 * http://idpay.ir
 */
if (!defined('_PS_VERSION_'))
    exit;

class idpay extends PaymentModule
{

    private $_html = '';
    private $_postErrors = array();

    public function __construct()
    {

        $this->name = 'idpay';
        $this->tab = 'payments_gateways';
        $this->version = '2.0';
        $this->author = 'Developer: JMDMahdi, Publisher: IDPay';
        $this->currencies = true;
        $this->currencies_mode = 'radio';
        parent::__construct();
        $this->displayName = $this->l('IDPay Payment Module');
        $this->description = $this->l('Online Payment With IDPay');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
        if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
            $this->warning = $this->l('No currency has been set for this module');
        $config = Configuration::getMultiple(array('idpay_api_key'));
        if (!isset($config['idpay_api_key']))
            $this->warning = $this->l('You have to enter your idpay token code to use idpay for your online payments.');

    }

    public function install()
    {
        if (!parent::install() || !Configuration::updateValue('idpay_success_massage', '') || !Configuration::updateValue('idpay_api_key', '') || !Configuration::updateValue('idpay_failed_massage', '') || !Configuration::updateValue('idpay_sandbox', '') || !Configuration::updateValue('idpay_currency', '') || !Configuration::updateValue('idpay_logo', '') || !Configuration::updateValue('idpay_HASH_KEY', $this->hash_key()) || !$this->registerHook('payment') || !$this->registerHook('paymentReturn'))
            return false;
        else
            return true;
    }

    public function uninstall()
    {
        if (!Configuration::deleteByName('idpay_api_key') || !Configuration::deleteByName('idpay_success_massage') || !Configuration::deleteByName('idpay_failed_massage') || !Configuration::deleteByName('idpay_sandbox') || !Configuration::deleteByName('idpay_logo') || !Configuration::deleteByName('idpay_currency') || !Configuration::deleteByName('idpay_HASH_KEY') || !parent::uninstall())
            return false;
        else
            return true;
    }

    public function hash_key()
    {
        $en = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
        $one = rand(1, 26);
        $two = rand(1, 26);
        $three = rand(1, 26);
        return $hash = $en[$one] . rand(0, 9) . rand(0, 9) . $en[$two] . $en[$three] . rand(0, 9) . rand(10, 99);
    }

    public function getContent()
    {

        if (Tools::isSubmit('idpay_submit')) {
            Configuration::updateValue('idpay_api_key', $_POST['idpay_api_key']);
            Configuration::updateValue('idpay_sandbox', $_POST['idpay_sandbox']);
            Configuration::updateValue('idpay_currency', $_POST['idpay_currency']);
            Configuration::updateValue('idpay_success_massage', $_POST['idpay_success_massage']);
            Configuration::updateValue('idpay_failed_massage', $_POST['idpay_failed_massage']);
            $this->_html .= '<div class="conf confirm">' . $this->l('Settings updated') . '</div>';
        }

        $this->_generateForm();
        return $this->_html;
    }

    private function _generateForm()
    {
        $this->_html .= '<div align="center"><form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
        $this->_html .= $this->l('API KEY :') . '<br><br>';
        $this->_html .= '<input type="text" name="idpay_api_key" value="' . Configuration::get('idpay_api_key') . '" ><br><br>';
        $this->_html .= $this->l('Sandbox :') . '<br><br>';
        $this->_html .= '<select name="idpay_sandbox"><option value="yes"' . (Configuration::get('idpay_sandbox') == "yes" ? 'selected="selected"' : "") . '>' . $this->l('Yes') . '</option><option value="no"' . (Configuration::get('idpay_sandbox') == "no" ? 'selected="selected"' : "") . '>' . $this->l('No') . '</option></select><br><br>';
        $this->_html .= $this->l('Currency :') . '<br><br>';
        $this->_html .= '<select name="idpay_currency"><option value="rial"' . (Configuration::get('idpay_currency') == "rial" ? 'selected="selected"' : "") . '>' . $this->l('Rial') . '</option><option value="toman"' . (Configuration::get('idpay_currency') == "toman" ? 'selected="selected"' : "") . '>' . $this->l('Toman') . '</option></select><br><br>';
        $this->_html .= $this->l('Success Massage :') . '<br><br>';
        $this->_html .= '<textarea dir="auto" name="idpay_success_massage" style="margin: 0px; width: 351px; height: 57px;">' . (!empty(Configuration::get('idpay_success_massage')) ? Configuration::get('idpay_success_massage') : "پرداخت شما با موفقیت انجام شد. کد رهگیری: {track_id}") . '</textarea><br><br>';
        $this->_html .= 'متن پیامی که می خواهید بعد از پرداخت به کاربر نمایش دهید را وارد کنید. همچنین می توانید از شورت کدهای {order_id} برای نمایش شماره سفارش و {track_id} برای نمایش کد رهگیری آیدی پی استفاده نمایید.<br><br>';
        $this->_html .= $this->l('Failed Massage :') . '<br><br>';
        $this->_html .= '<textarea dir="auto" name="idpay_failed_massage" style="margin: 0px; width: 351px; height: 57px;">' . (!empty(Configuration::get('idpay_failed_massage')) ? Configuration::get('idpay_failed_massage') : "پرداخت شما ناموفق بوده است. لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید.") . '</textarea><br><br>';
        $this->_html .= 'متن پیامی که می خواهید بعد از پرداخت به کاربر نمایش دهید را وارد کنید. همچنین می توانید از شورت کدهای {order_id} برای نمایش شماره سفارش و {track_id} برای نمایش کد رهگیری آیدی پی استفاده نمایید.<br><br>';
        $this->_html .= '<input type="submit" name="idpay_submit" value="' . $this->l('Save it!') . '" class="button">';
        $this->_html .= '</form><br></div>';
    }

    /**
     * @param \CartCore $cart
     */
    public function do_payment($cart)
    {

        $api_key = Configuration::get('idpay_api_key');
        $sandbox = Configuration::get('idpay_sandbox') == 'yes' ? 'true' : 'false';
        $amount = $cart ->getOrderTotal();
        if (Configuration::get('idpay_currency') == "toman") {
            $amount *= 10;
        }

        // Customer information
        $details = $cart->getSummaryDetails();
        $delivery = $details['delivery'];
        $name = $delivery->firstname . ' ' . $delivery->lastname;
        $phone = $delivery->phone_mobile;

        if (empty($phone_mobile)) {
            $phone = $delivery->phone;
        }
        // There is not any email field in the cart details.
        // So we gather the customer email from this line of code:
        $mail = Context::getContext()->customer->email;

        $desc = $Description = 'پرداخت سفارش شماره: ' . $cart->id;
        $callback = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . 'modules/idpay/callback.php?do=callback&hash=' . md5($amount . $cart->id . Configuration::get('idpay_HASH_KEY'));

        if (empty($amount)) {
            echo $this->error('واحد پول انتخاب شده پشتیبانی نمی شود.');
        }

        $data = array(
            'order_id' => $cart->id,
            'amount' => $amount,
            'name' => $name,
            'phone' => $phone,
            'mail' => $mail,
            'desc' => $desc,
            'callback' => $callback,
        );

        $ch = curl_init('https://test.idpay.ir/v1.1/payment');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-API-KEY:' . $api_key,
            'X-SANDBOX:' . $sandbox,
        ));

        $result = curl_exec($ch);
        $result = json_decode($result);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status != 201 || empty($result) || empty($result->id) || empty($result->link)) {
            echo $this->error(sprintf('خطا هنگام ایجاد تراکنش. وضعیت خطا: %s - کد خطا: %s - پیام خطا: %s', $http_status, $result->error_code, $result->error_message));
        } else {
            echo $this->success($this->l('Redirecting...'));
            echo '<script>window.location=("' . $result->link . '");</script>';
            exit;
        }
    }

    public function error($str)
    {
        return '<div class="alert error" dir="rtl" style="text-align: right">' . $str . '</div>';
    }

    public function success($star)
    {
        echo '<div class="conf confirm" dir="rtl" style="text-align: right">' . $str . '</div>';
    }

    public function hookPayment($params)
    {
        global $smarty;
        $smarty->assign('idpay_logo', Configuration::get('idpay_logo'));
        if ($this->active)
            return $this->display(__FILE__, 'idpay.tpl');
    }

    public function hookPaymentReturn($params)
    {
        if ($this->active)
            return $this->display(__FILE__, 'confirmation.tpl');
    }
}