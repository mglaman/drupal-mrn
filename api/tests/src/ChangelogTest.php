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
        $client = new Client([
            'handler' => HandlerStack::create(function () {
                return new \GuzzleHttp\Promise\FulfilledPromise(new Response(404));
            }),
        ]);
        $fixture = json_decode(file_get_contents(__DIR__.'/../fixtures/views_remote_data.json'));
        $sut = new Changelog($client, 'views_remote_data', $fixture->commits, '1.0.1', '1.0.2');
        self::assertEquals([
          'Lal_',
          'mglaman',
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
      $client = new Client([
          'handler' => HandlerStack::create(function () {
              return new \GuzzleHttp\Promise\FulfilledPromise(new Response(404));
          }),
      ]);
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

    public function testGitLabIssuesCategorization(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/canvas-compare.json')), // GitLab compare
          new Response(200, [], '{"list":[{"nid":"2431121","field_project_has_issue_queue":false}]}'), // Project node: migrated to GitLab issues
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-empty.json')), // contributors nid 3555239
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-empty.json')), // contributors nid 3588438
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-empty.json')), // contributors nid 3576410
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/canvas-issue-3555239.json')), // GitLab work item (category::bug)
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/canvas-issue-3588438.json')), // GitLab work item (category::task)
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/canvas-issue-3576410.json')), // GitLab work item (category::bug)
          new Response(200, [], '{"list":[]}'), // Change records (empty)
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        $fixture = (new GitLab($client))->compare('canvas', '1.4.1', '1.5.0');
        $sut = new Changelog($client, 'canvas', $fixture->commits, '1.4.1', '1.5.0');

        self::assertEquals(3, $sut->getIssueCount());

        $changes = $sut->getChanges();
        $byNid = [];
        foreach ($changes as $change) {
            $byNid[$change['nid']] = $change;
        }

        // Category comes from the scoped `category::*` GitLab label, not Misc.
        self::assertEquals('Bug', $byNid['3555239']['type']);
        self::assertEquals('Task', $byNid['3588438']['type']);
        self::assertEquals('Bug', $byNid['3576410']['type']);

        // Link is the work item web_url, not the broken /i/ link.
        self::assertEquals(
          'https://git.drupalcode.org/project/canvas/-/work_items/3555239',
          $byNid['3555239']['link']
        );
    }

    public function testGitLabIssuesFallBackToConventionalCommit(): void
    {
        // GitLab project where the work item lookup fails (404). Categories should
        // fall back to the conventional-commit prefix instead of all-Misc.
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/canvas-compare.json')), // GitLab compare
          new Response(200, [], '{"list":[{"nid":"2431121","field_project_has_issue_queue":false}]}'), // Project node
          new Response(404), // contributors nid 3555239
          new Response(404), // contributors nid 3588438
          new Response(404), // contributors nid 3576410
          new Response(404), // work item 3555239
          new Response(404), // work item 3588438
          new Response(404), // work item 3576410
          new Response(200, [], '{"list":[]}'), // Change records (empty)
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        $fixture = (new GitLab($client))->compare('canvas', '1.4.1', '1.5.0');
        $sut = new Changelog($client, 'canvas', $fixture->commits, '1.4.1', '1.5.0');

        $byNid = [];
        foreach ($sut->getChanges() as $change) {
            $byNid[$change['nid']] = $change;
        }

        // chore: -> Task, fix: -> Bug
        self::assertEquals('Task', $byNid['3555239']['type']);
        self::assertEquals('Task', $byNid['3588438']['type']);
        self::assertEquals('Bug', $byNid['3576410']['type']);

        // Falls back to the canonical project-scoped issue URL.
        self::assertEquals(
          'https://www.drupal.org/project/canvas/issues/3555239',
          $byNid['3555239']['link']
        );
    }

    public function testConventionalCommitScopeWithSpacesAndCaps(): void
    {
        // GitLab work items without a category:: label fall back to the
        // conventional-commit prefix. Scopes here contain spaces and capitals,
        // e.g. "feat(CLI Tool):" and "chore(Project management):".
        $commit = static function (string $title): \stdClass {
            $c = new \stdClass();
            $c->title = $title;
            $c->message = $title;
            $c->author_email = 'noreply@example.com';
            $c->committer_email = 'noreply@example.com';
            return $c;
        };
        $commits = [
          $commit('feat(CLI Tool): #3591610 Clean up output of Canvas Create'),
          $commit('chore(Project management): #3591594 Adopt COMPOSER_NO_BLOCKING'),
        ];

        $mockHandler = new MockHandler([
          new Response(200, [], '{"list":[{"nid":"2431121","field_project_has_issue_queue":false}]}'), // Project node
          new Response(404), // contributors nid 3591610
          new Response(404), // contributors nid 3591594
          new Response(404), // work item 3591610
          new Response(404), // work item 3591594
          new Response(200, [], '{"list":[]}'), // Change records (empty)
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        $sut = new Changelog($client, 'canvas', $commits, '1.4.1', '1.5.0');

        $byNid = [];
        foreach ($sut->getChanges() as $change) {
            $byNid[$change['nid']] = $change;
        }
        self::assertEquals('Feature', $byNid['3591610']['type']);
        self::assertEquals('Task', $byNid['3591594']['type']);
    }

    public function testThrowsExceptionWhenNoCommits(): void
    {
        // The client is needed by Changelog constructor but won't be used when commits array is empty
        $client = $this->createMock(ClientInterface::class);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No commits for the changelog to process.');
        
        new Changelog($client, 'test_project', [], '1.0.0', '1.0.1');
    }

}
