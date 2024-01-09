<?php

declare(strict_types=1);

namespace Conia\Core\Field\Attr;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate
{
    public readonly array $validators;

    public function __construct(string ...$validators)
    {
        $this->validators = $validators;
    }
}
