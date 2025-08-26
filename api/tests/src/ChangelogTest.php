<?php

namespace App\Tests;

use App\Changelog;
use App\GitLab;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(Changelog::class)]
class ChangelogTest extends TestCase
{

    public function testGetContributors(): void
    {
        $client = $this->createMock(Client::class);
        $fixture = json_decode(file_get_contents(__DIR__.'/../fixtures/views_remote_data.json'));
        $sut = new Changelog($client, 'views_remote_data', $fixture->commits, '1.0.1', '1.0.2');
        self::assertEquals([
          'Lal_',
          'mrinalini9',
        ], $sut->getContributors());
    }

    public function testGetChanges(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/views_remote_data.json')),
          new Response(403),
          new Response(403),
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/3294296.json')),
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        $fixture = (new GitLab($client))->compare('views_remote_data', '1.0.1', 'HEAD');
        $sut = new Changelog($client, 'views_remote_data', $fixture->commits, '1.0.1', 'HEAD');
        self::assertEquals([
            'Lal_',
            'mglaman',
            'mrinalini9',
        ], $sut->getContributors());
        self::assertEquals([
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
        ], $sut->getChanges());
    }

    public function testNewFormat(): void {
      $client = $this->createMock(Client::class);
      $fixture = json_decode(file_get_contents(__DIR__ . '/../fixtures/entity_logger-new-format.json'), FALSE, 512, JSON_THROW_ON_ERROR);
      $sut = new Changelog($client, 'entity_logger', $fixture->commits, '1.0.11', '1.0.12');
      self::assertEquals([
        'aren33k',
        'eelkeblok',
        'svendecabooter',
      ], $sut->getContributors());
    }

}
