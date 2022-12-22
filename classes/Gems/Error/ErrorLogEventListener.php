<?php


namespace Gems\Error;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ErrorLogEventListener
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __invoke($error, ServerRequestInterface $request, ResponseInterface $response)
    {
        $formattedError = $this->formatError($error);
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->error($formattedError,
                [
                    'status' => $response->getStatusCode(),
                    'method' => $request->getMethod(),
                    'uri'    => (string) $request->getUri(),
                ]
            );
            return;
        }
        error_log($error);
    }

    /**
     * Format an error message for better logging
     *
     * @param $error
     * @return string formatted error message
     */
    protected function formatError($error)
    {
        // Default Error type, when PHP Error occurs.
            $errorType = sprintf("Fatal error: Uncaught %s", get_class($error));
        if ($error  instanceof \ErrorException) {

            // this is an Exception
            /** @noinspection PhpUndefinedMethodInspection */
            $severity = $error->getSeverity();
            switch($severity) {
                case E_ERROR:
                case E_USER_ERROR:
                    $errorType = 'Fatal error';
                    break;
                case E_USER_WARNING:
                case E_WARNING:
                    $errorType = 'Warning';
                    break;
                case E_USER_NOTICE:
                case E_NOTICE:
                case E_STRICT:
                    $errorType = 'Notice';
                    break;
                case E_RECOVERABLE_ERROR:
                    $errorType = 'Catchable fatal error';
                    break;
                case E_USER_DEPRECATED:
                case E_DEPRECATED:
                    $errorType = "Deprecated";
                    break;
                default:
                    $errorType = 'Unknown error';
            }

            $formattedError = sprintf("PHP %s: %s in %s on line %d", $errorType, $error->getMessage(), $error->getFile(), $error->getLine());
        } else {
            // this is an Error.
            $formattedError = sprintf("PHP %s: %s in %s on line %d \nStack trace:\n%s", $errorType, $error->getMessage(), $error->getFile(), $error->getLine(), $error->getTraceAsString());
        }

        return $formattedError;
    }

    /**
     * Optionally set a logger to log the errors
     *
     * @param LoggerInterface $logger
     */
    public function setErrorLog(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


}
