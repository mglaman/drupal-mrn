<?php declare(strict_types=1);

namespace App;

final class CommitParser {

    public static function extractUsernames(string $title): array {
        $usernames = [];
        $matches = [];
        preg_match('/by ([^:]+):/S', $title, $matches);
        if (count($matches) !== 2) {
            return $usernames;
        }
        foreach (explode(',', $matches[1]) as $user) {
            $usernames[] = trim($user);
        }

        return $usernames;
    }

    public static function getNid(string $title): ?string {
        $matches = [];
        preg_match('/#(\d+)/S', $title, $matches);
        return count($matches) === 2 ? $matches[1] : null;
    }

}
