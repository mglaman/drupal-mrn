<?php

namespace App\Tests\FormatOutput;

use App\Changelog;
use App\FormatOutput\HtmlFormatOutput;
use App\GitLab;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlFormatOutput::class)]
class HtmlFormatOutputTest extends TestCase
{

    public function testFormat()
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/views_remote_data.json')),
          new Response(200, [], '{"list":[{"nid":"3258499"}]}'), // Project ID lookup
          new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/contribution-record-3294296.json')), // JSON:API contribution record
          new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/3294296.json')),
          new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/change-record-views-remote-data.json')), // Change records API response
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
<h3>Contributors (3)</h3>
<p><a href="https://www.drupal.org/u/lal_">Lal_</a>, <a href="https://www.drupal.org/u/mglaman">mglaman</a>, <a href="https://www.drupal.org/u/mrinalini9">mrinalini9</a></p>
<h3>Changelog</h3>
<p><strong>Issues:</strong> 1 issues resolved.</p>
<p>Changes since <a href="https://www.drupal.org/project/views_remote_data/releases/1.0.1">1.0.1</a> (<a href="https://git.drupalcode.org/project/views_remote_data/-/compare/1.0.1...1.0.2">compare</a>):</p>
<h4>Task</h4>
<ul>
  <li><a href="https://www.drupal.org/i/3294296">#3294296</a> by mrinalini9, Lal_: Drupal 10 readiness for the module</li>
</ul>
<h3>Change Records</h3>
<ul>
  <li><a href="https://www.drupal.org/node/1234567">Test change record for views_remote_data</a></li>
</ul>
HTML;

        self::assertEquals(
          $expected,
          $sut->format($changelog)
        );
    }

    public function testEscapesMarkupInCommitTitles(): void
    {
        $html = $this->formatCommits([
          (object) [
            'id' => 'abc123',
            'title' => 'Issue #3294296 by mglaman: fix <script>alert(1)</script> handling',
            'message' => '',
            'author_email' => 'git@example.com',
            'committer_email' => 'git@example.com',
          ],
        ]);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        // Escaping must not break issue linkification.
        self::assertStringContainsString(
          '<a href="https://www.drupal.org/i/3294296">#3294296</a>',
          $html
        );
    }

    public function testEscapesMarkupInContributorNames(): void
    {
        $html = $this->formatCommits([
          (object) [
            'id' => 'abc123',
            'title' => 'Issue #3294296 by <img src=x onerror=alert(1)>: some fix',
            'message' => '',
            'author_email' => 'git@example.com',
            'committer_email' => 'git@example.com',
          ],
        ]);

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    private function formatCommits(array $commits): string
    {
        $client = new Client([
          'handler' => HandlerStack::create(function () {
            return new \GuzzleHttp\Promise\FulfilledPromise(new Response(404));
          }),
        ]);
        $changelog = new Changelog($client, 'test_project', $commits, '1.0.0', '1.1.0');
        return (new HtmlFormatOutput())->format($changelog);
    }

}
