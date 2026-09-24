<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LimenAi\Agents\AgentDefinitionStore;

/**
 * REST API for custom admin UIs (no bundled management UI).
 */
class AgentDefinitionController
{
    public function __construct(
        private readonly AgentDefinitionStore $store,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        abort_unless($this->store->isAvailable(), 503, 'Database agent storage is not enabled.');

        return response()->json([
            'data' => array_map(
                fn ($model): array => $this->transform($model),
                $this->store->all(),
            ),
        ]);
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $this->authorizeAdmin($request);

        abort_unless($this->store->isAvailable(), 503, 'Database agent storage is not enabled.');

        $model = $this->store->find($key);

        abort_if($model === null, 404, 'Agent definition not found.');

        return response()->json(['data' => $this->transform($model)]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        abort_unless($this->store->isAvailable(), 503, 'Database agent storage is not enabled.');

        $validated = $this->validated($request);
        $key = (string) $validated['key'];

        abort_if($this->store->find($key) !== null, 422, 'Agent key already exists.');

        $model = $this->store->upsert($key, $validated);

        return response()->json(['data' => $this->transform($model)], 201);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $this->authorizeAdmin($request);

        abort_unless($this->store->isAvailable(), 503, 'Database agent storage is not enabled.');

        abort_if($this->store->find($key) === null, 404, 'Agent definition not found.');

        $validated = $this->validated($request, false);
        unset($validated['key']);

        $model = $this->store->upsert($key, $validated);
        $model = $this->store->bumpVersion($key) ?? $model;

        return response()->json(['data' => $this->transform($model)]);
    }

    public function destroy(Request $request, string $key): JsonResponse
    {
        $this->authorizeAdmin($request);

        abort_unless($this->store->isAvailable(), 503, 'Database agent storage is not enabled.');

        abort_unless($this->store->delete($key), 404, 'Agent definition not found.');

        return response()->json([], 204);
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, bool $requireKey = true): array
    {
        $rules = [
            'name' => [$requireKey ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'model' => [$requireKey ? 'required' : 'sometimes', 'string', 'max:255'],
            'provider' => [$requireKey ? 'required' : 'sometimes', 'string', 'max:64'],
            'instructions' => [$requireKey ? 'required' : 'sometimes', 'string'],
            'skills' => ['sometimes', 'array'],
            'tools' => ['sometimes', 'array'],
            'knowledge' => ['sometimes', 'array'],
            'memory' => ['sometimes', 'array'],
            'authorization' => ['sometimes', 'array'],
            'output' => ['sometimes', 'array'],
            'limits' => ['sometimes', 'array'],
            'agent_class' => ['nullable', 'string', 'max:255'],
            'enabled' => ['sometimes', 'boolean'],
            'version' => ['sometimes', 'string', 'max:32'],
        ];

        if ($requireKey) {
            $rules['key'] = ['required', 'string', 'max:64', 'regex:/^[a-z0-9_\\-]+$/'];
        }

        return $request->validate($rules);
    }

    protected function authorizeAdmin(Request $request): void
    {
        $ability = (string) config('limen-ai.api.admin_ability', 'manageLimenAiAgents');

        $user = $request->user();

        if ($ability !== '' && $user !== null) {
            abort_unless(Gate::forUser($user)->allows($ability), 403, 'Agent management is not allowed.');
        }
    }

    /** @return array<string, mixed> */
    protected function transform(mixed $model): array
    {
        return [
            'key' => $model->key,
            'name' => $model->name,
            'description' => $model->description,
            'model' => $model->model,
            'provider' => $model->provider,
            'instructions' => $model->instructions,
            'skills' => $model->skills ?? [],
            'tools' => $model->tools ?? [],
            'knowledge' => $model->knowledge ?? [],
            'memory' => $model->memory ?? [],
            'authorization' => $model->authorization ?? [],
            'output' => $model->output ?? [],
            'limits' => $model->limits ?? [],
            'agent_class' => $model->agent_class,
            'enabled' => (bool) $model->enabled,
            'version' => $model->version,
            'updated_at' => optional($model->updated_at)?->toIso8601String(),
        ];
    }
}
