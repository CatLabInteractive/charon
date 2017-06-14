<?php

namespace CatLab\Charon\Laravel\Middleware;

use CatLab\Charon\Laravel\Models\ResourceResponse;
use Closure;

class ResourceToOutput
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof ResourceResponse) {
            $response = \Response::json($response->getResource()->toArray());
        }

        return $response;
    }
}