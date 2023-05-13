<?php

namespace ClearCutCoding\PhpApiTools\Domain;

class ApiResponse
{
    public int $httpCode = 0;

    public string $body = '';

    /**
     * Will store headers in the format:
     *  ['name' => 'value'].
     */
    public array $headers = [];

    /**
     * Will store cookies in the format:
     *  [
     *      'name' => '',
     *      'value' => '',
     *      'path' => '',
     *      'HttpOnly' => true,
     * ].
     *
     * Cannot access directly, as cookies must be calculated if not already done so
     */
    protected array $cookies = [];

    public function getCookies(): array
    {
        if (count($this->cookies)) {
            return $this->cookies;
        }

        $this->setCookiesFromHeader();

        return $this->cookies;
    }

    protected function setCookiesFromHeader(): void
    {
        $cookies = [];
        $headers = $this->headers['set-cookie'] ?? null;

        if (!$headers) {
            return;
        }

        foreach ($headers as $header) {
            preg_match_all('/^([^;]*)/mi', (string) $header, $matchFound);

            /** @var string $item */
            foreach ($matchFound[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
            }
        }

        $this->cookies = $cookies;
    }
}
