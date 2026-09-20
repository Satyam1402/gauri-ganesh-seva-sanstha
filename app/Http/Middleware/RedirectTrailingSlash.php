<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "/about/" and "/about" would otherwise both render, giving search
 * engines two URLs for one page. GET requests with a trailing slash are
 * permanently redirected to the canonical slash-less form.
 */
class RedirectTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if ($request->isMethod('GET') && $path !== '/' && str_ends_with($path, '/')) {
            $url = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl().rtrim($path, '/'), '/');

            if ($query = $request->getQueryString()) {
                $url .= '?'.$query;
            }

            return redirect()->to($url, 301);
        }

        return $next($request);
    }
}
