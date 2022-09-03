<?php declare(strict_types=1);

namespace App\FormatOutput;

use App\Changelog;
use App\Formatter;
use App\TextBuffer;
use Symfony\Component\HttpFoundation\Response;

final class MarkdownFormatOutput implements FormatOutputInterface
{

    public function format(Changelog $changelog): string
    {
        $processedChanges = Changelog::groupByType($changelog->getChanges());
        $buffer = new TextBuffer();
        $buffer->writeln('/Add a summary here/');
        $buffer->writeln('');
        $buffer->writeln(
          sprintf('### Contributors (%s)', count($changelog->getContributors()))
        );
        $buffer->writeln('');
        $buffer->writeln(
          implode(
            ', ',
            array_map(
              static fn($username): string => Formatter::contributorLink($username, 'markdown'),
              $changelog->getContributors()
            )
          )
        );
        $buffer->writeln('');
        $buffer->writeln('### Changelog');
        $buffer->writeln('');
        $buffer->writeln(
          sprintf(
            '**Issues**: %s issues resolved.',
            $changelog->getIssueCount()
          )
        );
        $buffer->writeln('');
        $buffer->writeln(
          sprintf('Changes since [%1$s](https://www.drupal.org/project/%2$s/releases/%1$s):', $changelog->getFrom(), $changelog->getProject())
        );
        $buffer->writeln('');
        foreach ($processedChanges as $changeCategory => $changeCategoryItems) {
            $buffer->writeln(sprintf('#### %s', $changeCategory));
            $buffer->writeln('');
            foreach ($changeCategoryItems as $change) {
                $summary = preg_replace('/#(\d+)/S', sprintf('<a href="%s">#$1</a>', $change['link']), $change['summary']);
                $buffer->writeln(sprintf('* %s', $summary));
            }
            $buffer->writeln('');
        }
        return (string) $buffer;
    }

    public function getResponse(Changelog $changelog): Response
    {
        return new Response($this->format($changelog), 200, [
          'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

}
