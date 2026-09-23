<?php

namespace LimenAi\Http\Concerns;

use Illuminate\Http\Request;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Runtime\RunContextData;
use LimenAi\Support\ResponseLanguageResolver;

trait BuildsRunContext
{
    protected function runContextFromRequest(
        Request $request,
        AuthorizationService $authorization,
        array $metadata = [],
        ?string $guestToken = null,
    ): RunContextData {
        $languages = app(ResponseLanguageResolver::class);
        $metadataLocale = isset($metadata['preferred_language']) && is_string($metadata['preferred_language'])
            ? $metadata['preferred_language']
            : null;

        $locale = $languages->resolve(
            $request->header('X-Limen-Locale') ?: $request->input('locale') ?: $request->input('language'),
            null,
            $metadataLocale,
            $request->getPreferredLanguage() ?? app()->getLocale(),
        );

        return RunContextData::make([
            'user_id' => $authorization->currentUserId(),
            'guest_token' => $guestToken ?? $request->header('X-Limen-Guest-Token'),
            'metadata' => $metadata,
            'locale' => $locale,
        ]);
    }
}
