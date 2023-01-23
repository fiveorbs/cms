<?php

declare(strict_types=1);

namespace Conia\Core\Field;

use Conia\Core\Field\Field;
use Conia\Core\Type;
use Conia\Core\Value\Images;

class Image extends Field
{
    protected bool $single = false;

    public function value(Type $page, array $data): Images
    {
        return new Images($page, $data);
    }

    public function single(bool $single = true): static
    {
        $this->single = $single;

        return $this;
    }
}
