<?php

namespace App\Tests\FormatOutput;

use App\FormatOutput\FormatOutputFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormatOutputFactory::class)]
class FormatOutputFactoryTest extends TestCase
{

    #[DataProvider('contentTypesData')]
    public function testFormatFromContentTypes(array $contentTypes, string $expected): void
    {
        self::assertSame($expected, FormatOutputFactory::formatFromContentTypes($contentTypes));
    }

    public static function contentTypesData(): \Generator
    {
        yield 'no Accept header' => [[], 'html'];
        yield 'wildcard' => [['*/*'], 'html'];
        yield 'markdown' => [['text/markdown'], 'markdown'];
        yield 'json' => [['application/json'], 'json'];
        yield 'browser' => [['text/html', 'application/xhtml+xml', 'application/xml', '*/*'], 'html'];
        yield 'markdown preferred over wildcard' => [['text/markdown', '*/*'], 'markdown'];
        yield 'first supported type wins' => [['image/png', 'application/json', 'text/markdown'], 'json'];
    }

}
