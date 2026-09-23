<?php

namespace LimenAi\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAgentRequests
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldThrottle()) {
            return $next($request);
        }

        $maxAttempts = max(1, (int) $this->config->get('limen-ai.ui.rate_limit.max_attempts', 60));
        $decayMinutes = max(1, (int) $this->config->get('limen-ai.ui.rate_limit.decay_minutes', 1));
        $conversationId = (string) $request->route('conversationId', '');
        $key = sha1($request->ip().'|'.$conversationId.'|'.$request->path());

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many Limen AI requests. Try again in '.$seconds.' seconds.',
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }

    protected function shouldThrottle(): bool
    {
        if ((bool) $this->config->get('limen-ai.ui.rate_limit.enabled', false)) {
            return true;
        }

        return (bool) $this->config->get('limen-ai.ui.guest.enabled', false);
    }
}
