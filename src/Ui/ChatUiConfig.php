<?php

namespace LimenAi\Ui;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class ChatUiConfig
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $ui = $this->config->get('limen-ai.ui', []);

        return [
            'sounds' => $ui['sounds'] ?? [],
            'animations' => $ui['animations'] ?? [],
            'widget' => $ui['widget'] ?? [],
            'composer' => $ui['composer'] ?? [],
            'messages' => $ui['messages'] ?? [],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
