<?php

/*
 * This file is part of askvortsov/flarum-pwa
 *
 *  Copyright (c) 2021 Alexander Skvortsov.
 *
 *  For detailed copyright and license information, please view the
 *  LICENSE file that was distributed with this source code.
 */

namespace Askvortsov\FlarumPWA\Api\Controller;

use Askvortsov\FlarumPWA\PWATrait;
use Askvortsov\FlarumPWA\Util;
use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @TODO: Remove this in favor of one of the API resource classes that were added.
 *      Or extend an existing API Resource to add this to.
 *      Or use a vanilla RequestHandlerInterface controller.
 *      @link https://docs.flarum.org/2.x/extend/api#endpoints
 */
class DeleteLogoController extends AbstractDeleteController
{
    use PWATrait;

    protected Filesystem $uploadDir;

    public function __construct(protected SettingsRepositoryInterface $settings, Factory $filesystemFactory)
    {
        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
    }

    /**
     * {@inheritdoc}
     * @throws PermissionDeniedException|RouteNotFoundException
     */
    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $size = Arr::get($request->getQueryParams(), 'size');

        if (! in_array($size, Util::$ICON_SIZES)) {
            throw new RouteNotFoundException();
        }

        $pathKey = "askvortsov-pwa.icon_{$size}_path";
        $path = $this->settings->get($pathKey);

        $this->uploadDir->delete($path);

        $this->settings->set($pathKey, null);

        return new EmptyResponse(204);
    }
}
