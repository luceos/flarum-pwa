<?php

/*
 * This file is part of askvortsov/flarum-pwa
 *
 *  Copyright (c) 2021 Alexander Skvortsov.
 *
 *  For detailed copyright and license information, please view the
 *  LICENSE file that was distributed with this source code.
 */

namespace Askvortsov\FlarumPWA;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Container\Container;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\AuthenticationError;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;

class FirebasePushSender
{
    public function __construct(protected Container $container, protected NotificationBuilder $notifications, protected LoggerInterface $logger, protected SettingsRepositoryInterface $settings)
    {
    }

    public function notify(BlueprintInterface $blueprint, array $userIds = []): void
    {
        if (! $this->hasValidFirebaseSettings()) {
            return;
        }

        try {
            // We're using the container to resolve the FirebaseMessagingContract here so we have more
            // control on when and where to log the error. Having it passed on the constructor will mean
            // we'll have to throw an exception and log the error for the user in the exception handler
            // rather than directly in the class that consumes the contract.

            /** @throws FirebaseConfigInvalid */
            $messaging = $this->container->make(Messaging::class);
        } catch (FirebaseConfigInvalid) {
            $this->logger->error('Firebase config invalid');

            return;
        }

        FirebasePushSubscription::whereIn('user_id', $userIds)->each(function (FirebasePushSubscription $subscription) use ($messaging, $blueprint) {
            try {
                $messaging->send($this->newFirebaseCloudMessage($subscription, $blueprint));
            } catch (AuthenticationError $e) {
                $this->logger->error($e->getMessage());
            } catch (NotFound) {
                $subscription->delete();
            }
        });
    }

    private function newFirebaseCloudMessage(FirebasePushSubscription $subscription, BlueprintInterface $blueprint): CloudMessage
    {
        $message = $this->notifications->build($blueprint);

        return CloudMessage::new()
            ->withTarget('token', $subscription->token)
            ->withNotification(
                Notification::fromArray([
                    'title' => $message->title(),
                    'body' => strip_tags($message->body()),
                ])
            );
    }

    private function hasValidFirebaseSettings(): bool
    {
        $config = $this->settings->get('askvortsov-pwa.firebaseConfig');

        if (! $config) {
            return false;
        }

        json_decode($config);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
