<?php

namespace App\Tests\FormatOutput;

use App\Changelog;
use App\FormatOutput\HtmlFormatOutput;
use App\FormatOutput\MarkdownFormatOutput;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\FormatOutput\MarkdownFormatOutput
 */
class MarkdownFormatOutputTest extends TestCase
{

    /**
     * @covers ::format
     */
    public function testFormat()
    {
        $client = new Client();
        $fixture = json_decode(file_get_contents(__DIR__.'/../../fixtures/views_remote_data.json'));
        $changelog = new Changelog(
          $client,
          'views_remote_data',
          $fixture->commits,
          '1.0.1',
          '1.0.2'
        );
        $sut = new MarkdownFormatOutput();
        $expected = <<<MARKDOWN
/Add a summary here/

### Contributors (2)

[Lal_](https://www.drupal.org/u/lal_), [mrinalini9](https://www.drupal.org/u/mrinalini9)

### Changelog

**Issues**: 1 issues resolved.

Changes since [1.0.1](https://www.drupal.org/project/views_remote_data/releases/1.0.1):

#### Task

* #3294296 by mrinalini9, Lal_: Drupal 10 readiness for the module

MARKDOWN;

        self::assertEquals(
          $expected,
          $sut->format($changelog)
        );
    }

}
