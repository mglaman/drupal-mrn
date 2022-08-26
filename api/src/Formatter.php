<?php declare(strict_types=1);

namespace App;

final class Formatter {
    public static function contributorLink(string $name, string $format): string {
        $baseUrl = 'https://www.drupal.org/u/%1$s';
        $userAlias = str_replace(' ', '-', mb_strtolower($name));
        if ($format === 'html') {
            $replacement = '<a href="'.$baseUrl.'">%2$s</a>';
        } elseif ($format === 'markdown' || $format === 'md') {
            $replacement = '[%2$s]('.$baseUrl.')';
        } else {
            $replacement = '%2$s';
        }
        return sprintf($replacement, $userAlias, $name);
    }
}
