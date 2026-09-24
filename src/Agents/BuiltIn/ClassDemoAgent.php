<?php

namespace LimenAi\Agents\BuiltIn;

use LimenAi\Ai\Attributes\UsesModel;
use LimenAi\Ai\Attributes\UsesProvider;
use LimenAi\Ai\Contracts\Agent;
use LimenAi\Ai\Contracts\HasTools;
use LimenAi\Ai\Promptable;
use LimenAi\Tools\BuiltIn\ExampleEchoTool;

#[UsesModel('gpt-4.1-mini')]
#[UsesProvider('fake')]
final class ClassDemoAgent implements Agent, HasTools
{
    use Promptable;

    public function key(): string
    {
        return 'class_demo';
    }

    public function instructions(): string
    {
        return 'You are a class-based demo agent. Use the echo tool when asked.';
    }

    public function tools(): iterable
    {
        return [
            ExampleEchoTool::class,
        ];
    }
}
