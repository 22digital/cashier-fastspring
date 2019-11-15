<?php

namespace TwentyTwoDigital\CashierFastspring\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model as Eloquent;
use TwentyTwoDigital\CashierFastspring\Billable;

class User extends Eloquent
{
    use Billable;
}
