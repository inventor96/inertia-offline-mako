<?php

declare(strict_types=1);

namespace inventor96\InertiaOfflineMako;

use Closure;
use mako\config\Config;
use mako\http\Request;
use mako\http\Response;
use mako\http\response\builders\JSON;
use mako\http\response\builders\ResponseBuilderInterface;
use mako\http\response\senders\ResponseSenderInterface;
use mako\http\routing\middleware\MiddlewareInterface;

class ConditionalEtag implements MiddlewareInterface {
    public function __construct(private readonly Config $config) {}

    public function execute(Request $request, Response $response, Closure $next): Response {
        // run the request and get the response
        $response = $next($request, $response);

        // only GET requests
        if ($request->getMethod() !== 'GET') {
            return $response;
        }

        // if the body is JSON and it's an offline routes request, build it first
        $body = $response->getBody();
        if ($body instanceof JSON && $this->isOfflineRoutesRequest($request)) {
            $body->build($request, $response);
            $body = $response->getBody();
        }

        // check if we should apply an ETag
        if (!$this->shouldApplyEtag($request, $response, $body)) {
            return $response;
        }

        // generate an ETag based on the response body content and add it to the response headers
        $etag = '"' . hash('sha256', (string) $body) . '"';
        $response->headers->add('ETag', $etag);

        // return a 304 if there's no change
        $requestEtag = trim((string) $request->headers->get('If-None-Match', ''));
        if ($requestEtag !== '' && str_replace('-gzip', '', $requestEtag) === $etag) {
            $response->setStatus(304);
            $response->setBody('');
            $response->headers->remove('Content-Length');
        }

        // return the response with the ETag header added (if applicable)
        return $response;
    }

    /**
     * Determine if we should apply an ETag to this response. We only want to
     * apply ETags to successful responses with cacheable body content, and we
     * want to avoid applying ETags to streaming responses or other types that
     * may not be intended for caching.
     *
     * @param Request $request
     * @param Response $response
     * @param mixed $body
     * @return boolean
     */
    private function shouldApplyEtag(Request $request, Response $response, mixed $body): bool {
        // skip if not a successful response
        if ($response->getStatus()->value !== 200) {
            return false;
        }

        // skip if body is a stream or other non-cacheable type (e.g. file download)
        if ($body instanceof ResponseSenderInterface || $body instanceof ResponseBuilderInterface) {
            return false;
        }

        // skip if body is not a string or cannot be cast to a string
        if (!is_scalar($body) && !(is_object($body) && method_exists($body, '__toString'))) {
            return false;
        }

        // only inertia or offline routes JSON responses
        return $this->isInertiaJsonResponse($request, $response)
            || $this->isOfflineRoutesResponse($request, $response);
    }

    /**
     * Determine if the response is an Inertia JSON response, which should be
     * treated as cacheable for ETag purposes. We check for the Inertia header
     * and JSON content type to avoid applying ETags to non-Inertia JSON responses
     * (e.g. API endpoints) that may not be intended for caching.
     *
     * @param Request $request
     * @param Response $response
     * @return boolean
     */
    private function isInertiaJsonResponse(Request $request, Response $response): bool {
        return $request->headers->get('X-Inertia')
            && $response->getType() === 'application/json'
            && $response->headers->hasValue('X-Inertia', 'true', false);
    }

    /**
     * Determine if the response is from the offline routes endpoint, which
     * should be treated as a cacheable JSON response for ETag purposes.
     *
     * @param Request $request
     * @param Response $response
     * @return boolean
     */
    private function isOfflineRoutesResponse(Request $request, Response $response): bool {
        return $this->isOfflineRoutesRequest($request)
            && $response->getType() === 'application/json';
    }

    /**
     * Determine if the request is for the offline routes endpoint, which
     * should be treated as a cacheable JSON response for ETag purposes. We
     * check the path here instead of relying on a specific controller or
     * response type since the endpoint can be customized and may not have a
     * unique signature in the controller layer.
     *
     * @param Request $request
     * @return boolean
     */
    private function isOfflineRoutesRequest(Request $request): bool {
        $path = $this->config->get('inertia-offline::offline.routes_path', '/pwa/offline-routes');
        return $request->getPath() === $path;
    }
}
