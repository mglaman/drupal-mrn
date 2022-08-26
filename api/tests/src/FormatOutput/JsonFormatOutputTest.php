<?php

namespace App\Tests\FormatOutput;

use App\Changelog;
use App\FormatOutput\JsonFormatOutput;
use GuzzleHttp\Client;
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
        $client = new Client();
        $fixture = json_decode(file_get_contents(__DIR__.'/../../fixtures/views_remote_data.json'));
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
