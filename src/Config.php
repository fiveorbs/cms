<?php

declare(strict_types=1);

namespace Conia;

use \Exception;
use \ValueError;
use Chuck\Config as BaseConfig;
use Chuck\Config\Connection;


class Config extends BaseConfig
{
    protected string $panelPath = 'panel';
    protected string $theme = null;
    /** @var array<string, Type> */
    protected array $types = [];

    public function __construct(
        string $app,
        string $dsn,
        bool $debug = false,
        string $env = ''
    ) {
        parent::__construct($app, $debug, $env);
        $root = dirname(__DIR__);

        $this->addConnection(new Connection(
            $dsn,
            $root . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'sql',
            $root . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'migrations',
        ), 'conia');

        $this->set('panel.path', 'panel');
        $this->set('panel.theme', null);
        $this->set('session.expires', 60 * 60 * 24);
        $this->set('session.authcookie', $app . '_auth');

        $this->scripts()->add($root . DIRECTORY_SEPARATOR . 'bin');
    }

    public function setPanelPath(string $url): void
    {
        if (preg_match('/^[A-Za-z0-9]{1,32}$/', $url)) {
            $this->panelPath = $url;
        } else {
            throw new ValueError(
                'The panel url prefix be a nonempty string which consist only of letters' .
                    ' and numbers. Its length must not be longer than 32 characters.'
            );
        }
    }

    public function addType(Type $type): void
    {
        $name = $type->name;

        if (array_key_exists($name, $this->types)) {
            throw new Exception("Type '$name' already exists. Instance of '{$type::class}'");
        }

        $this->types[$name] = $type;
    }

    public function types(): array
    {
        return $this->types;
    }

    public function type(string $name): Type
    {
        return $this->types[$name];
    }
}
