<?php
namespace Framework;

use Framework\ErrorResponse\ErrorDisplayResponse;
use Framework\Exception\FrameworkException;
use Framework\ErrorResponse\ErrorResponse;
use Exception;
use Framework\Processor\IgnoreProcessor;
use Framework\Processor\PostProcessor;
use Framework\Processor\Processor;

/**
 * Class Route
 *
 * @package Framework
 */
class Route
{

    static function otherwise( $function ) {
        self::action('otherwise', null, $function);
    }

    static function action($method, $path, $function) {

        if ( $method != 'otherwise' ) {

            if ($method != $_SERVER['REQUEST_METHOD'])
                return IgnoreProcessor::getInstance();

            $me = self::getInstance();
            if ($me->is_called == true) return IgnoreProcessor::getInstance();

            $path_array = preg_split("/\//", $path);
            foreach ($path_array as $key => $path_element) {
                if (strlen(trim($path_element)) == 0) {
                    unset($path_array[$key]);
                }
            }
            $path_array = array_values($path_array);

            foreach ($path_array as $key => $path_element) {
                if (preg_match("/^\:(.*)$/", $path_element, $match)
                    || preg_match("/^\{(.*)\}$/", $path_element, $match)
                ) {
                    Input::set($match[1], $me->path[$key]);
                } else {
                    if (!isset($me->path[$key]) ||
                        $me->path[$key] != $path_element
                    ) {
                        return IgnoreProcessor::getInstance();
                    }
                }
            }
        }

        try {
            if ( is_callable( $function ) ) {
                $response = $function();
            } else {

                $function_array = preg_split("/@/", $function );
                if ( !isset($function_array[1]))
                    throw FrameworkException::internalError('Routing Error');

                $class_name = 'App\\Controller\\' . $function_array[0];
                $method_name = $function_array[1];

                $response = $class_name::$method_name();
            }

            if ( $response instanceof Response ) {
                $response->display();
            } else {
                $rs = new Response();
                $rs->setContentType('text/html')
                    ->setContent($response)
                    ->display();
            }
            $me->is_called = true;
        } catch ( FrameworkException $e ) {
            $me->handleError( $e );
            return IgnoreProcessor::getInstance();

        } catch ( Exception $e ) {
            $exception = FrameworkException::internalError('Internal Error: ' . $e->getMessage( ) );
            $me->handleError($exception);
            return IgnoreProcessor::getInstance();
        }
        return $me->getPostProcessor();
    }

    static function get($path, $function) {
        return self::action('GET', $path, $function);
    }
    static function post($path, $function) {
        return self::action('POST', $path, $function);
    }
    static function put($path, $function) {
        return self::action('PUT', $path, $function);
    }
    static function patch($path, $function) {
        return self::action('PATCH', $path, $function);
    }
    static function delete($path, $function) {
        return self::action('DELETE', $path, $function);
    }

    public static function reset( ) {
        self::$me = null;
    }

    public static function setErrorResponse( ErrorResponse $e ) {
        $me = self::getInstance();
        $me->error_response = $e;
    }

    public static function setPostProcessor( Processor $p ) {
        $me = self::getInstance();
        $me->post_processor = $p;
    }

    private function getPostProcessor( ) {
        if ( $this->post_processor ) return $this->post_processor;
        else return PostProcessor::getInstance();
    }


    private static $me;
    private $path;
    private $is_called;
    private $error_response;
    private $post_processor;

    private function __construct( ) {
        $this->is_called = false;
        $this->error_response = null;
        $this->post_processor = null;

        $request_uri = preg_split( "/\?/", $_SERVER['REQUEST_URI'], 2 );
        $requestUri = preg_split( "/\//", $request_uri[0] );
        $scriptName = preg_split( "/\//", $_SERVER['SCRIPT_NAME'] );


        foreach ($scriptName as $key => $value) {
            if ($value == $requestUri[$key]){
                unset($requestUri[$key]) ;
            }
        }
        $this->path = array_values($requestUri);

    }

    private static function getInstance( ) {
        if ( self::$me == null ) {
            self::$me = new Route();
        }
        return self::$me;
    }


    private function handleError( FrameworkException $e ) {
        if ( $this->error_response == null ) {
            if ( Config::get('app.debug', false ) )
                $this->error_response = new ErrorDisplayResponse();
            else $this->error_response = new ErrorResponse();
        }
        $this->error_response->set( $e )->display();
        return true;
    }


}