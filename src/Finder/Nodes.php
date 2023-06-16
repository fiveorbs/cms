<?php

declare(strict_types=1);

namespace Conia\Core\Finder;

use Conia\Core\Context;
use Conia\Core\Finder;
use Conia\Core\Node;
use Generator;
use Iterator;

final class Nodes implements Iterator
{
    private string $whereFields = '';
    private string $whereTypes = '';
    private string $order = '';
    private ?int $limit = null;
    private ?bool $deleted = false;
    private ?bool $published = true;
    private readonly array $builtins;
    private Generator $result;

    public function __construct(
        private readonly Context $context,
        private readonly Finder $find,
    ) {
        $this->builtins = [
            'changed' => 'n.changed',
            'classname' => 't.classname',
            'created' => 'n.created',
            'creator' => 'uc.uid',
            'editor' => 'ue.uid',
            'deleted' => 'n.deleted',
            'id' => 'n.uid',
            'locked' => 'n.locked',
            'published' => 'n.published',
            'type' => 't.name',
            'uid' => 'n.uid',
            'kind' => 't.kind',
        ];
    }

    public function filter(string $query): self
    {
        $compiler = new QueryCompiler($this->context, $this->builtins);
        $this->whereFields = $compiler->compile($query);

        return $this;
    }

    public function types(string ...$types): self
    {
        $this->whereTypes = $this->typesCondition($types);

        return $this;
    }

    public function type(string $type): self
    {
        $this->whereTypes = $this->typesCondition([$type]);

        return $this;
    }

    public function order(string ...$order): self
    {
        $compiler = new OrderCompiler($this->builtins);
        $this->order = $compiler->compile(implode(',', $order));

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function published(?bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    public function deleted(?bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function rewind(): void
    {
        if (!isset($this->result)) {
            $this->fetchResult();
        }
        $this->result->rewind();
    }

    public function current(): Node
    {
        if (!isset($this->result)) {
            $this->fetchResult();
        }

        $page = $this->result->current();

        $class = $page['classname'];
        $page['content'] = json_decode($page['content'], true);
        $page['editor_data'] = json_decode($page['editor_data'], true);
        $page['creator_data'] = json_decode($page['creator_data'], true);
        $context = $this->context;

        return new $class($context, $this->find, $page);
    }

    public function key(): int
    {
        return $this->result->key();
    }

    public function next(): void
    {
        $this->result->next();
    }

    public function valid(): bool
    {
        return $this->result->valid();
    }

    private function fetchResult(): void
    {
        $conditions = implode(' AND ', array_filter([
            trim($this->whereFields),
            trim($this->whereTypes),
        ], fn ($clause) => !empty($clause)));

        $params = [
            'condition' => $conditions,
            'deleted' => $this->deleted,
            'published' => $this->published,
            'limit' => $this->limit,
        ];

        if ($this->order) {
            $params['order'] = $this->order;
        }

        $this->result = $this->context->db->nodes->find($params)->lazy();
    }

    private function typesCondition(array $types): string
    {
        $result = [];

        foreach ($types as $type) {
            if (class_exists($type) && is_subclass_of($type, Node::class)) {
                $result[] = 't.classname = ' . $this->context->db->quote($type);
            } else {
                $result[] = 't.name = ' . $this->context->db->quote($type);
            }
        }

        return match (count($result)) {
            0 => '',
            1 => '    ' . $result[0],
            default => "    (\n        "
                . implode("\n        OR ", $result)
                . "\n    )"
        };
    }
}