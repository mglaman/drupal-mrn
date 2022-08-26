<?php declare(strict_types=1);

namespace App\FormatOutput;

use App\Changelog;
use App\Formatter;
use App\TextBuffer;
use Symfony\Component\HttpFoundation\Response;

final class HtmlFormatOutput implements FormatOutputInterface
{

    public function format(Changelog $changelog): string
    {
        $processedChanges = Changelog::groupByType($changelog->getChanges());
        $buffer = new TextBuffer();
        $buffer->writeln('<p><em>Add a summary here</em></p>');
        $buffer->writeln(
          sprintf('<h3>Contributors (%s)</h3>', count($changelog->getContributors()))
        );
        $buffer->writeln(
          sprintf(
            '<p>%s</p>',
            implode(
              ', ',
              array_map(
                static fn($username) : string => Formatter::contributorLink($username, 'html'),
                $changelog->getContributors()
              )
            )
          )
        );
        $buffer->writeln('<h3>Changelog</h3>');
        $buffer->writeln(
          sprintf(
            '<p><strong>Issues:</strong> %s issues resolved.</p>',
            $changelog->getIssueCount()
          )
        );
        $buffer->writeln(
          sprintf(
            '<p>Changes since <a href="https://www.drupal.org/project/%2$s/releases/%1$s">%1$s</a>:</p>',
            $changelog->getFrom(),
            $changelog->getProject()
          )
        );

        foreach ($processedChanges as $changeCategory => $changeCategoryItems) {
            $buffer->writeln(
              sprintf('<h4>%s</h4>', $changeCategory)
            );
            $buffer->writeln('<ul>');
            foreach ($changeCategoryItems as $change) {
                $buffer->writeln(
                  sprintf('  <li>%s</li>', $change)
                );
            }
            $buffer->writeln('</ul>');
        }
        return (string) $buffer;
    }

    public function getResponse(Changelog $changelog): Response
    {
        return new Response($this->format($changelog), 200, [
          'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }

}
