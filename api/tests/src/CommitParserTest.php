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

}
