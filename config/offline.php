<?php

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