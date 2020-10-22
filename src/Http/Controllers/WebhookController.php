<?php
/**
 * This file implements Webhook Controller.
 *
 * @author    Bilal Gultekin <bilal@gultekin.me>
 * @author    Justin Hartman <justin@22digital.co.za>
 * @copyright 2019 22 Digital
 * @license   MIT
 * @since     v0.1
 */

namespace TwentyTwoDigital\CashierFastspring\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Log;
use Symfony\Component\HttpFoundation\Response;
use TwentyTwoDigital\CashierFastspring\Events;
use TwentyTwoDigital\CashierFastspring\Fastspring\Fastspring;

/**
 * Controls the data flow into a webhook object and updates the view
 * whenever data changes.
 *
 * {@inheritdoc}
 */
class WebhookController extends Controller
{
    /**
     * Handle a Fastspring webhook call.
     *
     * @param \Illuminate\Http\Request $request The webhook requet
     *
     * @throws Exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        // keep id of successfully managed events
        $successfulEvents = [];

        $hmacSecret = array_key_exists('FASTSPRING_HMAC_SECRET', $_ENV)
            ? $_ENV['FASTSPRING_HMAC_SECRET']
            : config('services.fastspring.hmac_secret');

        // we try to be sure about
        // message integrity and authentication of message
        if ($hmacSecret) {
            $signature = $request->header('X-FS-Signature');

            // generate signature to check
            $generatedSignature = base64_encode(hash_hmac('sha256', $request->getContent(), $hmacSecret, true));

            // check if equals
            if ($signature != $generatedSignature) {
                // if not that means
                // someone trying to fool you
                // or you misconfigured your settings
                throw new Exception('Message security violation, MAC is wrong!');
            }
        }

        // iterate and trigger events
        foreach ($payload['events'] as $event) {
            // prepare category event class names like OrderAny
            $explodedType = explode('.', $event['type']);
            $category = array_shift($explodedType);
            $categoryEvent = '\TwentyTwoDigital\CashierFastspring\Events\\' . Str::studly($category) . 'Any';

            // prepare category event class names like activity
            $activity = str_replace('.', ' ', $event['type']);
            $activityEvent = '\TwentyTwoDigital\CashierFastspring\Events\\' . Str::studly($activity);

            // there may be some exceptions on events
            // so if anything goes bad its ID won't be added on the successfullEvents
            // and these events won't be marked as processed on fastspring side
            // so that will make events more manageable
            try {
                // check if the related event classes are exist
                // there may be not handled events
                if (!class_exists($categoryEvent) || !class_exists($activityEvent)) {
                    throw new Exception('There is no event for ' . $event['type']);
                }

                // trigger events
                Event::dispatch(
                    new Events\Any(
                        $event['id'],
                        $event['type'],
                        $event['live'],
                        $event['processed'],
                        $event['created'],
                        $event['data']
                    )
                );

                Event::dispatch(
                    new $categoryEvent(
                        $event['id'],
                        $event['type'],
                        $event['live'],
                        $event['processed'],
                        $event['created'],
                        $event['data']
                    )
                );

                Event::dispatch(
                    new $activityEvent(
                        $event['id'],
                        $event['type'],
                        $event['live'],
                        $event['processed'],
                        $event['created'],
                        $event['data']
                    )
                );

                // add event id to successful events
                $successfulEvents[] = $event['id'];
            } catch (Exception $e) {
                // log the exception
                // and continue to iterate

                Log::error($e);
            }
        }

        // return successful ids of events to mark them processed
        return new Response(implode("\n", $successfulEvents), 202);
    }
}
