<?php

namespace LimenAi\Tests\Unit\Providers;

use LimenAi\Providers\Fake\FakeEmbeddingProvider;
use LimenAi\Tests\TestCase;

class FakeEmbeddingProviderTest extends TestCase
{
    public function test_it_returns_deterministic_embeddings(): void
    {
        $provider = new FakeEmbeddingProvider;

        $first = $provider->embed(['hello world']);
        $second = $provider->embed(['hello world']);
        $different = $provider->embed(['other text']);

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $different);
        $this->assertCount(8, $first[0]);
    }
}
