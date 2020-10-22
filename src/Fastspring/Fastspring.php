<?php
/**
 * This class helps to reach the Fastspring class with Laravel config and static
 * methods.
 *
 * @author    Bilal Gultekin <bilal@gultekin.me>
 * @version   0.1:
 * @copyright 2019 22 Digital
 * @license   MIT
 * @see       https://docs.fastspring.com/integrating-with-fastspring/fastspring-api
 */

namespace TwentyTwoDigital\CashierFastspring\Fastspring;

/**
 * This class describes the Fastspring implementation.
 */
class Fastspring
{
    /**
     * Instance of Fastspring class.
     *
     * @var array
     */
    public static $instance;

    /**
     * Static method.
     *
     * It is not useful to construct this Fastspring class everytime. This helps
     * to construct this class with the current config. if there is not any
     * constructed instance then construct and save it to self::$instance
     *
     * @param string $method     The method
     * @param array  $parameters The parameters for username and password
     *
     * @return self
     */
    public static function __callStatic($method, $parameters)
    {
        if (!self::$instance) {
            $username = ($_ENV['FASTSPRING_USERNAME'] ?: config('services.fastspring.username'));
            $password = ($_ENV['FASTSPRING_PASSWORD'] ?: config('services.fastspring.password'));

            self::$instance = new ApiClient($username, $password);
        }

        return call_user_func_array([self::$instance, $method], $parameters);
    }
}
