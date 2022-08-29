<?php

namespace App\Tests;

use App\GitLab;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\GitLab
 */
class GitLabTest extends TestCase
{

    /**
     * @covers ::tags
     */
    public function testTags(): void {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
          new Response(200, [], file_get_contents(__DIR__ . '/../fixtures/views_remote_data.tags.json'))
        ]);
        $client = new \GuzzleHttp\Client([
          'handler' => HandlerStack::create($mockHandler)
        ]);
        $sut = new GitLab($client);
        self::assertCount(5, $sut->tags('views_remote_data'));
    }

    /**
     * @covers ::branches
     */
    public function testBranches(): void {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
          new Response(200, [], file_get_contents(__DIR__ . '/../fixtures/views_remote_data.branches.json'))
        ]);
        $client = new \GuzzleHttp\Client([
          'handler' => HandlerStack::create($mockHandler)
        ]);
        $sut = new GitLab($client);
        self::assertCount(1, $sut->branches('views_remote_data'));
    }

}
