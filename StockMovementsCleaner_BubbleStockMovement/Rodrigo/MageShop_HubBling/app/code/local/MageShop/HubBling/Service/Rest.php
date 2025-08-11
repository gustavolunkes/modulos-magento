<?php

class MageShop_HubBling_Service_Rest
{
    /**
     * Inicio do request
     *
     * @var resource|false|CurlHandle
     */
    private $_curl;
    /**
     * Url para consultar
     *
     * @var string
     */
    private $_url;
    /**
     * Push
     *
     * @var string|array
     */
    private $data = null;
    /**
     * Cabeçalho
     *
     * @var array
     */
    private $headers = array('Content-Type: application/json');
    /**
     * Tipo da requisição
     *
     * @var string
     */
    private $method_header = "GET";
    private $options;
    private $_res;
    /**
     * Instancia
     *
     * @param string $url
     * @return object|self
     */
    public function __construct()
    {
        $this->_curl = curl_init();
    }
    /**
     * Set url
     *
     * @param string $url
     * @return object
     */
    public function url($url)
    {
        $this->_url = $url;
        return $this;
    }
    /**
     * Define o cobeçalho da req
     *
     * @param array $header
     * @return object|self
     */
    public function _header($header)
    {
        $this->headers = $header;
        return $this;
    }
    /**
     * Copo da req
     *
     * @param string|array $data
     * @return object|self 
     */
    public function _body($data)
    {
        $this->data = $data;
        return $this;
    }
    /**
     * Tipo de req
     * 
     * @param string $method
     * @return object|self
     */
    public function _method($method)
    {
        $this->method_header = $method;
        switch ($method) {
            case 'post':
            case 'put':
            case 'delete':
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $this->options[CURLOPT_POST] = true;
                break;
        }
        return $this;
    }
    /**
     * Executa o curl
     *
     * @return object|self
     */
    private function _inicialize()
    {
        $this->options = [
            CURLOPT_URL => $this->_url,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_CUSTOMREQUEST => $this->method_header,
        ];

        // Se houver dados a serem enviados no POST, adicione-os
        if ($this->data !== null) {
            $this->options[CURLOPT_POSTFIELDS] = $this->data;
        }
        return $this;
    }

    public function setCurl($field, $value)
    {
        $this->options[$field] = $value;
        return $this;
    }
    /**
     * Returno da api
     *
     * @return string|array
     */
    public function getResponse()
    {
        return isset($this->_res['response']) ? $this->_res['response'] : null;
    }

    public function toArray()
    {
        return $this->getResponse() ? json_decode($this->getResponse(), true) : null;
    }

    public function toJson()
    {
        return $this->getResponse() ? json_decode($this->getResponse()) : null;
    }
    /**
     * Status de retorno
     *
     * @return int|string
     */
    public function getStatusCode()
    {
        return isset($this->_res['status_code']) ? $this->_res['status_code'] : null;
    }
    /**
     * Executa uma requisição na api
     *
     * @return object|self
     */
    public function exec()
    {
        $this->_inicialize();
        curl_setopt_array($this->_curl, $this->options);
        $response = curl_exec($this->_curl);
        if (curl_errno($this->_curl)) {
            throw new Exception("MageShop_HubBling_Service_Rest error: " . curl_error($this->_curl));
        }
        $status_code = curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
        curl_close($this->_curl);
        $this->_res = array(
            'status_code' => $status_code,
            'response' => $response
        );
        return $this;
    }

    public function success()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() <= 299;
    }

    public function unauthorized()
    {
        return $this->getStatusCode() == 401;
    }

    public function error()
    {
         // Definindo os códigos de status que indicam um erro
        $errorStatusCodes = [
            400, // Bad Request
            401, // Unauthorized
            403, // Forbidden
            404, // Not Found
            412, // Precondition Failed
            422, // Unprocessable Entity
            429, // Too Many Requests
            500, // Internal Server Error
        ];

        // Verifica se o código de status está na lista de códigos de erro
        if (in_array($this->getStatusCode(), $errorStatusCodes)) {
            return json_decode($this->getResponse(), true); // Retorna o corpo da resposta em caso de erro
        } else {
            return null; // Retorna null se não houver erro
        }

    }
    public function __destruct()
    {
        $this->_curl = null;
        unset($this->_curl);
    }
}
