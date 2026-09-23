<?php

namespace LimenAi\Http\Concerns;

use Illuminate\Http\Request;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Runtime\RunContextData;

trait BuildsRunContext
{
    protected function runContextFromRequest(Request $request, AuthorizationService $authorization): RunContextData
    {
        return RunContextData::make([
            'user_id' => $authorization->currentUserId(),
            'guest_token' => $request->header('X-Limen-Guest-Token'),
            'metadata' => [],
            'locale' => $request->getPreferredLanguage() ?? app()->getLocale(),
        ]);
    }
}
