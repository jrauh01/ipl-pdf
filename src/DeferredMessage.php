<?php

namespace ipl\Pdf;

class DeferredMessage
{
    private string $method;

    private array $params;

    public function __construct(string $method, array $params)
    {
        $this->method = $method;
        $this->params = $params;
    }

    public function __toString(): string
    {
        $shortenValues = function ($params) use (&$shortenValues) {
            foreach ($params as &$value) {
                if (is_array($value)) {
                    $value = $shortenValues($value);
                } elseif (is_string($value)) {
                    $shortened = substr($value, 0, 256);
                    if ($shortened !== $value) {
                        $value = $shortened . '...';
                    }
                }
            }

            return $params;
        };
        $shortenedParams = $shortenValues($this->params);

        return sprintf(
            'Received CDP event: %s(%s)',
            $this->method,
            join(',', array_map(function ($param) use ($shortenedParams) {
                return $param . '=' . json_encode($shortenedParams[$param]);
            }, array_keys($shortenedParams)))
        );
    }
}