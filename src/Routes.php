<?php

declare(strict_types=1);

namespace Conia\Core;

use Conia\Chuck\Group;
use Conia\Core\App;
use Conia\Core\Middleware\InitRequest;
use Conia\Core\View\Auth;
use Conia\Core\View\Page;
use Conia\Core\View\Panel;

class Routes
{
    protected string $panelPath;
    protected string $apiPath;

    public function __construct(protected Config $config)
    {
        $this->panelPath = $config->getPanelPath();
        $this->apiPath = $this->panelPath . '/api';
    }

    public function add(App $app): void
    {
        $this->addIndex($app);

        // All API routes
        $app->group(
            $this->apiPath,
            $this->addPanelApi(...),
            'conia.panel.',
        )->render('json');

        // Add catchall for page url paths. Must be the last one
        $app->route(
            '/...slug',
            [Page::class, 'catchall'],
            'conia:catchall',
        )->middleware(InitRequest::class);
    }

    protected function addIndex(App $app): void
    {
        $app->get($this->panelPath, fn () => '<h1>Panel not found in public directory</h1>')
            ->render('text', contentType: 'text/html');
    }

    protected function addAuth(Group $api): void
    {
        $api->get('/me', [Auth::class, 'me'], 'auth.user');
        $api->post('/login', [Auth::class, 'login'], 'auth.login');
        $api->post('/logout', [Auth::class, 'logout'], 'auth.logout')->render('json');
    }

    protected function addUser(Group $api): void
    {
        $api->get('users', [User::class, 'list'], 'users');
        $api->get('user/{uid}', [User::class, 'get'], 'user.get');
        $api->post('user', [User::class, 'create'], 'user.create');
        $api->put('user/{uid}', [User::class, 'save'], 'user.save');
    }

    protected function addSettings(Group $api): void
    {
        $api->get('/settings', [Panel::class, 'settings'], 'conia.settings');
    }

    protected function addSystem(Group $api): void
    {
        $api->get('/boot', [Panel::class, 'boot'], 'conia.boot');
        $api->get('/type/{name}', [Panel::class, 'type'], 'conia.type');
    }

    protected function addPanelApi(Group $api): void
    {
        $this->addSettings($api);
        $this->addAuth($api);
        $this->addUser($api);
        $this->addSystem($api);
    }
}
