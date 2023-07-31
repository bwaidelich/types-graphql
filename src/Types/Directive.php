<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class Directive
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?Arguments $arguments = null,
    ) {
    }

    public function render(): string
    {
        $result = "@$this->name";
        if ($this->arguments !== null) {
            $result .= '(' . $this->arguments->render() . ')';
        }
        return $result;
    }
}
