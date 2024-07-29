# Laravel Cashier FastSpring ![Beta](https://img.shields.io/badge/status-beta-green.svg)

[badge_packagist]:      https://img.shields.io/badge/packagist-22digital/laravel--cashier--fastspring-f28d1a.svg
[badge_release]:        https://img.shields.io/packagist/v/22digital/laravel-cashier-fastspring.svg
[badge_license]:        https://img.shields.io/github/license/22digital/laravel-cashier-fastspring.svg
[badge_coverage]:       https://scrutinizer-ci.com/g/22digital/laravel-cashier-fastspring/badges/coverage.png?b=master
[badge_build]:          https://scrutinizer-ci.com/g/22digital/laravel-cashier-fastspring/badges/build.png?b=master
[badge_laravel]:        https://img.shields.io/badge/Laravel-5.x--6.x-green.svg
[badge_styleci]:        https://github.styleci.io/repos/219102431/shield?branch=master
[badge_travis]:         https://travis-ci.org/22digital/laravel-cashier-fastspring.svg?branch=master

[link-contributors]:    https://github.com/22digital/laravel-cashier-fastspring/graphs/contributors
[link-packagist]:       https://packagist.org/packages/22digital/laravel-cashier-fastspring
[link-build]:           https://scrutinizer-ci.com/g/22digital/laravel-cashier-fastspring/build-status/master
[link-coverage]:        https://scrutinizer-ci.com/g/22digital/laravel-cashier-fastspring/?branch=master
[link-license]:         https://github.com/22digital/laravel-cashier-fastspring/blob/master/LICENSE
[link-laravel]:         https://laravel.com/
[link-styleci]:         https://github.styleci.io/repos/219102431
[link-travis]:          https://travis-ci.org/22digital/laravel-cashier-fastspring

[![Packagist][badge_packagist]][link-packagist]
[![Build Status][badge_build]][link-build]
[![Coverage Status][badge_coverage]][link-coverage]
[![Style CI][badge_styleci]][link-styleci]
[![Laravel 5][badge_laravel]][link-laravel]
[![License][badge_license]][link-license]

![Laravel Cashier Fastspring][hero]

