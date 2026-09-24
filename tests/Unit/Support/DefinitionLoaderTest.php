<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Registry\LimenAiRegistry;
use LimenAi\Support\ConfigFragmentWriter;
use LimenAi\Support\DefinitionLoader;
use LimenAi\Tests\TestCase;

class DefinitionLoaderTest extends TestCase
{
    public function test_it_merges_config_registry_and_fragment_files(): void
    {
        if (! function_exists('config_path')) {
            $this->markTestSkipped('config_path helper unavailable.');
        }

        app(ConfigFragmentWriter::class)->write('tools', 'runtime_echo', [
            'name' => 'Runtime Echo',
            'class' => \LimenAi\Tests\Stubs\EchoTool::class,
            'input_schema' => ['message' => ['type' => 'string', 'required' => true]],
        ]);

        app(LimenAiRegistry::class)->tool('registry_echo', \LimenAi\Tests\Stubs\EchoTool::class);

        $tools = app(DefinitionLoader::class)->tools();

        $this->assertArrayHasKey('example_echo', $tools);
        $this->assertArrayHasKey('runtime_echo', $tools);
        $this->assertArrayHasKey('registry_echo', $tools);
    }
}
