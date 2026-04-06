<?php

declare(strict_types=1);

namespace inventor96\InertiaOfflineMako;

use inventor96\Inertia\Inertia;
use mako\http\routing\Controller;

class OfflinePwaController extends Controller {
    public function offlineRoutes(OfflineRoutes $offlineRoutes): mixed {
        // set the appropriate cache control headers to prevent caching of the route list response
        $this->response->headers->add('Cache-Control', 'no-store, must-revalidate, private', true);

        // get the TTL for caching the route list response
        $ttl = (int) $this->config->get('inertia-offline::offline.route_list_cache_ttl', 86400);

        // return the route list
        return $this->jsonResponse([
            'ttl' => $ttl,
            'routes' => $offlineRoutes->generateRoutes(),
        ]);
    }

    public function version(Inertia $inertia): mixed {
        // set the appropriate cache control headers to prevent caching of the route list response
        $this->response->headers->add('Cache-Control', 'no-store, must-revalidate, private', true);

        // return the Inertia version
        return $this->jsonResponse([
            'version' => $inertia->getVersion(),
        ]);
    }
}
