<?php declare(strict_types=1);

namespace App;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;

final class GitLab
{
    public function __construct(
      private readonly ClientInterface $client
    )
    {
    }

    public function search(string $project): object {
        $project = urlencode('project/' . $project);
        $response = $this->client->request(
          'GET',
          "https://git.drupalcode.org/api/v4/projects?search=$project"
        );
        return \json_decode((string) $response->getBody());
    }

    public function project(string $project): object {
        $project = urlencode('project/' . $project);
        $response = $this->client->request(
          'GET',
          "https://git.drupalcode.org/api/v4/projects/$project"
        );
        return \json_decode((string) $response->getBody());
    }

    public function compare(string $project, string $from, string $to): object {
        $project = urlencode('project/' . $project);
        $response = $this->client->request(
          'GET',
          "https://git.drupalcode.org/api/v4/projects/$project/repository/compare?from=$from&to=$to"
        );
        return \json_decode((string) $response->getBody());
    }

    public function branches(string $project): array {
        $project = urlencode('project/' . $project);
        $response = $this->client->request(
          'GET',
          "https://git.drupalcode.org/api/v4/projects/$project/repository/branches"
        );
        return \json_decode((string) $response->getBody());
    }

    public function tags(string $project): array {
        $project = urlencode('project/' . $project);
        $response = $this->client->request(
          'GET',
          "https://git.drupalcode.org/api/v4/projects/$project/repository/tags?order_by=updated"
        );
        return \json_decode((string) $response->getBody());
    }

    public function users(string $search): array {
        $response = $this->client->request(
          'GET',
          "https://git.drupalcode.org/api/v4/users?search=" . urlencode($search)
        );
        return \json_decode((string) $response->getBody()) ?: [];
    }

    /**
     * Fetch work item (issue) details for multiple IIDs concurrently.
     *
     * @param string $project The project machine name (e.g., "canvas")
     * @param array $iids Array of work item IIDs
     * @return array Associative array mapping iid => issue object (or null)
     */
    public function issues(string $project, array $iids): array {
        if (empty($iids)) {
            return [];
        }
        $project = urlencode('project/' . $project);
        $issues = [];
        $promises = [];
        foreach ($iids as $iid) {
            $promises[$iid] = $this->client->requestAsync(
              'GET',
              "https://git.drupalcode.org/api/v4/projects/$project/issues/$iid"
            );
        }
        $results = Utils::settle($promises)->wait();
        foreach ($results as $iid => $result) {
            if ($result['state'] === PromiseInterface::FULFILLED) {
                try {
                    $issues[$iid] = \json_decode((string) $result['value']->getBody(), false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $issues[$iid] = null;
                }
            } else {
                $issues[$iid] = null;
            }
        }
        return $issues;
    }

}
