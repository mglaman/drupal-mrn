<?php declare(strict_types=1);

namespace App;

final class Formatter {
    public static function contributorLink(string $name, string $format): string {
        $baseUrl = 'https://www.drupal.org/u/%1$s';
        $userAlias = str_replace(' ', '-', mb_strtolower($name));
        if ($format === 'html') {
            // Usernames are parsed out of commit messages; escape both the
            // href and the link text.
            return sprintf(
                '<a href="%s">%s</a>',
                htmlspecialchars(sprintf($baseUrl, $userAlias), ENT_QUOTES),
                htmlspecialchars($name, ENT_QUOTES)
            );
        }
        if ($format === 'markdown' || $format === 'md') {
            return sprintf('[%2$s]('.$baseUrl.')', $userAlias, $name);
        }
        return $name;
    }
}
