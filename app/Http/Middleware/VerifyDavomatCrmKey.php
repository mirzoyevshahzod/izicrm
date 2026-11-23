<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * /api/davomat/* — faqat CRM: Authorization: Bearer DAVOMAT_CRM_API_KEY.
 */
class VerifyDavomatCrmKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('services.davomat.crm_api_key');

        if ($key === '' || ! hash_equals($key, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
