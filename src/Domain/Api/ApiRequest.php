<?php

namespace ClearCutCoding\PhpApiTools\Domain\Api;

class ApiRequest
{
    public ?string $url = null;

    public ?string $method = null;

    public string|array $body = [];

    public array $headers = [];

    /**
     * @param array $headers Must be in format:
     *
     * [
     *   "headername: headervalue",
     *   "headername: headervalue",
     * ]
     */
    public function addHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value, false);
        }
    }

    /**
     * @param mixed $value
     * @param bool  $replace
     *                       - true  => if header name already exists, override it
     *                       - false => if header name already exists, keep it and ignore request
     */
    public function addHeader(string $name, $value, bool $replace = false): void
    {
        if ($replace) {
            $this->removeHeader($name);
        }

        if (!$this->hasHeader($name)) {
            $this->headers[] = $name . ': ' . $value;
        }
    }

    public function removeHeader(string $name): void
    {
        foreach ($this->headers as $i => $header) {
            $h = $this->explodeHeaderString($header);

            if ($h[strtolower($name)] ?? false) {
                unset($this->headers[$i]);
            }
        }

        $this->headers = array_values($this->headers); // reset 0-based index keys in case of a delete
    }

    protected function hasHeader(string $name): bool
    {
        foreach ($this->headers as $header) {
            $h = $this->explodeHeaderString($header);

            if ($h[strtolower($name)] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
    public function explodeHeaderString(string $header): array
    {
        if (stripos($header, ':') !== false) {
            list($headername, $headervalue) = explode(':', $header, 2);

            return [strtolower($headername) => $headervalue];
        }

        return [];
    }
}
