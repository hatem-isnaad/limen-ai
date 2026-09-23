<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Authorization\GuestSessionService;

class GuestSessionController
{
    public function __construct(
        private readonly GuestSessionService $guestSessions,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile' => ['required', 'array'],
        ]);

        $session = $this->guestSessions->register($validated['profile']);

        return response()->json($session, 201);
    }
}
