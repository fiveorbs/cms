<?php

declare(strict_types=1);

namespace Conia\Core\Middleware;

use Conia\Chuck\Middleware;
use Conia\Chuck\Request;
use Conia\Chuck\Response;
use Conia\Core\Config;

class Session implements Middleware
{
    public function __construct(protected Config $config)
    {
    }

    public function __invoke(Request $request, callable $next): Response
    {
        $session = new \Conia\Core\Session(
            $this->config->app(),
            $this->config->get('session.options', []),
            $this->config->get('session.handler', null),
        );
        $session->start();

        $request->set('session', $session);

        return $next($request);
    }
}
