<?php
namespace Framework;
use Framework\Exception\FrameworkException;

/**
 * Class Config
 *
 * @package Framework
 */

class Config
{

    /**
     * Set
     *
     * Basically for testing purpose
     * @param $key
     * @param $value
     */

    public static function set ( $key, $value ) {
        self::$data[$key] = $value;
    }


    /**
     * Get Configuration
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public static function get( $key, $default = null ) {
        if ( ! self::has($key) ) {
            if ( $default instanceof FrameworkException ) throw $default;
            return $default;
        }
        
        $key_array = preg_split('/\./', $key );
        $return = self::$data;

        foreach($key_array as $k)
            $return = $return[$k];

        return $return;
    }
    
    public static function has( $key ) {

//        if ( defined('__APP__') ) $base_path = __APP__;
//        else {
            $base_path_array = preg_split("/\//", __DIR__);
//            $base_path = "";
//            foreach($base_path_array as $b) {
//                if ( $b == "" ) continue;
//                if ( $b == 'vendor' ) break;
//                $base_path .= "/" . $b;
//            }
//        }
//      why do not write like this
        $base_path = defined('__APP__') ? __APP__ : substr(__DIR__,0, strrpos(__DIR__, "vendor") - 1);

        $key_array = preg_split('/\./', $key );
        if ( !isset($key_array[0]) ) return false;

        if ( !isset(self::$data[$key_array[0]])) {
            $file_path = $base_path . '/config/' . $key_array[0] . '.php';
            if ( file_exists($file_path) )
                self::$data[$key_array[0]] = include $file_path;
//            else self::$data[$key_array[0] ] = null;
//          why should not do like this:
            else return false;
            if ( self::$data[$key_array[0]] == false ) {
//                self::$data[$key_array[0]] = null;
                return false;
            }
        }

        $return = self::$data;
        foreach($key_array as $k) {
            if ( !isset($return[$k]) ) {
                return false;
            }
            $return = $return[$k];
        }

        return true;
    }

    private static $data;
}
