<?php
/**
 * ControllerPaymentPaypal
 * @version V 0.1
 * @author Alberto Vara (C) Copyright 2013
 * @package OLIF.ControllerPaymentPaypal
 *
 * @desc Controlador para pagar con PayPal
 */
/* Example response:
 [TOKEN] => EC-3F3834943R725443J
 [SUCCESSPAGEREDIRECTREQUESTED] => false
 [TIMESTAMP] => 2012-03-22T14:54:45Z
 [CORRELATIONID] => 4df6f7e92cfef
 [ACK] => Success
 [VERSION] => 86.0
 [BUILD] => 2649250
 [TRANSACTIONID] => 8DA90762TF455360P
 [TRANSACTIONTYPE] => expresscheckout
 [PAYMENTTYPE] => instant
 [ORDERTIME] => 2012-03-22T14:54:43Z
 [AMT] => 5.95
 [TAXAMT] => 0.00
 [CURRENCYCODE] => EUR
 [PAYMENTSTATUS] => Pending
 [PENDINGREASON] => authorization
 [REASONCODE] => None
 [PROTECTIONELIGIBILITY] => Eligible
 [INSURANCEOPTIONSELECTED] => false
 [SHIPPINGOPTIONISDEFAULT] => false
 [PAYMENTINFO_0_TRANSACTIONID] => 8DA90762TF455360P
 [PAYMENTINFO_0_TRANSACTIONTYPE] => expresscheckout
 [PAYMENTINFO_0_PAYMENTTYPE] => instant
 [PAYMENTINFO_0_ORDERTIME] => 2012-03-22T14:54:43Z
 [PAYMENTINFO_0_AMT] => 5.95
 [PAYMENTINFO_0_TAXAMT] => 0.00
 [PAYMENTINFO_0_CURRENCYCODE] => EUR
 [PAYMENTINFO_0_PAYMENTSTATUS] => Pending
 [PAYMENTINFO_0_PENDINGREASON] => authorization
 [PAYMENTINFO_0_REASONCODE] => None
 [PAYMENTINFO_0_PROTECTIONELIGIBILITY] => Eligible
 [PAYMENTINFO_0_PROTECTIONELIGIBILITYTYPE] => ItemNotReceivedEligible,UnauthorizedPaymentEligible
 [PAYMENTINFO_0_SECUREMERCHANTACCOUNTID] => PUWCE6VUMAG4Y
 [PAYMENTINFO_0_ERRORCODE] => 0
 [PAYMENTINFO_0_ACK] => Success
 * */
namespace Olif;

require_once CORE_ROOT . CONTROLLERS . DIRECTORY_SEPARATOR . "ControllerApp.php";

class ControllerPaymentPaypal extends ControllerApp
{

    /**
     * *
     * $type
     * Opciones permitidas: Sanbox y live
     */
    private $type = array(
        '0' => 'sanbox',
        '1' => 'live'
    );

    /**
     * *
     * $signature
     * token para conectar con paypal
     */
    private $signature;

    /**
     * *
     * $api_signature
     * API Servers for API Signature Security
     */
    private $api_signature;

    /**
     * *
     * $api_certificate
     * API Servers for API Signature Security
     */
    private $api_certificate;

    /**
     * *
     * $api_websrc
     * API Servers for API Signature Security
     */
    private $api_websrc;

    /**
     * *
     * $api_username
     * Usuario receptor de los pagos
     */
    private $api_username;

    /**
     * *
     * $api_passowrd
     * Contraseña de suario receptor de los pagos
     */
    private $api_password;

    /**
     * *
     * $pp_v
     * Paypal version
     */
    private $pp_v = "86.0";

    /**
     * *
     * $paymentaction
     * Paypal version
     */
    private $paymentaction = "Authorization";

    /**
     * *
     * $method
     * Paypal version
     */
    private $method = "SetExpressCheckout";

    /**
     * *
     * $returnUrl
     * Paypal version
     */
    private $returnUrl = "";

    /**
     * *
     * $cancelUrl
     * Paypal version
     */
    private $cancelUrl = "";

    /**
     * *
     * $amt
     * Precio del producto a cobrar
     */
    private $amt = "";

    /**
     * *
     * $dbSent
     * array de variables enviadas, usada para validaciones
     */
    private $dbSent = array();

    /**
     * *
     * $dbRequest
     * array de variables recibidas, usada para validaciones
     */
    private $dbRequest = array();

    /**
     * *
     * $ppToken
     * token que se recibe cuando se conecta con paypal
     */
    private $ppToken = "";

    /**
     * $ch
     * Variable donde se inicialica Curl
     */
    private $ch = null;

    /**
     * $table_operations
     * Tabla donde guardar las transacciones
     */
    private $table_operations = "";

    /**
     * $client_session_name
     * Nombre de la variable de sesión donde se guarda el Identificador del cliente que está operando
     */
    private $client_session_name = 'client_id';

    /**
     * $productName
     * Variable donde se inicialica Curl
     */
    private $productName = "";

