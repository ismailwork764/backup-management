<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return response()->json(['message' => 'API token missing'], 401);
        }

        $agent = $this->resolveAgentByToken($token);

        if (!$agent || !$agent->is_active) {
            return response()->json(['message' => 'Invalid or inactive API token'], 401);
        }

        $request->attributes->set('agent', $agent);

        return $next($request);
    }


    private function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return trim(str_replace('Bearer', '', $header));
    }

    private function resolveAgentByToken(string $token): ?Agent
    {
        $agent = Agent::where('api_token', $token)->where('is_active', true)->first();
        if ($agent) {
            return $agent;
        }

        return null;
    }
}
