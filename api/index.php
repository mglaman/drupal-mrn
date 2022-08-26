<?php
declare(strict_types=1);

use App\Changelog;
use App\FormatOutput\FormatOutputFactory;
use App\FormatOutput\JsonFormatOutput;
use App\Formatter;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require __DIR__.'/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

$request = Request::createFromGlobals();

$project = urlencode('project/'.$request->query->get('project', ''));
$from = $request->query->get('from', '');
$to = $request->query->get('to', '');
$format = $request->query->get('format', 'html');

$client = new Client();

$response = $client->get("https://git.drupalcode.org/api/v4/projects/$project/repository/compare?from=$from&to=$to");
$compare = \json_decode((string) $response->getBody());
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
$response->headers->set('Cache-Control', 'public, max-age=86400');
$timestamp = time();
$response->setLastModified(new \DateTime(gmdate(\DateTimeInterface::RFC7231, $timestamp)));
$response->setEtag((string) $timestamp);
$response->send();

