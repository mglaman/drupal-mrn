<?php

declare(strict_types=1);

namespace App\FormatOutput;

use App\Changelog;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class JsonFormatOutput implements FormatOutputInterface
{

    /**
     * @throws \JsonException
     */
    public function format(Changelog $changelog): string
    {
        return json_encode([
          'contributors' => $changelog->getContributors(),
          'issueCount' => $changelog->getIssueCount(),
          'from' => $changelog->getFrom(),
          'to' => $changelog->getTo(),
          'changes' => $changelog->getChanges(),
          'changeRecords' => $changelog->getChangeRecords(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public function getResponse(Changelog $changelog): Response
    {
        return JsonResponse::fromJsonString($this->format($changelog));
    }

}
