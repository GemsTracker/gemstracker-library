<?php

namespace Gems\Site;

use Exception;
use Throwable;

class NotAllowedUrlException extends Exception
{
    private string $url;

    public function __construct(string $url, int $code = 0, ?Throwable $previous = null)
    {
        $this->url = $url;
        $message = sprintf('Url %s is not known', $url);
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }


}