<?php

namespace App\Tests;

use App\TextBuffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextBuffer::class)]
class TextBufferTest extends TestCase
{

    #[DataProvider('textData')]
    public function testOutput(array $input, string $output): void
    {
        $sut = new TextBuffer();
        foreach ($input as $item) {
            $sut->writeln($item);
        }
        self::assertSame($output, (string) $sut);
    }

    public static function textData()
    {
        yield 'single string' => [
          ['foobar'],
          'foobar',
        ];
        yield 'multiple lines' => [
          ['hello', 'world'],
          "hello\nworld",
        ];
    }

}
