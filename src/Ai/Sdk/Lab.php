<?php

namespace LimenAi\Ai\Sdk;

enum Lab: string
{
    case OpenAi = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';
    case OpenRouter = 'openrouter';
    case Cohere = 'cohere';
    case Jina = 'jina';
    case Bedrock = 'bedrock';
    case ElevenLabs = 'elevenlabs';
    case Groq = 'groq';
    case Xai = 'xai';
    case Voyage = 'voyage';
    case Fake = 'fake';
    case OpenAiCompatible = 'openai-compatible';
}
