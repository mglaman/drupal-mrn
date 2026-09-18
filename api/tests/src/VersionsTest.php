<?php

namespace App\Tests;

use App\Versions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Keep these cases in sync with app/tests/versions.test.js.
 */
#[CoversClass(Versions::class)]
class VersionsTest extends TestCase
{

    #[DataProvider('previousVersionData')]
    public function testFindPrevious(string $version, array $tags, ?string $expected): void
    {
        self::assertSame($expected, Versions::findPrevious($version, $tags));
    }

    public static function previousVersionData(): \Generator
    {
        yield 'no tags' => ['1.0.1', [], null];
        yield 'unknown version' => ['9.9.9', ['1.0.0', '1.0.1'], null];
        yield 'oldest tag' => ['1.0.0', ['1.0.0', '1.0.1'], null];
        yield 'previous patch' => ['1.0.2', ['1.0.0', '1.0.1', '1.0.2'], '1.0.1'];
        yield 'numeric sort' => ['1.10.0', ['1.2.0', '1.9.0', '1.10.0'], '1.9.0'];
        yield 'across minor versions' => ['1.1.0', ['1.0.0', '1.0.4', '1.1.0'], '1.0.4'];
        yield 'unsorted input' => ['1.0.2', ['1.0.2', '1.0.0', '1.0.1'], '1.0.1'];
        yield '8.x- prefix' => ['8.x-1.17', ['8.x-1.15', '8.x-1.16', '8.x-1.17'], '8.x-1.16'];
        yield 'same core prefix' => ['8.x-1.2', ['7.x-1.1', '8.x-1.1', '7.x-1.2', '8.x-1.2'], '8.x-1.1'];
        yield 'pre-releases before stable' => ['1.0.0', ['1.0.0-alpha1', '1.0.0-beta1', '1.0.0-rc1', '1.0.0'], '1.0.0-rc1'];
        yield 'alpha < beta < rc' => ['1.0.0-rc1', ['1.0.0-alpha1', '1.0.0-beta1', '1.0.0-rc1'], '1.0.0-beta1'];
        yield 'numbered pre-releases' => ['1.0.0-beta2', ['1.0.0-beta1', '1.0.0-beta2', '1.0.0-alpha3'], '1.0.0-beta1'];
        yield 'stable before next pre-release' => ['2.0.0-alpha1', ['1.0.0', '2.0.0-alpha1'], '1.0.0'];
    }

}
