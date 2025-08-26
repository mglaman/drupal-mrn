<?php

namespace App\Tests;

use App\CommitParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommitParser::class)]
class CommitParserTest extends TestCase
{

    #[DataProvider('providerExtractUsernames')]
    public function testExtractUsernames(\stdClass $commit, bool $sort, array $expected): void
    {
        self::assertEquals(
            $expected,
            CommitParser::extractUsernames($commit, $sort)
        );
    }

    public static function providerExtractUsernames(): \Generator
    {
        $title = 'Issue #3294296 by mrinalini9, Lal_: Drupal 10 readiness for the module';
        yield 'classic' => [
            (object) ['title' => $title, 'message' => $title],
            false,
            ['mrinalini9', 'Lal_']
        ];
        yield 'classic sorted' => [
            (object) ['title' => $title, 'message' => $title],
            true,
            ['Lal_', 'mrinalini9']
        ];
        $message_with_trailers = "[#3542407] feat: Code cleanup\n\nAuthored-by: svendecabooter <40491-svendecabooter@users.noreply.drupalcode.org>";
        yield 'authored-by' => [
            (object) [
                'title' => '[#3542407] feat: Code cleanup',
                'message' => $message_with_trailers,
            ],
            false,
            ['svendecabooter']
        ];
        $message_with_co_authors = "[#3542407] feat: Code cleanup\n\nCo-authored-by: user1 <user1@example.com>\nCo-authored-by: user2 <user2@example.com>";
        yield 'co-authored-by' => [
            (object) [
                'title' => '[#3542407] feat: Code cleanup',
                'message' => $message_with_co_authors,
            ],
            true,
            ['user1', 'user2']
        ];
        $mixed_message = "Issue #123 by userA, userB: Fix stuff.\n\nCo-authored-by: userA <userA@example.com>\nAuthored-by: userD <userD@example.com>";
        yield 'mixed with duplicates' => [
            (object) [
                'title' => 'Issue #123 by userA, userB: Fix stuff.',
                'message' => $mixed_message,
            ],
            true,
            ['userA', 'userB', 'userD']
        ];
        $no_users_message = 'A commit with no user attribution';
        yield 'no users' => [
            (object) [
                'title' => $no_users_message,
                'message' => $no_users_message,
            ],
            false,
            []
        ];
    }

    #[DataProvider('commitsNids')]
    public function testGetNid(string $commit, string $expected): void {
      self::assertEquals(
        $expected,
        CommitParser::getNid($commit)
      );
    }

    public static function commitsNids() {
      yield [
        'Issue #3294296 by mrinalini9, Lal_: Drupal 10 readiness for the module',
        '3294296'
      ];
      yield [
        'Fix issue 3178420',
        '3178420',
      ];
      yield [
        'Some random code changes without an nid',
        '',
      ];
    }
}
