<?php

namespace App\Tests\FormatOutput;

use App\Changelog;
use App\FormatOutput\JsonFormatOutput;
use App\GitLab;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\FormatOutput\JsonFormatOutput
 */
class JsonFormatOutputTest extends TestCase
{

    /**
     * @covers ::format
     */
    public function testFormat(): void
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
        $sut = new JsonFormatOutput();
        self::assertEquals(
          [
            'contributors' => [
              'Lal_',
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
                  'mrinalini9',
                ],
              ],
            ],
            'from' => '1.0.1',
            'to' => '1.0.2',
          ],
          json_decode($sut->format($changelog), true)
        );
    }

}
