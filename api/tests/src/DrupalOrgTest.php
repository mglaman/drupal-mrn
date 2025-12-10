<?php

namespace App\Tests;

use App\DrupalOrg;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DrupalOrg::class)]
class DrupalOrgTest extends TestCase
{
    public function testGetContributorsFromJsonApi(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-3560441.json')),
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        
        $drupalOrg = new DrupalOrg($client);
        $contributors = $drupalOrg->getContributorsFromJsonApi('3560441');
        
        self::assertEquals([
            'wim leers',
            'penyaskito',
        ], $contributors);
    }

    public function testGetContributorsFromJsonApiEmpty(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-empty.json')),
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        
        $drupalOrg = new DrupalOrg($client);
        $contributors = $drupalOrg->getContributorsFromJsonApi('9999999');
        
        self::assertEquals([], $contributors);
    }

    public function testGetContributorsFromJsonApiRequestException(): void
    {
        $mockHandler = new MockHandler([
          new Response(403),
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        
        $drupalOrg = new DrupalOrg($client);
        $contributors = $drupalOrg->getContributorsFromJsonApi('3560441');
        
        self::assertEquals([], $contributors);
    }

    public function testGetProjectId(): void
    {
        $mockHandler = new MockHandler([
          new Response(200, [], '{"list":[{"nid":"923314"}]}'),
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        
        $drupalOrg = new DrupalOrg($client);
        $projectId = $drupalOrg->getProjectId('redis');
        
        self::assertEquals('923314', $projectId);
    }

    public function testGetContributorsFromJsonApiBatch(): void
    {
        // Setup responses for two different issues
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-3560441.json')),
          new Response(200, [], file_get_contents(__DIR__.'/../fixtures/contribution-record-3294296.json')),
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler),
        ]);
        
        $drupalOrg = new DrupalOrg($client);
        $contributors = $drupalOrg->getContributorsFromJsonApiBatch(['3560441', '3294296']);
        
        // Verify we get both results mapped to their NIDs
        self::assertArrayHasKey('3560441', $contributors);
        self::assertArrayHasKey('3294296', $contributors);
        
        self::assertEquals([
            'wim leers',
            'penyaskito',
        ], $contributors['3560441']);
        
        self::assertEquals([
            'mrinalini9',
            'Lal_',
            'mglaman',
        ], $contributors['3294296']);
    }

    public function testGetContributorsFromJsonApiBatchEmpty(): void
    {
        $drupalOrg = new DrupalOrg($this->createMock(Client::class));
        $contributors = $drupalOrg->getContributorsFromJsonApiBatch([]);
        
        self::assertEquals([], $contributors);
    }
}
