<?php

namespace App\Tests\FormatOutput;

use App\Changelog;
use App\FormatOutput\HtmlFormatOutput;
use App\GitLab;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\FormatOutput\HtmlFormatOutput
 */
class HtmlFormatOutputTest extends TestCase
{

    /**
     * @covers ::format
     */
    public function testFormat()
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/views_remote_data.json')),
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
        $sut = new HtmlFormatOutput();
        $expected = <<<HTML
<p><em>Add a summary here</em></p>
<h3>Contributors (2)</h3>
<p><a href="https://www.drupal.org/u/lal_">Lal_</a>, <a href="https://www.drupal.org/u/mrinalini9">mrinalini9</a></p>
<h3>Changelog</h3>
<p><strong>Issues:</strong> 1 issues resolved.</p>
<p>Changes since <a href="https://www.drupal.org/project/views_remote_data/releases/1.0.1">1.0.1</a>:</p>
<h4>Task</h4>
<ul>
  <li>#3294296 by mrinalini9, Lal_: Drupal 10 readiness for the module</li>
</ul>
HTML;

        self::assertEquals(
          $expected,
          $sut->format($changelog)
        );
    }

}
