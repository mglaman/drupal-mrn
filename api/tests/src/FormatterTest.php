<?php

namespace App\Tests;

use App\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Formatter::class)]
class FormatterTest extends TestCase
{

    public function testHtmlLink(): void
    {
        self::assertEquals(
            '<a href="https://www.drupal.org/u/lal_">Lal_</a>',
            Formatter::contributorLink('Lal_', 'html')
        );
    }

    public function testHtmlLinkEscapesMarkup(): void
    {
        self::assertEquals(
            '<a href="https://www.drupal.org/u/&lt;img-src=x-onerror=alert(1)&gt;">&lt;img src=x onerror=alert(1)&gt;</a>',
            Formatter::contributorLink('<img src=x onerror=alert(1)>', 'html')
        );
    }

    public function testHtmlLinkEscapesQuotes(): void
    {
        self::assertEquals(
            '<a href="https://www.drupal.org/u/x&quot;-onmouseover=&quot;alert(1)">x&quot; onmouseover=&quot;alert(1)</a>',
            Formatter::contributorLink('x" onmouseover="alert(1)', 'html')
        );
    }

    public function testMarkdownLink(): void
    {
        self::assertEquals(
            '[My User](https://www.drupal.org/u/my-user)',
            Formatter::contributorLink('My User', 'markdown')
        );
    }

    public function testPlainText(): void
    {
        self::assertEquals('Lal_', Formatter::contributorLink('Lal_', 'text'));
    }

}
