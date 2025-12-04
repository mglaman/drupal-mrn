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
          new Response(200, [], '{"list":[{"nid":"3258499"}]}'), // Project ID lookup
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-3294296.json')), // JSON:API contribution record
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/3294296.json')),
          new Response(200, [], '{"list":[]}'), // Change records API response (empty)
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

      $fixture = json_decode(file_get_contents(__DIR__ . '/../fixtures/eca_flag-2.0.5.json'), FALSE, 512, JSON_THROW_ON_ERROR);
      $sut = new Changelog($client, 'entity_logger', $fixture->commits, '2.0.5', '2.0.4');
      self::assertEquals([
        'anaconda777',
        'jurgenhaas',
      ], $sut->getContributors());
    }

    public function testGetChangeRecords(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/redis-compare.json')), // GitLab compare
          new Response(200, [], '{"list":[{"nid":"923314"}]}'), // Project ID lookup for Redis
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-empty.json')), // JSON:API contribution record (empty, fallback to commit parsing)
          new Response(403), // User search (author)
          new Response(403), // User search (committer)
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/3294296.json')), // Issue lookup (using existing fixture)
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/change-records-redis.json')), // Change records API response
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        $fixture = (new GitLab($client))->compare('redis', '8.x-1.8', '8.x-1.9');
        $sut = new Changelog($client, 'redis', $fixture->commits, '8.x-1.8', '8.x-1.9');

        // Verify change records are returned
        $changeRecords = $sut->getChangeRecords();
        self::assertCount(1, $changeRecords);
        self::assertEquals('3500807', $changeRecords[0]->nid);
        self::assertEquals('Ability to treat invalidateAll() like a deleteAll()', $changeRecords[0]->title);
        self::assertEquals('https://www.drupal.org/node/3500807', $changeRecords[0]->url);
        self::assertEquals('8.x-1.9', $changeRecords[0]->field_change_to);
    }

    public function testGetContributorsFromJsonApi(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/views_remote_data.json')), // GitLab compare
          new Response(200, [], '{"list":[{"nid":"3258499"}]}'), // Project ID lookup
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-3560441.json')), // JSON:API with contributors
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/3294296.json')), // Issue lookup
          new Response(200, [], '{"list":[]}'), // Change records API response (empty)
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        $fixture = (new GitLab($client))->compare('views_remote_data', '1.0.1', 'HEAD');
        $sut = new Changelog($client, 'views_remote_data', $fixture->commits, '1.0.1', 'HEAD');
        
        // Verify contributors from JSON:API are used
        self::assertEquals([
            'penyaskito',
            'wim leers',
        ], $sut->getContributors());
        
        $changes = $sut->getChanges();
        self::assertEquals([
            'penyaskito',
            'wim leers',
        ], $changes[0]['contributors']);
    }

    public function testGetContributorsFallbackToCommitParsing(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/views_remote_data.json')), // GitLab compare
          new Response(200, [], '{"list":[{"nid":"3258499"}]}'), // Project ID lookup
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-empty.json')), // JSON:API with no data - trigger fallback
          new Response(403), // User search (author) - fallback to commit parsing
          new Response(403), // User search (committer) - fallback to commit parsing
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/3294296.json')), // Issue lookup
          new Response(200, [], '{"list":[]}'), // Change records API response (empty)
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        $fixture = (new GitLab($client))->compare('views_remote_data', '1.0.1', 'HEAD');
        $sut = new Changelog($client, 'views_remote_data', $fixture->commits, '1.0.1', 'HEAD');
        
        // Verify fallback to commit parsing works
        self::assertEquals([
            'Lal_',
            'mglaman',
            'mrinalini9',
        ], $sut->getContributors());
    }

}
