# Inertia Offline for Mako

A Mako framework adapter for [inertia-offline](https://github.com/inventor96/inertia-offline), enabling offline-first capabilities for Inertia.js applications.

## Installation

### 1. Install via Composer

```bash
composer require inventor96/inertia-offline-mako
```

### 2. Register the Package in Mako

Add the package to your `app/config/application.php`:

```php
'packages' => [
    'web' => [
        inventor96\InertiaOfflineMako\InertiaOfflinePackage::class,
    ],
],
```

## Usage

See [inertia-offline](https://github.com/inventor96/inertia-offline-php?tab=readme-ov-file#1-offlinecacheable-attribute) for usage instructions on how to mark routes as offline-cacheable and configure the client-side service worker.

## Configuration

The default configuration for the package works out of the box, but you can create an override file at `app/config/packages/inertia-offline/offline.php` to customize settings:

```php
return [
	/**
	 * The class that implements `PaginationUrlExpanderInterface` to use for
	 * expanding pagination URLs in the `OfflineRoutes` adapter. When left null
	 * or empty, the default is to use an adapter that relies on the built-in
	 * pagination of Mako.
	 */
	'pagination_expander' => null,

	/**
	 * The time (in seconds) that the offline route list response should be
	 * cached by clients and intermediate caches. Default is 86400 (24 hours).
	 */
	'route_list_cache_ttl' => 86400,

	/**
	 * The path for the route that serves the list of offline routes. Must be
	 * the same as `routeMetaPath` in the client config. Set to null or empty
	 * to disable automatic registration of the route (e.g. you want to
	 * register it manually). Default is `/pwa/offline-routes`.
	 */
	'routes_path' => '/pwa/offline-routes',

	/**
	 * The path for the route that serves the Inertia version. Must be the same
	 * as `routeVersionPath` in the client config. Set to null or empty to
	 * disable automatic registration of the route (e.g. you want to register
	 * it manually). Default is `/pwa/offline-version`.
	 */
	'version_path' => '/pwa/offline-version',
];
```

## Etag Middleware

In case you don't have Etag support in your application, you can use the `ConditionalEtag` middleware provided by the package. This middleware generates ETags for responses related to offline functionality. Enabling ETag support is optional, but is highly recommended for better caching and performance for the client-side service worker.

### Register Globally

As the middleware automatically avoids adding ETags to non-offline responses, you can safely register it globally. Add the middleware to your `app/http/routing/middleware.php` in the global middleware list, setting a high priority to ensure it runs last:

```php
$dispatcher
    ->registerGlobalMiddleware(\inventor96\InertiaOfflineMako\ConditionalEtag::class)
    ->setMiddlewarePriority(\inventor96\InertiaOfflineMako\ConditionalEtag::class, 1000);
```

## Custom Pagination URL Expander

By default, the package uses Mako's built-in pagination. If your application uses custom pagination patterns, implement a custom expander.

### Example Implementation

Create a class that implements `PaginationUrlExpanderInterface`:

```php
<?php

namespace app\adapters;

use inventor96\InertiaOffline\Contracts\PaginationUrlExpanderInterface;
use inventor96\InertiaOffline\OfflineCacheable;

class CustomPaginationUrlExpander implements PaginationUrlExpanderInterface
{
    public function expand(
        string $baseUrl,
        mixed $pagination, // from the OfflineCacheable::$pagination attribute
        mixed $route,
        OfflineCacheable $attribute,
        array $routeParams = []
    ): array {
        $urls = [];
        
        // Extract total pages or items from your pagination object
        $totalPages = $pagination->pages() ?? 1;
        
        // Generate URLs for each page
        for ($page = 1; $page <= $totalPages; $page++) {
            $urls[] = $baseUrl . '?page=' . $page;
        }
        
        return $urls;
    }
}
```

### Register in Config

Update `app/config/packages/inertia-offline/offline.php` to use your custom expander:

```php
'pagination_expander' => \app\adapters\CustomPaginationUrlExpander::class,
```

The package will automatically instantiate your expander through the Mako framework container and use it when generating offline route lists.
