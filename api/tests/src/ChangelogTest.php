<?php

namespace App\Tests;

use App\Changelog;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\Changelog
 */
class ChangelogTest extends TestCase
{

    /**
     * @covers ::getContributors
     */
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

    /**
     * @covers ::getChanges
     */
    public function testGetChanges(): void
    {
        // @todo provide mocked responses.
        $client = new Client();
        $fixture = json_decode(file_get_contents(__DIR__.'/../fixtures/views_remote_data.json'));
        $sut = new Changelog($client, 'views_remote_data', $fixture->commits, '1.0.1', '1.0.2');
        self::assertEquals(          [
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
        ], $sut->getChanges());
    }

}
