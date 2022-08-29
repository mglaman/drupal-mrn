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

$request = Request::createFromGlobals();
$project = $request->query->get('project', '');
if (!is_string($project) || $project === '') {
    return new JsonResponse([
      'message' => 'The project must be provided.',
    ], 400);
}
$client = new Client();
$gitlab = new GitLab($client);

$data = [
  'tags' => $gitlab->tags($project),
  'branches' => $gitlab->branches($project),
];

$response = new JsonResponse($data);
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Cache-Control', 'public, max-age=600');
$timestamp = time();
$response->setLastModified(new \DateTime(gmdate(\DateTimeInterface::RFC7231, $timestamp)));
$response->setEtag((string) $timestamp);
$response->send();
