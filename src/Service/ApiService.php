<?php

namespace ClearCutCoding\PhpApiTools\Service;

use ClearCutCoding\PhpApiTools\Domain\ApiRequest;
use ClearCutCoding\PhpApiTools\Domain\ApiResponse;
use ClearCutCoding\PhpApiTools\Exception\ApiException;
use ClearCutCoding\PhpApiTools\Exception\ApiRedirectException;

class ApiService
{
    /**
     * @throws ApiException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function makeRequest(ApiRequest $request, array $options = []): ApiResponse
    {
        if (!$request->url) {
            throw new ApiException('no url supplied');
        }

        $body = (is_array($request->body) && !($options['opt_form-data'] ?? false))
            ? json_encode($request->body, JSON_THROW_ON_ERROR)
            : $request->body;
        $this->decorateRequestHeaders($request, $body, $options);

        // ////////// OPTIONS /////////////////
        $ch = curl_init($request->url);
        // config
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($options['opt_followredirect'] ?? false) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        // request
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request->headers);

        // response (save headers)
        $responseHeaders = [];
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function (mixed $curl, string $header) use (&$responseHeaders) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) { // ignore invalid headers
                    return $len;
                }

                $responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        // ////////// END OPTIONS /////////////////

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if (in_array($httpCode, [301, 302])) {
            $msg = [
                'error' => 'Redirect occurred, ensure opt_followredirect is set',
                'code' => $httpCode,
                'url' => curl_getinfo($ch, CURLINFO_REDIRECT_URL),
            ];
            curl_close($ch);
            throw new ApiRedirectException(json_encode($msg, JSON_THROW_ON_ERROR));
        }

        if (!in_array($httpCode, [200, 201, 202, 203, 204, 205, 206])) {
            curl_close($ch);
            $message = $request->url . ' - received http code: ' . $httpCode . ' - ' . $error . ' - ' . $result;
            throw new ApiException($message, $httpCode);
        }

        if ($error) {
            curl_close($ch);
            throw new ApiException($request->url . ' - ' . $error . ' - ' . $result, $httpCode);
        }

        curl_close($ch);

        $response = new ApiResponse();
        $response->httpCode = $httpCode;
        $response->body = (string) $result;
        $response->headers = $responseHeaders;

        return $response;
    }

    protected function decorateRequestHeaders(ApiRequest $request, array|string $body, array $options): void
    {
        // NOTE: addHeader() will not add the content-type if one has already been specified in the $request object
        if ($options['opt_form-data'] ?? false) {
            $request->addHeader('content-type', 'multipart/form-data');
        } else {
            $request->addHeader('content-type', 'application/json');
        }

        if (!is_array($body)) {
            $request->addHeader('content-length', strlen($body));
        }
    }
}