    /**
     * $productDesc
     * Variable donde se inicialica Curl
     */
    private $productDesc = "";

    /**
     * construct
     * $mode => configura si las operaciones se van a hacer en podo de prueba(Sandbox) o producción (Live)
     */
    public function __construct($mode = 0)
    {
        $this->init($mode);
    }

    public function init($mode)
    {
        $type_s = $this->type[$mode];
        if ($type_s == 'sanbox') {
            $this->api_signature = 'https://api-3t.sandbox.paypal.com/nvp';
            $this->api_certificate = 'https://api-3t.sandbox.paypal.com/nvp';
            $this->api_websrc = "https://www.sandbox.paypal.com/cgi-bin/webscr";
            $this->signature = "AFcWxV21C7fd0v3bYYYRCpSSRl31A4h1HkJxcvYsq1SPSXTvbGRhi-hW";
            $this->api_username = "a.vara.1986-facilitator_api1.gmail.com";
            $this->api_password = "1385920097";
        } elseif ($type_s == 'live') {
            $this->api_signature = 'https://api-3t.paypal.com/nvp';
            $this->api_certificate = 'https://api-3t.paypal.com/nvp';
            $this->api_websrc = "https://www.paypal.com/cgi-bin/webscr";
            $this->signature = PAYPAL_SIGNATURE;
            $this->api_username = PAYPAL_USERNAME;
            $this->api_password = PAYPAL_PASSWORD;
        } else {
            // $this->sendError("[ERROR ".get_class($this)."::".__FUNCTION__."::".__LINE__."] No se fijó un modo para pay");
        }
        $this->getControllerCurl();
        $this->curl->initEcurl();
        $this->getControllerSession();
        $this->getControllerFormat();
    }

    /**
     * getSignature
     * Devuelve la Signature asociada
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * getDataSent
     * Devuelve las variables enviadas en la última transacción a Paypal.
     * Sirve para depurar
     */
    public function getDataSent()
    {
        return $this->dbSent;
    }

    /**
     * getDataRequest
     * Devuelve las variables enviadas por Paypal.
     * Sirve para depurar
     */
    public function getDataRequest()
    {
        return $this->dbRequest;
    }

    /**
     * getToken
     * Devuelve la Signature asociada
     */
    public function getToken()
    {
        if (strlen($this->ppToken) == 0) {
            return $this->session->get('token');
        } else {
            return $this->ppToken;
        }
    }

    /**
     * getExpressCheckoutUrl
     * Devuelve la Signature asociada
     */
    public function getExpressCheckoutUrl()
    {
        $url = $this->api_websrc . "?cmd=" . urlencode('_express-checkout') . "&rm=2&token=" . urlencode($this->ppToken);
        return $url;
    }

    /**
     * setReturnUrl
     * Asigna la url de retorno
     */
    public function setReturnUrl($url)
    {
        $this->returnUrl = $url;
    }

    /**
     * setCancelUrl
     * Asigna la url de retorno en caso de cancelaciÃ³n
     */
    public function setCancelUrl($url)
    {
        $this->cancelUrl = $url;
    }

    /**
     * setProductName
     * Asigna la url de retorno en caso de cancelaciÃ³n
     */
    public function setProductName($value)
    {
        $this->productName = $value;
    }

    /**
     * setProductDesc
     * Asigna la url de retorno en caso de cancelaciÃ³n
     */
    public function setProductDesc($value)
    {
        $this->productDesc = $value;
    }

