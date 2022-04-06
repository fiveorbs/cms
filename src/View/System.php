<?php

declare(strict_types=1);

namespace Conia\View;

use Conia\Controller;
use Conia\Request;
use Conia\Response;


class System extends Controller
{
    public function settings(): array
    {
        return ['hans' => 'franz'];
    }
}
