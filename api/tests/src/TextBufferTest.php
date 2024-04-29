<?php

namespace App\Tests;

use App\TextBuffer;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\TextBuffer
 */
class TextBufferTest extends TestCase
{

    /**
     * @covers ::writeln
     * @dataProvider textData
     */
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
