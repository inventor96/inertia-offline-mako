<?php

declare(strict_types=1);

namespace inventor96\InertiaOfflineMako;

use inventor96\InertiaOffline\AbstractOfflineRouteList;
use inventor96\InertiaOffline\ActionTypeEnum;
use inventor96\InertiaOffline\Contracts\PaginationUrlExpanderInterface;
use inventor96\InertiaOffline\OfflineCacheable;
use mako\http\routing\Route;
use mako\http\routing\Routes;
use mako\logger\Logger;
use mako\syringe\Container;

class OfflineRoutes extends AbstractOfflineRouteList {
    public function __construct(
        private readonly Routes $routes,
        private readonly Logger $logger,
        private readonly Container $container,
        private readonly PaginationUrlExpanderInterface $paginationUrlExpander,
    ) {}

    protected function getRoutes(): iterable {
        return $this->routes->getRoutes();
    }

    protected function getRoutePattern(mixed $route): string {
        /** @var Route $route */
        return $route->getRoute();
    }

    protected function getRouteAction(mixed $route): mixed {
        /** @var Route $route */
        return $route->getAction();
    }

    protected function invokeAction(mixed $action, array $parameters = []): mixed {
        // if not already an object (i.e. it's a class), instantiate the class
        if (ActionTypeEnum::from($action) === ActionTypeEnum::METHOD && !is_object($action[0])) {
            $action[0] = $this->container->get((string) $action[0]);
        }

        return $this->container->call($action, $parameters);
    }

    protected function expandPaginationUrls(
        string $baseUrl,
        mixed $pagination,
        mixed $route,
        OfflineCacheable $attribute,
        array $routeParams = [],
    ): array {
        return $this->paginationUrlExpander->expand($baseUrl, $pagination, $route, $attribute, $routeParams);
    }

    protected function logWarning(string $message): void {
        $this->logger->warning($message);
    }
}
