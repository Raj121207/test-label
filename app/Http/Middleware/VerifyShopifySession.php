<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
class VerifyShopifySession
{
    public function handle(Request $request, Closure $next)
    {
        $url = $request->getRequestUri();
        $parsedPath = parse_url($url, PHP_URL_PATH);
        $cleanPath = ltrim($parsedPath, '/');
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)(\?.*)?$/', $url)) {

            return Response::file($cleanPath);
        }
        return $next($request);
    }
}