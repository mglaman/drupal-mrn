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
        $message_with_by_trailer = "feat: A new feature\n\nBy: smustgrave";
        yield 'by trailer' => [
            (object) [
                'title' => 'feat: A new feature',
                'message' => $message_with_by_trailer,
            ],
            false,
            ['smustgrave'],
        ];
        // Test case for issue: "by" word in commit message should not be confused as contributor
        $message_with_by_in_text = "[#3531858] fix(Internal HTTP API): CanvasController's response must vary by access result cacheability\n\nBy: wim leers\nBy: mglaman\nBy: penyaskito";
        yield 'by word in message text' => [
            (object) [
                'title' => "[#3531858] fix(Internal HTTP API): CanvasController's response must vary by access result cacheability",
                'message' => $message_with_by_in_text,
            ],
            false,
            ['wim leers', 'mglaman', 'penyaskito'],
        ];
        // Test case for new format with @ prefix in usernames
        $message_with_at_prefix = "fix(Redux-integrated field widgets): mglaman/drupal-mrn#3521641 AJAX race condition\n\nBy: @bnjmnm\nBy: @larowlan\nBy: @wimleers\nBy: @hooroomoo\nBy: @mglaman";
        yield 'new format with @ prefix' => [
            (object) [
                'title' => 'fix(Redux-integrated field widgets): mglaman/drupal-mrn#3521641 AJAX race condition',
                'message' => $message_with_at_prefix,
            ],
            true,
            ['bnjmnm', 'hooroomoo', 'larowlan', 'mglaman', 'wimleers'],
        ];
        // Test case for mixed format (old without @ and new with @) should deduplicate
        $message_mixed_format = "Issue #123 by userA, userB: Fix stuff.\n\nBy: @userA\nBy: @userC";
        yield 'mixed format with and without @ prefix' => [
            (object) [
                'title' => 'Issue #123 by userA, userB: Fix stuff.',
                'message' => $message_mixed_format,
            ],
            true,
            ['userA', 'userB', 'userC'],
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
