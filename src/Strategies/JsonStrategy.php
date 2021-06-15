<?php

namespace Lotos\Http\Strategies;

use Lotos\Http\StrategyInterface;
use Psr\Http\Message\ResponseInterface;

class JsonStrategy implements StrategyInterface
{
    private $responseBody;

    public function process(ResponseInterface $response) : void
    {
        $this->resolve($response->withHeader('Content-type', 'application/json; charset=utf-8'));
    }

    private function resolve(ResponseInterface $response) : void
    {
        $content = unserialize($response->getBody());
        $content = ($content === false) ? '' : $content;
        $body = json_encode($content, JSON_UNESCAPED_UNICODE);
        $body = str_replace(',',', ', $body);
        $body = str_replace('\/', '/', $body);
        $body = str_replace('\\\\', '{slash}', $body);
        $body = str_replace('\\', '', $body);
        $body = str_replace('{slash}u', '\u', $body);
        $body = str_replace('{slash}', '\\', $body);
        $body = trim($body, '"');
        $hash = md5($body);
        $response = $response->withHeader('Content-MD5', $hash)
                ->withHeader('Access-Control-Expose-Headers', $this->getAllowedHeaders($response));
        header(
            'HTTP/' . $response->getProtocolVersion() .
            ' ' . $response->getStatusCode() .
            ' ' . $response->getReasonPhrase());
        foreach ($response->getHeaders() as $header => $values) {
            header($header . ':' . implode(',', $values));
        }
        printf($body);
    }

    private function getAllowedHeaders(ResponseInterface $response) : string
    {
        $headers = [];
        foreach($response->getHeaders() as $header => $values) {
            array_push($headers, $header);
        }
        return implode(',', $headers);
    }

    private function normalizeString(string $string) : string
    {
        return str_replace(',',', ', json_encode(json_decode($string)));
    }
}
