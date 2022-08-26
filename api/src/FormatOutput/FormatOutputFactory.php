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
}
