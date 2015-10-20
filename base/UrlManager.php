<?php
namespace base;

/**
 * UrlManager handles HTTP request parsing and creation of URLs based on a set of rules.
 */
class UrlManager
{
    /**
     * Parses the user request.
     * @param Request $request the request component
     * @return array|boolean the route and the associated parameters.
     * False is returned if the current request cannot be successfully parsed.
     */
    public function parseRequest($request)
    {
        $route = $request->getQueryParam('r', '');
        if (is_array($route)) {
            $route = '';
        }
        return [(string) $route, []];
    }
}