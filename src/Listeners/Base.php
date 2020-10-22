<?php
/**
 * This file implements Base.
 *
 * @author    Bilal Gultekin <bilal@gultekin.me>
 * @author    Justin Hartman <justin@22digital.co.za>
 * @copyright 2019 22 Digital
 * @license   MIT
 * @since     v0.1
 */

namespace TwentyTwoDigital\CashierFastspring\Listeners;

/**
 * This class describes a base.
 */
class Base
{
    /**
     * Get the billable entity instance by Fastspring ID.
     *
     * @param string $fastspringId
     *
     * @return \TwentyTwoDigital\CashierFastspring\Billable
     */
    public function getUserByFastspringId($fastspringId)
    {
        $model = $_ENV['FASTSPRING_MODEL'] ?: config('services.fastspring.model');

        return (new $model())->where('fastspring_id', $fastspringId)->first();
    }
}
