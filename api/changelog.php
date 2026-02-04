<?php
declare(strict_types=1);

use App\Changelog;
use App\ClientFactory;
use App\FormatOutput\FormatOutputFactory;
use App\GitLab;
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
$from = $request->query->get('from', '');
$to = $request->query->get('to', 'HEAD');
$format = $request->query->get('format', 'html');

if (!is_string($project) || $project === '') {
    (new JsonResponse([
      'message' => 'Project cannot be empty',
    ], 400))->send();
    return;
}

$client = ClientFactory::create();
try {
    $compare = (new GitLab($client))->compare($project, $from, $to);
} catch (\GuzzleHttp\Exception\RequestException $e) {
    if ($e->hasResponse()) {
        $response = $e->getResponse();
        JsonResponse::fromJsonString((string) $response->getBody(), 400)->send();
    } else {
        (new JsonResponse([
          'message' => 'error contacting gitlab',
        ], 400))->send();
    }
    return;
}
$commits = $compare->commits;

$changelog = new Changelog(
  $client,
  $project,
  $commits,
  $from,
  $to
);

$response = FormatOutputFactory::getFormatOutput($format)
  ->getResponse($changelog);
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Cache-Control', 'public, max-age=86400');
$timestamp = time();
$response->setLastModified(new \DateTime(gmdate(\DateTimeInterface::RFC7231, $timestamp)));
$response->setEtag((string) $timestamp);
$response->send();

