<?php

/**
 * Example agents — one per built-in LLM provider.
 * Merged into limen-ai.agents; each pins provider and reads model from .env.
 */
if (! function_exists('limen_ai_provider_example_agent')) {
    function limen_ai_provider_example_agent(
        string $displayName,
        string $provider,
        string $modelEnv,
        string $defaultModel,
    ): array {
        return [
            'name' => $displayName,
            'description' => "Example agent using the {$provider} LLM provider.",
            'model' => env($modelEnv, $defaultModel),
            'provider' => $provider,
            'instructions' => 'You are a helpful assistant. Use the example_echo tool when asked to echo text.',
            'persona' => [
                'display_name' => $displayName,
                'tone' => 'friendly',
                'language' => 'en',
                'response_style' => 'concise',
                'rules' => [
                    'Identify your provider when asked.',
                    'Use tools only when they add value.',
                ],
            ],
            'skills' => ['general_assistance'],
            'tools' => ['example_echo'],
            'knowledge' => ['getting_started'],
            'memory' => [
                'conversation' => true,
                'user' => false,
                'limit' => 10,
                'allowed_keys' => ['preferred_language', 'timezone'],
                'max_value_length' => 256,
            ],
            'authorization' => [
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ],
            'output' => [
                'format' => 'text',
                'max_response_chars' => 4000,
            ],
            'limits' => [
                'max_tool_calls' => 10,
                'max_steps' => 20,
                'timeout' => 60,
                'temperature' => 0.2,
                'max_tokens' => 1200,
                'max_history_messages' => 30,
            ],
            'version' => '1.0.0',
        ];
    }
}

return [
    'example_openai' => limen_ai_provider_example_agent(
        'OpenAI Example',
        'openai',
        'LIMEN_AI_OPENAI_MODEL',
        'gpt-4.1-mini',
    ),
    'example_anthropic' => limen_ai_provider_example_agent(
        'Anthropic Example',
        'anthropic',
        'LIMEN_AI_ANTHROPIC_MODEL',
        'claude-sonnet-4-20250514',
    ),
    'example_gemini' => limen_ai_provider_example_agent(
        'Gemini Example',
        'gemini',
        'LIMEN_AI_GEMINI_MODEL',
        'gemini-2.0-flash',
    ),
    'example_openrouter' => limen_ai_provider_example_agent(
        'OpenRouter Example',
        'openrouter',
        'LIMEN_AI_OPENROUTER_MODEL',
        'anthropic/claude-3.5-sonnet',
    ),
];
