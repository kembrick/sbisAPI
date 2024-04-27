<?php

namespace sbisAPI;
use Exception;

class SbisStore
{
    private string $token;

    /**
     * @param string $appClientId
     * @param string $appSecret
     * @param string $secretKey
     * @throws Exception
     */
    public function __construct(string $appClientId, string $appSecret, string $secretKey)
    {
        $this->authenticateByKey($appClientId, $appSecret, $secretKey);
    }

    /**
     * @param string $appClientId
     * @param string $appSecret
     * @param string $secretKey
     * @return void
     * @throws Exception
     */
    private function authenticateByKey(string $appClientId, string $appSecret, string $secretKey)
    {
        $url = 'https://online.sbis.ru/oauth/service/';
        $data = [
            'app_client_id' => $appClientId,
            'app_secret'    => $appSecret,
            'secret_key'    => $secretKey
        ];
        $token = $this->query($url, $data)['access_token'];
        if (isset($token))
            $this->token = $token;
    }

    /**
     * @param string $url
     * @param array $postData
     * @return mixed
     * @throws Exception
     */
    private function query(string $url, array $postData = [])
    {
        $header = [
            'Content-type: charset=utf-8'
        ];
        if (isset($this->token))
            $header[] = 'X-SBISAccessToken: ' . $this->token;
        $data = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => $header,
        ];
        $ch = curl_init();
        if (count($postData)) {
            $postData = json_encode($postData);
            $data[CURLOPT_POST] = true;
            $data[CURLOPT_POSTFIELDS] = $postData;
        } else {
            $data[CURLOPT_FOLLOWLOCATION] = false;
        }
        curl_setopt_array($ch, $data);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response)
            throw new Exception('Ошибка. Не получен ответ по адресу ' . $url, 500);
        $responseJson = @json_decode($response, true);
        if (!$responseJson)
            throw new Exception('Ошибка: "' . $response . '"', 500);
        else
            $response = $responseJson;
        if (isset($response['error']) && $response['error'])
            throw new Exception('Ошибка: "' . $response['error']['message'] .'"');

        return $response;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getDataByUrl($url)
    {
        return $this->query($url);
    }

}