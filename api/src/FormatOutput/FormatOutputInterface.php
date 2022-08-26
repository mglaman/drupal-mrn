<?php declare(strict_types=1);

namespace App\FormatOutput;

use App\Changelog;
use Symfony\Component\HttpFoundation\Response;

interface FormatOutputInterface {
    public function format(Changelog $changelog): string;
    public function getResponse(Changelog $changelog): Response;
}
