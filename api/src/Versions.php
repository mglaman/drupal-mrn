<?php declare(strict_types=1);

namespace App;

/**
 * Version helpers for Drupal.org release tags.
 *
 * Tags mix plain semver (1.0.1), core-prefixed tags (8.x-1.17), and
 * pre-release suffixes (-alpha1, -beta2, -rc1, -dev). This mirrors
 * app/src/lib/versions.js so the API and the UI pick the same "from" version.
 */
final class Versions
{

    /**
     * Finds the release that comes before the given version.
     *
     * @param list<string> $tags
     *   The tag names for the project.
     *
     * @return string|null
     *   The previous tag name, or NULL if the version is not a known tag or is
     *   the oldest one.
     */
    public static function findPrevious(string $version, array $tags): ?string
    {
        usort($tags, self::compare(...));

        $prefix = self::prefix($version);
        $stripped = self::stripPrefix($version);

        $currentIndex = null;
        if ($prefix !== '') {
            foreach ($tags as $index => $tag) {
                if (self::stripPrefix($tag) === $stripped && self::prefix($tag) === $prefix) {
                    $currentIndex = $index;
                    break;
                }
            }
        }
        if ($currentIndex === null) {
            foreach ($tags as $index => $tag) {
                if (self::stripPrefix($tag) === $stripped) {
                    $currentIndex = $index;
                    break;
                }
            }
        }
        if ($currentIndex === null || $currentIndex === 0) {
            return null;
        }

        // Prefer the closest older tag for the same core prefix, so 8.x-1.2
        // resolves to 8.x-1.1 and not 7.x-1.2.
        if ($prefix !== '') {
            for ($i = $currentIndex - 1; $i >= 0; $i--) {
                if (self::prefix($tags[$i]) === $prefix) {
                    return $tags[$i];
                }
            }
        }

        return $tags[$currentIndex - 1];
    }

    private static function compare(string $a, string $b): int
    {
        $baseA = explode('-', self::stripPrefix($a))[0];
        $baseB = explode('-', self::stripPrefix($b))[0];
        $baseComparison = version_compare($baseA, $baseB);
        if ($baseComparison !== 0) {
            return $baseComparison;
        }
        return self::preReleaseWeight($a) <=> self::preReleaseWeight($b);
    }

    private static function preReleaseWeight(string $version): int
    {
        $weight = match (true) {
            str_contains($version, '-alpha') => 100,
            str_contains($version, '-beta') => 200,
            str_contains($version, '-rc') => 300,
            str_contains($version, '-dev') => 50,
            default => 1000,
        };
        if (preg_match('/(alpha|beta|rc)(\d+)/', $version, $matches) === 1) {
            $weight += (int) $matches[2];
        }
        return $weight;
    }

    private static function prefix(string $version): string
    {
        return preg_match('/^\d+\.x-/', $version, $matches) === 1 ? $matches[0] : '';
    }

    private static function stripPrefix(string $version): string
    {
        return (string) preg_replace('/^\d+\.x-/', '', $version);
    }

}
