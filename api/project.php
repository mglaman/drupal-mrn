<?php

declare(strict_types=1);

use App\Changelog;
use App\FormatOutput\FormatOutputFactory;
use App\GitLab;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

\Sentry\init([
  'dsn' => 'https://0ecd7a4d3b954f20b06e84efc52f4e8b@o4505060230627328.ingest.us.sentry.io/4507170561327104',
]);

$request = Request::createFromGlobals();
$project = $request->query->get('project', '');
if (!is_string($project) || $project === '') {
    (new JsonResponse([
      'message' => 'The project must be provided.',
    ], 400))->send();
    return;
}
$client = new Client();
$gitlab = new GitLab($client);

try {
    $data = [
      'tags' => $gitlab->tags($project),
      'branches' => $gitlab->branches($project),
    ];
} catch (\GuzzleHttp\Exception\ClientException $exception) {
    if ($exception->getResponse()->getStatusCode() === 404) {
        (new JsonResponse([
          'message' => 'The project cannot be found.'
        ], 400))->send();
        return;
    }
    (new JsonResponse([
      'message' => 'error:' . $exception->getMessage(),
    ], 400))->send();
    return;
} catch (\GuzzleHttp\Exception\RequestException $exception) {
    (new JsonResponse([
      'message' => 'error:' . $exception->getMessage(),
    ], 400))->send();
    return;
}

$response = new JsonResponse($data);
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Cache-Control', 'public, max-age=600');
$timestamp = time();
$response->setLastModified(new \DateTime(gmdate(\DateTimeInterface::RFC7231, $timestamp)));
$response->setEtag((string) $timestamp);
$response->send();
