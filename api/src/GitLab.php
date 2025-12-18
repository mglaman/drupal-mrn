<?php declare(strict_types=1);

namespace App;

use GuzzleHttp\ClientInterface;

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

}
