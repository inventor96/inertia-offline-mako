<?php
namespace inventor96\InertiaOfflineMako;

use inventor96\InertiaOffline\Contracts\PaginationUrlExpanderInterface;
use inventor96\InertiaOffline\QueryPaginationUrlExpander;
use mako\application\Package;
use mako\config\Config;
use mako\http\routing\Routes;

class InertiaOfflinePackage extends Package
{
	protected string $packageName = 'inventor96/inertia-offline-mako';
	protected string $fileNamespace = 'inertia-offline';

	function bootstrap(): void
	{
		$this->registerPaginationUrlExpander();
		$this->registerRoutes();
	}

	protected function registerPaginationUrlExpander(): void
	{
		/** @var Config $config */
		$config = $this->container->get(Config::class);

		// get the pagination url expander from config
		$expander = $config->get($this->fileNamespace . '::offline.pagination_expander');

		// default to QueryPaginationUrlExpander with the page key from config
		if (empty($expander)) {
			$expander = fn () => new QueryPaginationUrlExpander((string) $config->get('pagination.page_key', 'page'));
		}

		// register the pagination url expander
		$this->container->register(PaginationUrlExpanderInterface::class, $expander);
	}

	protected function registerRoutes(): void
	{
		/** @var Config $config */
		$config = $this->container->get(Config::class);
		/** @var Routes $routes */
		$routes = $this->container->get(Routes::class);

		// get configured paths
		$offlineRoutesPath = $config->get($this->fileNamespace . '::offline.routes_path', '/pwa/offline-routes');
		$offlineVersionPath = $config->get($this->fileNamespace . '::offline.version_path', '/pwa/offline-version');

		// ensure valid paths
		if (
			!empty($offlineRoutesPath)
			&& (
				!is_string($offlineRoutesPath)
				|| str_starts_with($offlineRoutesPath, '/')
			)
		) {
			throw new \InvalidArgumentException('Invalid offline routes path: ' . $offlineRoutesPath);
		}
		if (
			!empty($offlineVersionPath)
			&& (
				!is_string($offlineVersionPath)
				|| str_starts_with($offlineVersionPath, '/')
			)
		) {
			throw new \InvalidArgumentException('Invalid offline version path: ' . $offlineVersionPath);
		}

		// register the routes
		if (!empty($offlineRoutesPath) || !is_string($offlineRoutesPath)) {
			$routes->get($offlineRoutesPath, [OfflinePwaController::class, 'offlineRoutes'], 'pwa:offlineRoutes');
		}
		if (!empty($offlineVersionPath) || !is_string($offlineVersionPath)) {
			$routes->get($offlineVersionPath, [OfflinePwaController::class, 'version'], 'pwa:version');
		}
	}
}