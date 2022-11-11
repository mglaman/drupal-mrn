<?php

namespace App\Tests;

use App\CommitParser;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\CommitParser
 */
class CommitParserTest extends TestCase
{

    /**
     * @covers ::extractUsernames
     */
    public function testExtractUsernames(): void {
        self::assertSame(
          ['mrinalini9', 'Lal_'],
          CommitParser::extractUsernames(
            'Issue #3294296 by mrinalini9, Lal_: Drupal 10 readiness for the module'
          )
        );
    }

    /**
     * @covers ::getNid
     * @dataProvider commitsNids
     */
    public function testGetNid(string $commit, string $expected): void {
      self::assertEquals(
        $expected,
        CommitParser::getNid($commit)
      );
    }

    public function commitsNids() {
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
