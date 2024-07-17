<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

final class Changelog
{
    private const CATEGORY_MAP = [
      0 => 'Misc',
      1 => 'Bug',
      2 => 'Task',
      3 => 'Feature',
      4 => 'Support',
      5 => 'Plan',
    ];

    private int $issueCount = 0;

    private array $changes;

    private array $contributors;

    public function __construct(
      private readonly ClientInterface $client,
      private readonly string $project,
      array $commits,
      private readonly string $from,
      private readonly string $to
    ) {
        $gitlab = new GitLab($this->client);
        if (count($commits) === 0) {
            throw new \RuntimeException('No commits for the changelog to process.');
        }
        $contributors = [];
        foreach ($commits as $commit) {
            $commitContributors = CommitParser::extractUsernames($commit->title);
            $emailUsernameRegex = '/(?<=[0-9]-)([a-zA-Z0-9-_\.]{2,255})(?=@users\.noreply\.drupalcode\.org)/';
            try {
                $author = $gitlab->users($commit->author_name);
                if (count($author) > 0) {
                    $commitContributors[] = $author[0]->username;
                }
            } catch (RequestException) {
                if (preg_match($emailUsernameRegex, $commit->author_email, $authorMatches)) {
                    $commitContributors[] = $authorMatches[0];
                }
            }
            try {
                $committer = $gitlab->users($commit->committer_name);
                if (count($committer) > 0) {
                    $commitContributors[] = $committer[0]->username;
                }
            } catch (RequestException) {
                if (preg_match($emailUsernameRegex, $commit->committer_email, $committerMatches)) {
                    $commitContributors[] = $committerMatches[0];
                }
            }
            $contributors[] = $commitContributors;

            $nid = CommitParser::getNid($commit->title);
            if ($nid !== null) {
                try {
                    $issue = \json_decode(
                      (string) $this->client->get("https://www.drupal.org/api-d7/node/$nid.json")
                        ->getBody()
                    );
                    $issueCategory = $issue->field_issue_category ?? 0;
                    $issueCategoryLabel = self::CATEGORY_MAP[$issueCategory];
                    $this->issueCount++;
                } catch (RequestException $e) {
                    $issueCategoryLabel = self::CATEGORY_MAP[0];
                }
            } else {
                $issueCategoryLabel = self::CATEGORY_MAP[0];
            }
            $this->changes[] = [
              'nid' => $nid,
              'link' => $nid !== null ? "https://www.drupal.org/i/$nid" : '',
              'type' => $issueCategoryLabel,
              'summary' => preg_replace('/^(Patch |- |Issue ){0,3}/', '', $commit->title),
              'contributors' => $this->getChangeContributors($commit->title),
            ];
        }
        $this->contributors = array_unique(array_merge(...$contributors));
        sort($this->contributors);
    }

    /**
     * @return array
     */
    public function getContributors(): array
    {
        return $this->contributors;
    }

    /**
     * @return array
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @return int
     */
    public function getIssueCount(): int
    {
        return $this->issueCount;
    }

    /**
     * @return string
     */
    public function getProject(): string
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    public static function groupByType(array $changes): array
    {
        $grouped = [];
        foreach ($changes as $change) {
            $grouped[$change['type']][] = $change;
        }
        ksort($grouped);
        return $grouped;
    }

    private function getChangeContributors(string $change): array
    {
        $match = [];
        preg_match('/by ([^:]+):/S', $change, $match);
        if (count($match) !== 2) {
            return [];
        }
        $names = explode(', ', $match[1]);
        sort($names);
        return $names;
    }

    private function formatLine(string $value): string
    {
        $value = preg_replace('/^(Patch |- |Issue ){0,3}/', '', $value);

        $baseUrl = 'https://www.drupal.org/i/$1';

        if ($this->format === 'html') {
            $replacement = sprintf('<a href="%s">#$1</a>', $baseUrl);
        } elseif ($this->format === 'markdown' || $this->format === 'md') {
            $replacement = sprintf('[#$1](%s)', $baseUrl);
        } else {
            $replacement = '#$1';
        }

        $value = preg_replace('/#(\d+)/S', $replacement, $value);

        // Anything between 'by' and ':' is a comma-separated list of usernames.
        return preg_replace_callback(
          '/by ([^:]+):/S',
          function (array $matches): string {
              $out = array_map(
                fn(string $user) => Formatter::contributorLink(trim($user),
                  $this->format),
                explode(',', $matches[1])
              );
              return 'by '.implode(', ', $out).':';
          },
          $value
        );
    }

}
