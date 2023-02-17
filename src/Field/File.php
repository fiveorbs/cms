<?php

declare(strict_types=1);

namespace Conia\Core\Field;

use Conia\Core\Type;
use Conia\Core\Value\Files;
use Conia\Core\Value\ValueContext;

class File extends Field
{
    protected bool $multiple = false;

    public function value(Type $node, ValueContext $context): Files
    {
        return new Files($node, $this, $context);
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }
}
