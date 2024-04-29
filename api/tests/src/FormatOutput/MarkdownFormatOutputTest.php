<?php

namespace App\Tests\FormatOutput;

use App\Changelog;
use App\FormatOutput\HtmlFormatOutput;
use App\FormatOutput\MarkdownFormatOutput;
use App\GitLab;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Nyholm\Psr7\Response;
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
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/views_remote_data.json')),
            new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/users.search.author_name.json')),
            new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/users.search.committer_name.json')),
          new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/3294296.json')),
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler)
        ]);
        $fixture = (new GitLab($client))->compare('views_remote_data', '1.0.1', 'HEAD');
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

### Contributors (3)

[Lal_](https://www.drupal.org/u/lal_), [mglaman](https://www.drupal.org/u/mglaman), [mrinalini9](https://www.drupal.org/u/mrinalini9)

### Changelog

**Issues**: 1 issues resolved.

Changes since [1.0.1](https://www.drupal.org/project/views_remote_data/releases/1.0.1):

#### Task

* [#3294296](https://www.drupal.org/i/3294296) by mrinalini9, Lal_: Drupal 10 readiness for the module

MARKDOWN;

        self::assertEquals(
          $expected,
          $sut->format($changelog)
        );
    }

}
