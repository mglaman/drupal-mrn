<?php declare(strict_types=1);

namespace App;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

final class DrupalOrg
{
    public function __construct(
      private readonly ClientInterface $client
    )
    {
    }

    /**
     * Fetch change records for a project and version.
     *
     * @param string $projectId The Drupal.org project ID
     * @param string $version The version to filter by (e.g., "8.x-1.9")
     * @return array Array of change record objects
     */
    public function getChangeRecords(string $projectId, string $version): array
    {
        try {
            $url = sprintf(
              'https://www.drupal.org/api-d7/node.json?type=changenotice&field_project=%s&field_change_to=%s',
              urlencode($projectId),
              urlencode($version)
            );
            $response = $this->client->request('GET', $url);
            $data = \json_decode((string) $response->getBody());
            return $data->list ?? [];
        } catch (RequestException) {
            // If the request fails, return empty array
            return [];
        }
    }

    /**
     * Get project ID from project machine name.
     *
     * @param string $machineName The project machine name (e.g., "redis")
     * @return string|null The project ID (nid), or null if not found
     */
    public function getProjectId(string $machineName): ?string
    {
        try {
            $url = sprintf(
              'https://www.drupal.org/api-d7/node.json?field_project_machine_name=%s',
              urlencode($machineName)
            );
            $response = $this->client->request('GET', $url);
            $data = \json_decode((string) $response->getBody());
            // The API returns a list array, get the first project node
            if (isset($data->list) && count($data->list) > 0) {
                return $data->list[0]->nid ?? null;
            }
            return null;
        } catch (RequestException) {
            return null;
        }
    }

    /**
     * Get project ID from an issue NID.
     * This is a helper method to extract project ID from an issue.
     *
     * @param string $nid The issue node ID
     * @return string|null The project ID, or null if not found
     */
    public function getProjectIdFromIssue(string $nid): ?string
    {
        try {
            $response = $this->client->request('GET', "https://www.drupal.org/api-d7/node/$nid.json");
            $issue = \json_decode((string) $response->getBody());
            return $issue->field_project->id ?? null;
        } catch (RequestException) {
            return null;
        }
    }
}

