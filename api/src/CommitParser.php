<?php declare(strict_types=1);

namespace App;

final class CommitParser {

    public static function extractUsernames(string $title, $sort = false): array {
        $usernames = [];
        $matches = [];
        preg_match('/by ([^:]+):/S', $title, $matches);
        if (count($matches) !== 2) {
            return $usernames;
        }
        foreach (explode(',', $matches[1]) as $user) {
            $usernames[] = trim($user);
        }
        if ($sort) {
            sort($usernames);
        }

        return $usernames;
    }

    public static function getNid(string $title): ?string {
        $matches = [];
        // Drupal.org commits should have "Issue #{nid}".
        if (preg_match('/#(\d+)/S', $title, $matches) === 1) {
            return $matches[1];
        }
        // But maybe they forgot the leading "#" on the issue ID.
        if (preg_match('/([0-9]{4,})/S', $title, $matches) === 1) {
            return $matches[1];
        }
        return null;
    }

}
