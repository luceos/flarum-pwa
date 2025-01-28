<?php

/*
 * This file is part of askvortsov/flarum-pwa
 *
 *  Copyright (c) 2021 Alexander Skvortsov.
 *
 *  For detailed copyright and license information, please view the
 *  LICENSE file that was distributed with this source code.
 */

namespace Askvortsov\FlarumPWA\Api\Serializer;

use Askvortsov\FlarumPWA\PushSubscription;
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use InvalidArgumentException;
use Tobscure\JsonApi\Relationship;

/**
 * @TODO: Remove this in favor of one of the API resource classes that were added.
 *      Or extend an existing API Resource to add this to.
 *      Or use a vanilla RequestHandlerInterface controller.
 *      @link https://docs.flarum.org/2.x/extend/api#endpoints
 */
class PushSubscriptionSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'push_subscriptions';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($subscription): array
    {
        if (! ($subscription instanceof PushSubscription)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.PushSubscription::class
            );
        }

        return [
            'endpoint' => $subscription->endpoint,
            'vapidPublicKey' => $subscription->vapid_public_key,
            'expiresAt' => $this->formatDate($subscription->expires_at),
        ];
    }

    protected function user($subscription): Relationship
    {
        return $this->hasOne($subscription, BasicUserSerializer::class);
    }
}
