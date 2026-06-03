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

    private array $changes = [];

    private array $contributors;

    private array $changeRecords = [];

    public function __construct(
      private readonly ClientInterface $client,
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

        // Get the project node from its machine name.
        $drupalOrg = new DrupalOrg($this->client);
        $projectInfo = $drupalOrg->getProjectInfo($this->project);
        $projectId = $projectInfo?->nid;
        // Projects migrated to GitLab work items have field_project_has_issue_queue
        // set to false. The field is absent for projects on the legacy queue.
        $hasGitLabIssues = isset($projectInfo->field_project_has_issue_queue)
          && !filter_var($projectInfo->field_project_has_issue_queue, FILTER_VALIDATE_BOOLEAN);

        // Collect all NIDs for batch fetching and cache them keyed by commit index
        $nids = [];
        $commitNids = [];
        foreach ($commits as $index => $commit) {
            $nid = CommitParser::getNid($commit->title);
            $commitNids[$index] = $nid;
            if ($nid !== null) {
                $nids[] = $nid;
            }
        }
        $nids = array_unique($nids);

        // Fetch all contributors and issue details concurrently. GitLab-issues
        // projects resolve issues from the GitLab API; legacy projects from the
        // Drupal.org node API.
        $contributorsFromApi = $drupalOrg->getContributorsFromJsonApi($nids);
        $gitlabIssues = [];
        $issueDetails = [];
        if ($hasGitLabIssues) {
            $gitlabIssues = (new GitLab($this->client))->issues($this->project, $nids);
        } else {
            $issueDetails = $drupalOrg->getIssueDetails($nids);
        }

        foreach ($commits as $index => $commit) {
            $nid = $commitNids[$index];
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

            $issueCategoryLabel = null;
            $link = $nid !== null ? "https://www.drupal.org/i/$nid" : '';
            if ($nid !== null && $hasGitLabIssues) {
                // Work item IIDs are not globally unique, so the canonical
                // project-scoped issue URL replaces the /i/ link.
                $link = "https://www.drupal.org/project/$this->project/issues/$nid";
                if (isset($gitlabIssues[$nid])) {
                    $issue = $gitlabIssues[$nid];
                    $issueCategoryLabel = self::categoryFromGitLabLabels($issue->labels ?? []);
                    $link = $issue->web_url ?? $link;
                    $this->issueCount++;
                }
            } elseif ($nid !== null && isset($issueDetails[$nid])) {
                $issue = $issueDetails[$nid];
                $issueCategory = $issue->field_issue_category ?? 0;
                $issueCategoryLabel = self::CATEGORY_MAP[$issueCategory] ?? null;
                $this->issueCount++;
            }

            // Fall back to the conventional-commit prefix when no category was
            // resolved (e.g. non-standard GitLab labels, or a failed lookup).
            $issueCategoryLabel ??= self::categoryFromConventionalCommit($commit->title);
            $issueCategoryLabel ??= self::CATEGORY_MAP[0];

            $commitContributors = array_unique($commitContributors);
            sort($commitContributors);
            $this->changes[] = [
              'nid' => $nid,
              'link' => $link,
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

    /**
     * Map a GitLab scoped `category::*` label to a category, or null if none.
     */
    private static function categoryFromGitLabLabels(array $labels): ?string
    {
        foreach ($labels as $label) {
            if (preg_match('/^category::(.+)$/i', (string) $label, $matches) === 1) {
                return match (strtolower(trim($matches[1]))) {
                    'bug' => self::CATEGORY_MAP[1],
                    'task' => self::CATEGORY_MAP[2],
                    'feature' => self::CATEGORY_MAP[3],
                    'support' => self::CATEGORY_MAP[4],
                    'plan' => self::CATEGORY_MAP[5],
                    default => null,
                };
            }
        }
        return null;
    }

    /**
     * Map a conventional-commit prefix to a category, or null if none.
     */
    private static function categoryFromConventionalCommit(string $title): ?string
    {
        if (preg_match('/^(fix|feat|chore|docs|style|refactor|perf|test|build|ci)(?:\([a-z0-9-]+\))?!?: /i', $title, $matches) === 1) {
            return match (strtolower($matches[1])) {
                'fix' => self::CATEGORY_MAP[1],
                'feat' => self::CATEGORY_MAP[3],
                'chore' => self::CATEGORY_MAP[2],
                default => null,
            };
        }
        return null;
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