## Table of contents
- [Introduction](#introduction)
- [Installation](#installation)
- [Configuration](#configuration)
    - [Migrations](#migrations)
    - [Billable Model](#billable-model)
    - [API Keys](#api-keys)
    - [Creating Plans](#creating-plans)
- [Quick Start](#quick-start)
- [Usage](#usage)
    - [Subscriptions](#subscriptions)
        - [Creating Subscriptions](#creating-subscription)
        - [Checking Subscription Status](#checking-subscription-status)
        - [Changing Plans](#changing-plans)
        - [Subscription Periods](#subscription-period)
        - [Subscription Trials](#subscription-trials)
        - [Cancelling Subscriptions](#cancelling-subscriptions)
        - [Resuming Subscriptions](#resuming-subscriptions)
        - [Updating Credit Cards](#updating-credit-cards)
    - [Webhooks](#webhooks)
    - [Single Charges](#single-charges)
    - [Invoices](#invoices)
- [Contributing](#contributing)
- [Credits](#credits)
- [Licence](#licence)

## Introduction

Cashier Fastspring is a cashier-like laravel package which provides interface to [Fastspring](https://fastspring.com) subscription and payment services. This package handles webhooks and provides a simple API for Fastspring. Before using this package, looking at [Fastspring documentation](http://docs.fastspring.com/) is strongly recommended.

## Installation

Add `22digital/laravel-cashier-fastspring` package to your dependencies.

```bash
composer require "22digital/laravel-cashier-fastspring"
```

After requiring package, add service provider of this package to providers in `config/app.php`.

```php
'providers' => array(
    // ...
    TwentyTwoDigital\CashierFastspring\CashierServiceProvider::class,
)
```

## Configuration

### Migrations

Cashier Fastspring package comes with database migrations. After requiring the package, you can publish it with following command.

```bash
php artisan vendor:publish
```

After publishing them, you can find migration files in your `database/migrations` folder. Remember that you can modify them according to your needs.

### Billable Model

Next, add the Billable trait to your model definition. This trait provides various methods to allow you to perform common billing tasks, such as creating and checking subscriptions, getting orders etc.

```php
use TwentyTwoDigital\CashierFastspring\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

### API Keys

You should add Fastspring configuration to `config/services.php` file.

```php
'fastspring' => [
    'model' => App\User::class,
    'username' => env('FASTSPRING_USERNAME'),
    'password' => env('FASTSPRING_PASSWORD'),

    // strongly recommend to set hmac secret in webhook configuration
    // to prevent webhook spoofing
    'hmac_secret' => env('FASTSPRING_HMAC_SECRET')
],
```

### Webhook Route

Fastspring can notify your application of a variety of events via webhooks. To handle webhooks, define a route and also set it in Fastspring settings.

```php
use TwentyTwoDigital\CashierFastspring\Http\Controllers\WebhookController;

Route::post('/webhook/fastspring', [WebhookController::class, 'handleWebhook'])->name('webhook.fastspring');
```

#### Webhooks & CSRF Protection

Fastspring webhook requests need to bypass CSRF protection. That's why be sure to list your webhook URI as an exception in your `VerifyCsrfToken` middleware.

```php
protected $except = [
    'fastspring/*',
];
```

### Creating Plans

This package does not cover creating plans at Fastspring side or storing created plans. You should create your subscription plans at [Fastspring's Dashboard](https://dashboard.fastspring.com).

## Quick Start

Cashier Fastspring comes with built-in listeners which you can find in `src/Events` for quickstart. These listeners help you to sync subscriptions and invoices with your database.

Remember that you can create and use your listeners and database structure according to your needs. In order to customize, you can check [Usage](#usage).

In Cashier Fastspring, every webhook request fires related events. You can register listeners to webhook events in `app/providers/EventServiceProvider.php`. You can see more at [Webhooks](#webhooks).

```php
use TwentyTwoDigital\CashierFastspring\Events\OrderCompleted;
use TwentyTwoDigital\CashierFastspring\Events\SubscriptionActivated;
use TwentyTwoDigital\CashierFastspring\Events\SubscriptionCanceled;
use TwentyTwoDigital\CashierFastspring\Events\SubscriptionChargeCompleted;
use TwentyTwoDigital\CashierFastspring\Events\SubscriptionDeactivated;
use TwentyTwoDigital\CashierFastspring\Events\SubscriptionPaymentOverdue;
use TwentyTwoDigital\CashierFastspring\Listeners\OrderCompleted as OrderCompletedListener;
use TwentyTwoDigital\CashierFastspring\Listeners\SubscriptionChargeCompleted as SubscriptionChargeCompletedListener;
use TwentyTwoDigital\CashierFastspring\Listeners\SubscriptionDeactivated as SubscriptionDeactivatedListener;
use TwentyTwoDigital\CashierFastspring\Listeners\SubscriptionStateChanged;

protected $listen = [
    // some others
    SubscriptionCanceled::class => [
        SubscriptionStateChanged::class,
    ],
    SubscriptionDeactivated::class => [
        SubscriptionDeactivatedListener::class,
    ],
    SubscriptionPaymentOverdue::class => [
        SubscriptionStateChanged::class,
    ],
    OrderCompleted::class => [
        OrderCompletedListener::class,
    ],
    SubscriptionActivated::class => [
        SubscriptionActivatedListener::class,
    ],
    SubscriptionChargeCompleted::class => [
        SubscriptionChargeCompletedListener::class,
    ],
];
```

You should create a session for subscription payment page. You can do it with `newSubscription` method as below.

```php
// we create session and return it to frontend to care
$builder = Auth::user()->newSubscription('default', $selectedPlan);
$session = $builder->create();
```


You can provide session id `$session->id` to Fastspring's [Popup Storefronts](http://docs.fastspring.com/storefronts/popup-storefronts-on-your-website) or [Web Storefronts](http://docs.fastspring.com/storefronts/web-storefronts).


Note: `newSubscription` method does not create a subscription model. After a successful payment, you can use [webhooks](#webhooks) or [browser script](http://docs.fastspring.com/integrating-with-fastspring/webhooks#Webhooks-BrowserScripts) to inform your app and create related models.

## Usage

Cashier Fastspring comes with ready-to-use `Subscription`, `Subscription Period`, `Invoice` models and webhook handler. You can find detailed explanation below. Remember that you can easily replace these models and logic with yours.

### Subscriptions

Cashier Fastspring provides a `local` type of subscription which lets you to create subscriptions without interacting with Fastspring. This may help you to create plans without payment info required. If a subscription has no `fastspring_id`, it is typed as local. You can check type using `type()`, `isLocal()`, `isFastspring()` methods.

#### Creating Subscriptions

To create a subscription, you can use `newSubscription` method of the `Billable` model. After creating session, you can provide session id `$session->id` to Fastspring's [Popup Storefronts](http://docs.fastspring.com/storefronts/popup-storefronts-on-your-website) or [Web Storefronts](http://docs.fastspring.com/storefronts/web-storefronts).

```php
// we create session and return it to frontend to care
$builder = Auth::user()->newSubscription('default', $selectedPlan);
$session = $builder->create();
```

You can also provide coupon or quantity. As a hint, coupons also can be set on Fastspring's payment pages.

```php
$builder = Auth::user()->newSubscription('default', $selectedPlan)
    ->withCoupon('free-ticket-to-Mars')
    ->quantity(1); // yeap no ticket for returning
$session = $builder->create();
```

If the `Billable` model is not created as Fastspring customer yet `newSubscription` model creates it automatically and saves `fastspring_id`. If you want to do this manually you can use `createAsFastspringCustomer` method.

```php
$apiResponse = Auth::user()->createAsFastspringCustomer();
```

If details of a `Billable` model is updated, you can also update them at Fastspring side with `updateAsFastspringCustomer` method.

```php
$apiResponse = Auth::user()->updateAsFastspringCustomer();
```

#### Checking Subscription Status

At Fastspring side, there are 5 states for subscriptions: `active`, `overdue`, `canceled`, `deactivated`, `trial`. The only state you should give up to serve your customer is `deactivated` state. Others are just informative states. Cashier Fastspring package keeps synchronized state of subscriptions with webhooks.

You can check if you should still serve to the `Billable` model by using `subscribed` method.

```php
if ($user->subscribed('default')) {
    //
}
```

You can retrieve related subscription model by using `subscription` method and use methods of `Subscription` methods to check its status.

```php
$subscription = $user->subscription('default');

// check if you should serve or not
$subscription->valid();

// check if its state is active
$subscription->active();

// check if its state is deactived
$subscription->deactivated();

// check if its state is overdue
$subscription->overdue();

// alias: onTrial(). check if its state is trial
$subscription->trial();

// alias: canceled(), onGracePeriod(). check if its state is canceled
$subscription->cancelled();
```

You can use the `subscribedToPlan` method to check if the user is subscribed to a given plan.

```php
if ($user->subscribedToPlan('monthly', 'default')) {
    //
}
```

#### Changing Plans

You can change current plan of a `Billable` model by using `swap` method as below. Before using this, it is recommended to look at [Prorating when Upgrading or Downgrading Subscription Plans](http://docs.fastspring.com/activity-events-orders-and-subscriptions/managing-active-subscriptions/prorating-when-upgrading-or-downgrading-subscription-plans).

```php
$user = App\User::find(1);

$user->subscription('default')->swap('provider-plan-id', $prorate, $quantity, $coupons);
```

The `swap` method communicates with Fastspring and updates the subscription model according to response. If you plan to swap plan without prorating, plan doesn't change immediately. In that case, future plan and swap date are saved to `swap_to` and `swap_at` columns. End of the current subscription period, Fastspring sends you webhook request about subscription change. That's why if you think to use prorating, remember to set webhooks right.

#### Subscription Trials

You can handle trial days of your plans at [Fastspring Dashboard](https://dashboard.fastspring.com).

#### Cancelling Subscriptions

To cancel a subscription, call the `cancel` method on the subscription model.

```php
$user->subscription('default')->cancel();
```

If you want to cancel a subscription immediately, you can use `cancelNow` method.

```php
$user->subscription('default')->cancelNow();
```

Both methods update the subscription model according to response of Fastspring. The `cancel` method saves cancellation time to the `swap_at` column.

#### Resuming Subscriptions

To resume a subscription, you can use `resume` method on the subscription model. The user must be still on grace period. Otherwise, this method throws a `LogicException`.

```php
$user->subscription('default')->resume();
```

This method updates the subscription model's `state` and set `swap_to`, `swap_at` columns `null` according to response.

#### Subscription Period

Cashier Fastspring package has also built-in subscription period model and related methods in order to help you to manage payment periods of subscription. This may help you to keep usage of particular resources of users between payment periods.

You can call `activePeriodOrCreate` method of `Subscription` model and retrieve current `SubscriptionPeriod` model which involves `id`, `start_date`, `end_date` information. If the current active period is not created yet, this method fetches it from Fastspring API and creates. If the subscription is a local subscription, it also creates a new period according to the last subscription period (if there is none, it assumes today is the start day of the subscription period).


```php
$activePeriod = $user->subscription('default')->activePeriodOrCreate();

// if you don't want to create an active subscription period immediately when no exist
// you can use activePeriod method as below
// you can set a cron job for creation of new periods
// or do it in your way
$activePeriod = $user->subscription('default')->activePeriod();
```

#### Updating Credit Cards

Fastspring does not provide any API to update credit card or any other payment information directly. You should redirect your customers to the their account management panel at Fastspring side. You can generate account management panel URL by using `accountManagementURI` method of the `Billable` model.

```php
$redirectURI = $user->accountManagementURI();
```

### Webhooks

Cashier Fastspring package provides an easy way to handle webhooks. It fires related events for each webhook request and provides request payload data as a parameter. It also handles message security if you set `FASTSPRING_HMAC_SECRET`. You can find sample listeners in `src/Listeners` folder.

Beside webhook specific events, there are also category and any events. For instance, if you want to listen all webhook requests, you can register your listener to `TwentyTwoDigital\CashierFastspring\Events\Any` event. Also, if you want to listen all subscription related webhook requests, you can use `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny` event.

You can see relation between package events and webhook requests at the table below.

| Webhook Request    | Fired Cashier Fastspring Events |
| ------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| account.created | `TwentyTwoDigital\CashierFastspring\Events\AccountCreated`, `TwentyTwoDigital\CashierFastspring\Events\AccountAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| fulfillment.failed | `TwentyTwoDigital\CashierFastspring\Events\FulfillmentFailed`, `TwentyTwoDigital\CashierFastspring\Events\FulfillmentAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| mailingListEntry.removed | `TwentyTwoDigital\CashierFastspring\Events\MailingListEntryRemoved`, `TwentyTwoDigital\CashierFastspring\Events\MailingListEntryAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| mailingListEntry.updated | `TwentyTwoDigital\CashierFastspring\Events\MailingListEntryUpdated`, `TwentyTwoDigital\CashierFastspring\Events\MailingListEntryAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| order.approval.pending | `TwentyTwoDigital\CashierFastspring\Events\OrderApprovalPending`, `TwentyTwoDigital\CashierFastspring\Events\OrderAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| order.canceled | `TwentyTwoDigital\CashierFastspring\Events\OrderCanceled`, `TwentyTwoDigital\CashierFastspring\Events\OrderAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| order.payment.pending | `TwentyTwoDigital\CashierFastspring\Events\OrderPaymentPending`, `TwentyTwoDigital\CashierFastspring\Events\OrderAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| order.completed | `TwentyTwoDigital\CashierFastspring\Events\OrderCompleted`, `TwentyTwoDigital\CashierFastspring\Events\OrderAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| order.failed | `TwentyTwoDigital\CashierFastspring\Events\OrderFailed`, `TwentyTwoDigital\CashierFastspring\Events\OrderAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| payoutEntry.created | `TwentyTwoDigital\CashierFastspring\Events\PayoutEntryCreated`, `TwentyTwoDigital\CashierFastspring\Events\PayoutEntryAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| return.created | `TwentyTwoDigital\CashierFastspring\Events\ReturnCreated`, `TwentyTwoDigital\CashierFastspring\Events\ReturnAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.activated | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionActivated`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.canceled | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionCanceled`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.charge.completed | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionChargeCompleted`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.charge.failed | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionChargeFailed`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.deactivated | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionDeactivated`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.payment.overdue | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionPaymentOverdue`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.payment.reminder | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionPaymentReminder`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.trial.reminder | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionTrialReminder`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |
| subscription.updated | `TwentyTwoDigital\CashierFastspring\Events\SubscriptionUpdated`, `TwentyTwoDigital\CashierFastspring\Events\SubscriptionAny`, `TwentyTwoDigital\CashierFastspring\Events\Any` |

To listen an event, you can register listeners in `app/providers/EventServiceProvider.php`.

```php
protected $listen = [
    // some others
    'TwentyTwoDigital\CashierFastspring\Events\SubscriptionCanceled' => [
        'Your\Lovely\Listener'
    ]
];
```

### Single Charges

Not implemented yet. If you need it you can contribute to the package. Please check [Contributing](#contributing).

### Invoices

In Fastspring, invoices are generated by Fastspring. You don't need to generate official or unofficial invoices. If you are using default webhook listeners, your invoices will be sync to your database. You can get invoices URL with the `Invoice` model or over the `Billable` trait.

## Contributing

Thank you for considering contributing to the Cashier Fastspring. You can read the contribution guide lines [here](contributing.md). You can also check [issues](https://github.com/22digital/laravel-cashier-fastspring/issues) to improve this package.

## Credits

Cashier Fastspring package is developed by Bilal Gultekin over Taylor Otwell's Cashier package. You can see all contributors [here][link-contributors].

## License

Cashier Fastspring is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

[hero]: /docs/images/hero-small.png
