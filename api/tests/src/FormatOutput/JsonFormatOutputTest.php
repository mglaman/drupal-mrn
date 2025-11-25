<?php

namespace App\Tests\FormatOutput;

use App\Changelog;
use App\FormatOutput\JsonFormatOutput;
use App\GitLab;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonFormatOutput::class)]
class JsonFormatOutputTest extends TestCase
{

    public function testFormat(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/views_remote_data.json')),
          new Response(200, [], '{"list":[{"nid":"3258499"}]}'), // Project ID lookup
            new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/users.search.author_name.json')),
            new Response(200, [], file_get_contents(__DIR__.'/../../fixtures/users.search.committer_name.json')),
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
        $sut = new JsonFormatOutput();
        $result = json_decode($sut->format($changelog), true);
        self::assertEquals(
          [
            'contributors' => [
              'Lal_',
              'mglaman',
              'mrinalini9',
            ],
            'issueCount' => 1,
            'changes' => [
              [
                'nid' => '3294296',
                'type' => 'Task',
                'summary' => '#3294296 by mrinalini9, Lal_: Drupal 10 readiness for the module',
                'link' => 'https://www.drupal.org/i/3294296',
                'contributors' => [
                  'Lal_',
                  'mglaman',
                  'mrinalini9',
                ],
              ],
            ],
            'from' => '1.0.1',
            'to' => '1.0.2',
          ],
          [
            'contributors' => $result['contributors'],
            'issueCount' => $result['issueCount'],
            'changes' => $result['changes'],
            'from' => $result['from'],
            'to' => $result['to'],
          ]
        );
        // Verify change records are present
        self::assertArrayHasKey('changeRecords', $result);
        self::assertCount(1, $result['changeRecords']);
        self::assertEquals('1234567', $result['changeRecords'][0]['nid']);
        self::assertEquals('Test change record for views_remote_data', $result['changeRecords'][0]['title']);
        self::assertEquals('https://www.drupal.org/node/1234567', $result['changeRecords'][0]['url']);
    }

}
