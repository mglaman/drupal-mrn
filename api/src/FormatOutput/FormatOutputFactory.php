<?php declare(strict_types=1);

namespace App\FormatOutput;

final class FormatOutputFactory {
    public static function getFormatOutput(string $format): FormatOutputInterface
    {
        return match ($format) {
            'json' => new JsonFormatOutput(),
            'html' => new HtmlFormatOutput(),
            'md', 'markdown' => new MarkdownFormatOutput(),
            default => throw new \InvalidArgumentException("$format isn't a valid format.")
        };
    }

    /**
     * Picks a format from the request's acceptable content types.
     *
     * @param list<string> $contentTypes
     *   Content types ordered by preference, as returned by
     *   Request::getAcceptableContentTypes().
     */
    public static function formatFromContentTypes(array $contentTypes): string
    {
        foreach ($contentTypes as $contentType) {
            $format = match ($contentType) {
                'text/markdown' => 'markdown',
                'application/json' => 'json',
                'text/html' => 'html',
                default => null,
            };
            if ($format !== null) {
                return $format;
            }
        }
        return 'html';
    }
}
