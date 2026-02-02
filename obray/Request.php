<?php

namespace d3yii2\d3printeripp\obray;

use d3yii2\d3printeripp\obray\transport\IPPPayload;
use Exception;
use obray\ipp\exceptions\AuthenticationError;
use obray\ipp\exceptions\HTTPError;
use obray\ipp\interfaces\RequestInterface;

class Request implements RequestInterface
{
    /**
     * send
     *
     * This method applies request headers, formulates the request and then
     * parses the response into a response payload.
     *
     * @param string $printerURI
     * @param string $encodedPayload This is the actual payload of the request
     * @param string|null $user
     * @param string|null $password
     * @param array $curlOptions
     * @return IPPPayload
     * @throws AuthenticationError
     * @throws HTTPError
     * @throws Exception
     */

    public static function send(
        string $printerURI,
        string $encodedPayload,
        string $user=null,
        string $password=null,
        array $curlOptions=[]
    ): IPPPayload
    {
        // interpret ipp request into http request
        $results = parse_url($printerURI);
        $postURL = $printerURI;
        if(empty($results['path'])) {
            $results['path'] = '';
        }
        if ($results['scheme'] === 'ipp') {
            $postURL = 'http://' . $results['host'] . ':' . ($results['port'] ?? '631'). $results['path'];
        }

        // setup headers
        $headers = array(
            0 => "Content-Type: application/ipp",
            1 => "Content-Length: " . strlen($encodedPayload),
            2 => "Connection: close"
        );
        if(!empty($user) && !empty($password)){
            $headers[] = "Authorization: Basic " . base64_encode($user.':'.$password);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$postURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedPayload);

        forEach($curlOptions as $curlOption){
            if (!isset($curlOption['key'], $curlOption['value'])) {
                continue;
            }
            curl_setopt($ch, $curlOption['key'], $curlOption['value']);
        }

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        $info = curl_getinfo($ch);

        curl_close($ch);

        if((int)$info['http_code'] === 401) {
            throw new AuthenticationError('401');
        }
        if ((int)$info['http_code'] !== 200) {
            throw new HTTPError($info['http_code']);
        }

        // Further processing ...
        $responsePayload = new IPPPayload();
        $responsePayload->decode($server_output);
        return $responsePayload;

    }
}
