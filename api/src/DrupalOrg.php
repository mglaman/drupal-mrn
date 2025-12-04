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
            if ($data === null || !isset($data->list)) {
                return [];
            }
            return $data->list;
        } catch (RequestException) {
            // If the request fails, return empty array
            return [];
        } catch (\JsonException) {
            // If JSON decoding fails, return empty array
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
            if ($data === null || !isset($data->list) || count($data->list) === 0) {
                return null;
            }
            // The API returns a list array, get the first project node
            return $data->list[0]->nid ?? null;
        } catch (RequestException) {
            return null;
        } catch (\JsonException) {
            // If JSON decoding fails, return null
            return null;
        }
    }

    /**
     * Fetch contributors for an issue from the contribution records API.
     *
     * @param string $nid The issue node ID
     * @return array Array of contributor display names
     */
    public function getContributorsFromJsonApi(string $nid): array
    {
        try {
            $url = sprintf(
              'https://www.drupal.org/jsonapi/node/contribution_record?filter[field_source_link.uri]=https://www.drupal.org/node/%s&include=field_contributors.field_contributor_user&fields[node--contribution_record]=field_contributors&fields[paragraph--contributor]=field_contributor_user&fields[user--user]=display_name',
              urlencode($nid)
            );
            $response = $this->client->request('GET', $url);
            $data = \json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR);
            
            // Check if we have data
            if (!isset($data->data) || count($data->data) === 0) {
                return [];
            }
            
            // Extract display names from included users
            $contributors = [];
            if (isset($data->included)) {
                foreach ($data->included as $item) {
                    if ($item->type === 'user--user' && isset($item->attributes->display_name)) {
                        $contributors[] = $item->attributes->display_name;
                    }
                }
            }
            
            return $contributors;
        } catch (RequestException) {
            // If the request fails, return empty array
            return [];
        } catch (\JsonException) {
            // If JSON decoding fails, return empty array
            return [];
        }
    }

}

