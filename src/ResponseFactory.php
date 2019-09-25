<?php

namespace Lotos\Http;

use Psr\Http\Message\{ResponseInterface, ResponseFactoryInterface};
use Lotos\Http\Response\{Response, HtmlResponse, JsonResponse};

class ResponseFactory implements ResponseFactoryInterface
{

    public function createResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ) : ResponseInterface {
        return (new Response)->withStatus($code, $reasonPhrase);
    }

    public function createHtmlResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ) : ResponseInterface {
        return (new HtmlResponse)->withStatus($code, $reasonPhrase);
    }

    public function createJsonResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ) : ResponseInterface {
        return (new JsonResponse)->withStatus($code, $reasonPhrase);
    }

    public function createXmlResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ) : ResponseInterface {
        return (new XmlResponse)->withStatus($code, $reasonPhrase);
    }

}
