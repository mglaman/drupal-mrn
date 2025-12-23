<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
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

    private array $changes = [];

    private array $contributors;

    private array $changeRecords = [];

    public function __construct(
      private readonly Client $client,
      private readonly string $project,
      array $commits,
      private readonly string $from,
      private readonly string $to
    ) {
        if (count($commits) === 0) {
            throw new \RuntimeException('No commits for the changelog to process.');
        }
        $emailUsernameRegex = '/(?<=[0-9]-)([a-zA-Z0-9-_\.]{2,255})(?=@users\.noreply\.drupalcode\.org)/';
        $contributors = [];

        // Get project ID from machine name
        $drupalOrg = new DrupalOrg($this->client);
        $projectId = $drupalOrg->getProjectId($this->project);

        // Collect all NIDs for batch fetching
        $nids = [];
        foreach ($commits as $commit) {
            $nid = CommitParser::getNid($commit->title);
            if ($nid !== null) {
                $nids[] = $nid;
            }
        }

        // Fetch all contributors concurrently
        $contributorsFromApi = $drupalOrg->getContributorsFromJsonApi($nids);

        foreach ($commits as $commit) {
            $nid = CommitParser::getNid($commit->title);
            $commitContributors = [];
            
            // Try to use contributors from batch JSON:API fetch
            if ($nid !== null && isset($contributorsFromApi[$nid]) && !empty($contributorsFromApi[$nid])) {
                $commitContributors = $contributorsFromApi[$nid];
            }
            
            // Fallback to commit parsing if JSON:API didn't return contributors
            if (empty($commitContributors)) {
                $commitContributors = CommitParser::extractUsernames($commit);
                // Extract usernames from commit author and committer emails if available
                if (preg_match($emailUsernameRegex, $commit->author_email, $authorMatches)) {
                    $commitContributors[] = $authorMatches[0];
                }
                if (preg_match($emailUsernameRegex, $commit->committer_email, $committerMatches)) {
                    $commitContributors[] = $committerMatches[0];
                }
            }
            $contributors[] = $commitContributors;

            if ($nid !== null) {
                try {
                    $issue = \json_decode(
                      (string) $this->client->request('GET', "https://www.drupal.org/api-d7/node/$nid.json")
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
            $commitContributors = array_unique($commitContributors);
            sort($commitContributors);
            $this->changes[] = [
              'nid' => $nid,
              'link' => $nid !== null ? "https://www.drupal.org/i/$nid" : '',
              'type' => $issueCategoryLabel,
              'summary' => preg_replace('/^(Patch |- |Issue ){0,3}/', '', $commit->title),
              'contributors' => $commitContributors,
            ];
        }
        $this->contributors = array_unique(array_merge(...$contributors));
        sort($this->contributors);

        // Fetch change records if we have a project ID
        if ($projectId !== null) {
            $this->changeRecords = $drupalOrg->getChangeRecords($projectId, $this->to);
        }
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

    /**
     * @return array
     */
    public function getChangeRecords(): array
    {
        return $this->changeRecords;
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

}
