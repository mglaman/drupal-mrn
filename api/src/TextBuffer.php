<?php declare(strict_types=1);

namespace App;

final class TextBuffer implements \Stringable {
    private array $out = [];
    public function writeln(string $data): void {
        $this->out[] = $data;
    }
    public function __toString(): string
    {
        return implode(PHP_EOL, $this->out);
    }

}
