<?php
class MageShop_HubBling_Helper_Data extends Mage_Core_Helper_Abstract
{

    const URL_BLIG = 'https://www.bling.com.br/';
    const URL_BLIG_API = 'https://api.bling.com.br/';

    /**
     * Gets the configuration value by path
     *
     * @param string $path System Config Path
     *
     * @return mixed
     */
    public function getConfigData($path)
    {
        return Mage::getStoreConfig("hubbling/settings/{$path}");
    }

    public function getOAuth()
    {
        return self::URL_BLIG . 'Api/v3/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->getConfigData('client_public_key'),
            'state' => bin2hex(random_bytes(16)),
        ]);
    }

    public function getUrlAPI()
    {
        return self::URL_BLIG_API;
    }

    public function getAPI()
    {
        return new MageShop_HubBling_Service_Rest();
    }

    public function base64Client()
    {
        return base64_encode($this->getConfigData('client_public_key') . ":" . $this->getConfigData('client_secret_key'));
    }

    public function get($endpoint, $params = [])
    {
        $url = $this->getUrlAPI() . $endpoint;

        // Concatenar parâmetros na URL (correto para GET)
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $webserver = $this->getAPI()
            ->url($url)
            ->_header(["Authorization: Bearer " . $this->getConfigData('token')])
            ->_method('GET')
            ->exec();

        if (!$webserver->success() && $webserver->unauthorized()) {
            if (!$this->refreshToken()) { 
                throw new Exception("Falha ao renovar o token.");
            }

            return [];
        }
        return $webserver;
    }

    public function refreshToken()
    {
        return $this->newToken('Api/v3/oauth/token', [
            'grant_type'   => 'refresh_token',
            'refresh_token' => $this->getConfigData('refresh_token')
        ]);
    }

    public function newToken($endpoint, $params)
    {
        $url = $this->getUrlAPI() . $endpoint;
        $webserver = $this->getAPI()
            ->url($url)
            ->_header([
                "Authorization: Basic " . $this->base64Client(),
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: 1.0"
            ])
            ->_method('POST')
            ->_body(http_build_query($params))
            ->exec();

        if ($webserver->success()) {
            $res = $webserver->toArray();
            $data = [
                'token' => $res['access_token'],
                'refresh_token' => $res['refresh_token'],
                'expires_in' => $res['expires_in'],
                'token_type' => $res['token_type'],
                'scope' => $res['scope'],
            ];
            $this->saveConfig($data);
            return $res;
        }
        throw new \Exception("Você não está autorizado a realizar esta operação. Verifique suas credenciais e tente novamente.", 1);
    }

    public function saveConfig($data)
    {
        foreach ($data as $path => $value) {
            Mage::getModel('core/config')->saveConfig("hubbling/settings/{$path}", $value);
            // Opcional: Limpar o cache para garantir que o novo valor seja usado
        }
        Mage::app()->getCacheInstance()->flush();
    }
}
