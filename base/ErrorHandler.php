<?php
namespace base;
use base\exceptions\HttpException;

/**
 * ErrorHandler handles uncaught PHP errors and exceptions.
 */
class ErrorHandler
{
    /**
     * @var \Exception the exception that is being handled currently.
     */
    public $exception;
    /**
     * Register this error handler
     */
    public function register()
    {
        ini_set('display_errors', false);
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleFatalError']);
    }

    /**
     * Unregisters this error handler by restoring the PHP error and exception handlers.
     */
    public function unregister()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Handles PHP execution errors such as warnings and notices.
     *
     * This method is used as a PHP error handler. It will simply raise an ErrorException.
     *
     * @param integer $code the level of the error raised.
     * @param string $message the error message.
     * @param string $file the filename that the error was raised in.
     * @param integer $line the line number the error was raised at.
     * @return boolean whether the normal error handler continues.
     *
     * @throws \ErrorException
     */
    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() & $code) {
            $exception = new \ErrorException($message, $code, $code, $file, $line);
            throw $exception;
        }
        return false;
    }

    /**
     * Handles fatal PHP errors
     */
    public function handleFatalError()
    {
        $error = error_get_last();
        if (isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
            $exception = new \ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            $this->exception = $exception;
            $this->logException($exception);
            $this->renderException($exception);
            exit(1);
        }
    }

    /**
     * Handles uncaught PHP exceptions.
     *
     * This method is implemented as a PHP exception handler.
     *
     * @param \Exception $exception the exception that is not caught
     */
    public function handleException($exception)
    {
        $this->exception = $exception;

        // disable error capturing to avoid recursive errors while handling exceptions
        $this->unregister();

        // set preventive HTTP status code to 500 in case error handling somehow fails and headers are sent
        // HTTP exceptions will override this value in renderException()
        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
        }

        try {
            $this->logException($exception);
            $this->renderException($exception);
        } catch (\Exception $e) {
            // an other exception could be thrown while displaying the exception
//            $msg = "An Error occurred while handling another error:\n";
//            $msg .= (string) $e;
//            $msg .= "\nPrevious exception:\n";
//            $msg .= (string) $exception;
//            if (APPMODE_DEBUG) {
//                if (PHP_SAPI === 'cli') {
//                    echo $msg . "\n";
//                } else {
//                    echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES) . '</pre>';
//                }
//            } else {
//                echo 'An internal server error occurred.';
//            }
//            $msg .= "\n\$_SERVER = " . VarDumper::export($_SERVER);
//            error_log($msg);
//            if (defined('HHVM_VERSION')) {
//                flush();
//            }
//            exit(1);
        }

        $this->exception = null;
    }

    /**
     * Logs the given exception
     * @param \Exception $exception the exception to be logged
     */
    public function logException($exception)
    {
        $category = get_class($exception);
        if ($exception instanceof HttpException) {
            $category = 'base\\HttpException:' . $exception->statusCode;
        } elseif ($exception instanceof \ErrorException) {
            $category .= ':' . $exception->getSeverity();
        }
        Application::error($exception, $category);
    }
    /**
     * Renders the exception.
     * @param \Exception $exception the exception to be rendered.
     */
    protected function renderException($exception)
    {
        $response = new Response();
        if(APPMODE_DEBUG){
            $response->data = '<pre>' .print_r($exception, true). '</pre>';
        }else{
            //TODO: implement in production mode
        }
        if ($exception instanceof HttpException) {
            $response->setStatusCode($exception->statusCode);
        } else {
            $response->setStatusCode(500);
        }
        $response->send();
    }
}