    /**
     * Fija el precio
     * Asigna la url de retorno en caso de cancelaciÃ³n
     */
    public function setAmt($amount)
    {
        $amount = $this->format->numberToSQL($amount);
        if (is_numeric($amount) && strlen($amount) > 0 && $amount > 0) {
            $this->amt = $amount;
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * setExpressCheckout
     * Crea una conexión con PayPal, creando el TOKEN
     */
    public function setExpressCheckout()
    {
        $this->method = 'SetExpressCheckout';
        if ($this->returnUrl != '' && $this->cancelUrl != '') {
            $paypal_data = array(
                'USER' => urlencode($this->api_username),
                'PWD' => urlencode($this->api_password),
                'SIGNATURE' => urlencode($this->signature),
                'VERSION' => urlencode($this->pp_v),
                'PAYMENTREQUEST_0_PAYMENTACTION' => urlencode($this->paymentaction),
                'PAYMENTREQUEST_0_CURRENCYCODE' => urlencode('EUR'),
                'METHOD' => urlencode($this->method),
                'LOCALECODE' => 'ES',
                'RM' => urlencode('2'),
                'L_PAYMENTREQUEST_0_NAME0' => ($this->productName),
                'L_PAYMENTREQUEST_0_DESC0' => ($this->productDesc),
                'L_PAYMENTREQUEST_0_AMT0' => urlencode($this->amt),
                'PAYMENTREQUEST_0_AMT' => urlencode($this->amt),
                'RETURNURL' => ($this->returnUrl),
                'CANCELURL' => ($this->cancelUrl)
            );
            /* Debug */
            $this->dbSent = $paypal_data;
            /* ***** */
            $this->curl->assignVars($paypal_data);
            $this->curl->setESSL();
            /* ***** */
            // getting response from server
            $result = $this->curl->getURL($this->api_signature, true);
            parse_str(($result), $result);
            /* Debug */
            $this->dbRequest = $result;
            /* ***** */
        } else {
            $this->sendError("[ERROR " . get_class($this) . "::" . __FUNCTION__ . "::" . __LINE__ . "]No se especificó la url de retorno o de cancelación");
        }
        if (strlen($result['TOKEN']) > 0 && $result['ACK'] == 'Success') {
            $this->ppToken = $result['TOKEN'];
            $this->session->set('token', $result['TOKEN']);
        } else {
            $this->sendError("[ERROR " . get_class($this) . "::" . __FUNCTION__ . "::" . __LINE__ . "] Error al validar la conexión Paypal: <pre>" . print_r($result, true) . "</pre> \n<br> Información enviada:<pre>" . print_r($paypal_data, true) . "</pre>");
        }
        return $result;
    }

    /**
     * getExpressCheckout
     * Nos devuelve la información de la compra
     */
    public function getExpressCheckout()
    {
        $this->method = 'GetExpressCheckoutDetails';
        $paypal_data = array(
            'USER' => urlencode($this->api_username),
            'PWD' => urlencode($this->api_password),
            'SIGNATURE' => urlencode($this->signature),
            'VERSION' => urlencode($this->pp_v),
            'METHOD' => urlencode($this->method),
            'TOKEN' => urlencode($this->getToken())
        );
        /* Debug */
        $this->dbSent = $paypal_data;
        /* ***** */
        $this->curl->assignVars($paypal_data);
        $this->curl->setESSL();
        /* ***** */
        // getting response from server
        $result = $this->curl->getURL($this->api_signature, true);
        parse_str($result, $result);
        /* Debug */
        $this->dbRequest = $result;
        /* ***** */
        if ($this->checkPayPalTokens($result['TOKEN']) && $result['ACK'] == 'Success') {
            return $result;
        } else {
            $this->sendError("[ERROR " . get_class($this) . "::" . __FUNCTION__ . "::" . __LINE__ . "] Error al validar la conexión Paypal: <pre>" . print_r($result, true) . "</pre> \n<br> Información enviada:<pre>" . print_r($paypal_data, true) . "</pre>");
        }
    }

    /**
     * checkPayPalTokens
     * Pasamos un token y nos verifica si es el toquen de la sesiÃ³n creada
     */
    public function checkPayPalTokens($token)
    {
        $token_session = $this->session->get('token');
        if ($token === $token_session && strlen($token) > 0 && strlen($token_session) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * doExpressCheckoutPayment
     * Cobramo$ la pasta
     */
    public function doExpressCheckoutPayment($token, $payerid, $product)
    {
        $this->curl->initEcurl();
        $this->method = 'DoExpressCheckoutPayment';
        $paypal_data = array(
            'USER' => urlencode($this->api_username),
            'PWD' => urlencode($this->api_password),
            'SIGNATURE' => urlencode($this->signature),
            'VERSION' => urlencode($this->pp_v),
            'PAYMENTACTION' => 'Authorization',
            'METHOD' => urlencode($this->method),
            'AMT' => $product['PRICE'],
            'CURRENCYCODE' => urlencode('EUR'),
            'TOKEN' => urlencode($token),
            'PAYERID' => urlencode($payerid)
        );
        /* Debug */
        $this->dbSent = $paypal_data;
        /* ***** */
        $this->curl->assignVars($paypal_data);
        $this->curl->setESSL();
        /* ***** */
        // getting response from server
        $result = $this->curl->getURL($this->api_signature, true);
        parse_str($result, $result);
        /* Debug */
        $this->dbRequest = $result;
        if ($this->checkPayPalTokens(@$result['TOKEN']) && $result['ACK'] == 'Success') {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * getTransactionDetails [DEPRECATED]
     * Crea una conexión con PayPal, creando el TOKEN
     */
    public function getTransactionDetails($id_transaction)
    {
        $this->method = 'GetTransactionDetails';
        if (strlen($id_transaction) > 0) {
            $paypal_data = array(
                'METHOD' => urlencode($this->method),
                'TRANSACTIONID' => urlencode($id_transaction),
                'USER' => urlencode($this->api_username),
                'PWD' => urlencode($this->api_password),
                'SIGNATURE' => urlencode($this->signature),
                'VERSION' => urlencode($this->pp_v)
            );
            $this->dbSent = $paypal_data;
            /* ***** */
            $this->curl->assignVars($paypal_data);
            $this->curl->setESSL();
            /* ***** */
            // getting response from server
            $result = $this->curl->getURL($this->api_signature, true);
            parse_str($result, $result);
            $this->dbRequest = $result;
        } else {
            $result = false;
        }
        return $result;
    }
}