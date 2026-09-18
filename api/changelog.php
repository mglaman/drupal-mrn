<?php
declare(strict_types=1);

use App\Changelog;
use App\ClientFactory;
use App\FormatOutput\FormatOutputFactory;
use App\GitLab;
use App\Versions;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

\Sentry\init([
  'dsn' => 'https://0ecd7a4d3b954f20b06e84efc52f4e8b@o4505060230627328.ingest.us.sentry.io/4507170561327104',
]);

$request = Request::createFromGlobals();

try {
    $project = $request->query->get('project', '');
    $from = $request->query->get('from', '');
    $to = $request->query->get('to', 'HEAD');
    // The format parameter wins. Without it, honor the Accept header so
    // clients can send `Accept: text/markdown`.
    $format = $request->query->get(
      'format',
      FormatOutputFactory::formatFromContentTypes($request->getAcceptableContentTypes())
    );
} catch (BadRequestException) {
    (new JsonResponse([
      'message' => 'The project, from, to, and format parameters must be strings.',
    ], 400))->send();
    return;
}

if (!is_string($project) || $project === '') {
    (new JsonResponse([
      'message' => 'The project must be provided.',
    ], 400))->send();
    return;
}
if (!is_string($from) || !is_string($to) || !is_string($format)) {
    (new JsonResponse([
      'message' => 'The from, to, and format parameters must be strings.',
    ], 400))->send();
    return;
}

try {
    $formatOutput = FormatOutputFactory::getFormatOutput($format);
} catch (\InvalidArgumentException) {
    (new JsonResponse([
      'message' => sprintf('Invalid format "%s". Use one of: html, markdown, json.', $format),
    ], 400))->send();
    return;
}

$client = ClientFactory::create();
$gitlab = new GitLab($client);
$projectHint = sprintf('Call /project?project=%s to list its tags and branches.', urlencode($project));

if ($from === '') {
    try {
        $tagNames = array_map(static fn (object $tag): string => $tag->name, $gitlab->tags($project));
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        if ($e->getResponse()->getStatusCode() === 404) {
            (new JsonResponse([
              'message' => 'The project cannot be found.',
            ], 404))->send();
            return;
        }
        (new JsonResponse([
          'message' => 'Error contacting GitLab: ' . $e->getMessage(),
        ], 502))->send();
        return;
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        (new JsonResponse([
          'message' => 'Error contacting GitLab: ' . $e->getMessage(),
        ], 502))->send();
        return;
    }
    $previous = Versions::findPrevious($to, $tagNames);
    if ($previous === null) {
        (new JsonResponse([
          'message' => sprintf('Could not detect the release before "%s". Pass the from parameter. %s', $to, $projectHint),
        ], 400))->send();
        return;
    }
    $from = $previous;
}

try {
    $compare = $gitlab->compare($project, $from, $to);
} catch (\GuzzleHttp\Exception\ClientException $e) {
    if ($e->getResponse()->getStatusCode() === 404) {
        $gitlabMessage = json_decode((string) $e->getResponse()->getBody())->message ?? '';
        $message = str_contains((string) $gitlabMessage, 'Project')
          ? 'The project cannot be found.'
          : sprintf('The version "%s" or "%s" does not exist. %s', $from, $to, $projectHint);
        (new JsonResponse([
          'message' => $message,
        ], 404))->send();
        return;
    }
    (new JsonResponse([
      'message' => 'Error contacting GitLab: ' . $e->getMessage(),
    ], 502))->send();
    return;
} catch (\GuzzleHttp\Exception\RequestException $e) {
    (new JsonResponse([
      'message' => 'Error contacting GitLab: ' . $e->getMessage(),
    ], 502))->send();
    return;
}
$commits = $compare->commits;

try {
    $changelog = new Changelog(
      $client,
      $project,
      $commits,
      $from,
      $to
    );
} catch (\RuntimeException $e) {
    $compareUrl = sprintf(
        'https://git.drupalcode.org/project/%s/-/compare/%s...%s',
        $project,
        $from,
        $to
    );
    (new JsonResponse([
      'message' => $e->getMessage(),
      'compare_url' => $compareUrl,
    ], 400))->send();
    return;
}

$response = $formatOutput->getResponse($changelog);
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Cache-Control', 'public, max-age=86400');
$response->setVary('Accept');
$timestamp = time();
$response->setLastModified(new \DateTime(gmdate(\DateTimeInterface::RFC7231, $timestamp)));
$response->setEtag((string) $timestamp);
$response->send();

