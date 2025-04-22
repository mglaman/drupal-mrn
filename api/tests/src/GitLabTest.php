<?php

namespace App\Tests;

use App\GitLab;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitLab::class)]
class GitLabTest extends TestCase
{

    public function testTags(): void {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__ . '/../fixtures/views_remote_data.tags.json'))
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler)
        ]);
        $sut = new GitLab($client);
        self::assertCount(5, $sut->tags('views_remote_data'));
    }

    public function testBranches(): void {
        $mockHandler = new MockHandler([
          new Response(200, [], file_get_contents(__DIR__ . '/../fixtures/views_remote_data.branches.json'))
        ]);
        $client = new Client([
          'handler' => HandlerStack::create($mockHandler)
        ]);
        $sut = new GitLab($client);
        self::assertCount(1, $sut->branches('views_remote_data'));
    }

    public function testUsers(): void {
        $mockHandler = new MockHandler([
            new Response(200, [], file_get_contents(__DIR__ . '/../fixtures/users.search.author_name.json')),
            new Response(200, [], file_get_contents(__DIR__ . '/../fixtures/users.search.committer_name.json')),
        ]);
        $client = new Client([
            'handler' => HandlerStack::create($mockHandler)
        ]);
        $sut = new GitLab($client);
        $users = $sut->users('mrinalini9');
        self::assertCount(1, $users);
        self::assertEquals('mrinalini9', $users[0]->username);
        $users = $sut->users('Matt Glaman');
        self::assertCount(1, $users);
        self::assertEquals('mglaman', $users[0]->username);
    }

}
