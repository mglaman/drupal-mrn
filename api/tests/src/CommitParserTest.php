<?php

namespace App\Tests;

use App\CommitParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommitParser::class)]
class CommitParserTest extends TestCase
{

    public function testExtractUsernames(): void {
        self::assertSame(
          ['mrinalini9', 'Lal_'],
          CommitParser::extractUsernames(
            'Issue #3294296 by mrinalini9, Lal_: Drupal 10 readiness for the module'
          )
        );
    }

    #[DataProvider('commitsNids')]
    public function testGetNid(string $commit, string $expected): void {
      self::assertEquals(
        $expected,
        CommitParser::getNid($commit)
      );
    }

    public static function commitsNids() {
      yield [
        'Issue #3294296 by mrinalini9, Lal_: Drupal 10 readiness for the module',
        '3294296'
      ];
      yield [
        'Fix issue 3178420',
        '3178420',
      ];
      yield [
        'Some random code changes without an nid',
        '',
      ];
    }

}
