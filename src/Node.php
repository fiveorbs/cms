<?php

declare(strict_types=1);

namespace Conia\Core;

use Conia\Chuck\Exception\HttpBadRequest;
use Conia\Chuck\Registry;
use Conia\Chuck\Renderer\Render;
use Conia\Chuck\Request;
use Conia\Chuck\Response;
use Conia\Core\Config;
use Conia\Core\Exception\NoSuchField;
use Conia\Core\Exception\RuntimeException;
use Conia\Core\Exception\ValueError;
use Conia\Core\Field\Attr\Description;
use Conia\Core\Field\Attr\Fulltext;
use Conia\Core\Field\Attr\Height;
use Conia\Core\Field\Attr\Label;
use Conia\Core\Field\Attr\Multiple;
use Conia\Core\Field\Attr\Required;
use Conia\Core\Field\Attr\Translate;
use Conia\Core\Field\Attr\TranslateImage;
use Conia\Core\Field\Attr\Width;
use Conia\Core\Field\Field;
use Conia\Core\Finder;
use Conia\Core\Locale;
use Conia\Core\Value\Value;
use Conia\Core\Value\ValueContext;
use Conia\Quma\Database;
use Generator;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

abstract class Node
{
    public readonly Request $request;
    public readonly Config $config;
    protected readonly Database $db;
    protected readonly Registry $registry;
    protected static string $name = '';
    protected static string $template = '';
    protected static array $permissions = [];
    protected static int $columns = 12;
    protected array $list = [];

    final public function __construct(
        Context $context,
        protected readonly Finder $find,
        protected readonly array $data,
    ) {
        $this->initFields();

        $this->db = $context->db;
        $this->request = $context->request;
        $this->config = $context->config;
        $this->registry = $context->registry;
    }

    final public function __get(string $fieldName): ?Value
    {
        return $this->getValue($fieldName);
    }

    // TODO: should be optimized as this could result
    //       in many ::get() calls
    final public function __isset(string $fieldName): bool
    {
        if (isset($this->{$fieldName})) {
            return $this->{$fieldName}->value()->isset();
        }

        return false;
    }

    final public function getValue(string $fieldName): ?Value
    {
        if (!isset($this->{$fieldName})) {
            $type = $this::class;

            throw new NoSuchField("The field '{$fieldName}' does not exist on node with type '{$type}'.");
        }

        $field = $this->{$fieldName};
        $value = $field->value();

        if ($value->isset()) {
            return $value;
        }

        return null;
    }

    /**
     * Is called after self::initFields.
     *
     * Can be used to make adjustments the already initialized fields
     */
    public function init(): void
    {
    }

    /**
     * Should return the general title of the node.
     *
     * Shown in the admin interface. But can also be used in the frontend.
     */
    abstract public function title(): string;

    public function form(): Generator
    {
        foreach ($this->list as $field) {
            yield $this->fields[$field];
        }
    }

    public static function columns(): int
    {
        if (static::$columns < 12 || static::$columns > 25) {
            throw new ValueError('The value of $columns must be >= 12 and <= 25');
        }

        return static::$columns;
    }

    public static function className(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }

    public static function name(): ?string
    {
        if (!empty(static::$name)) {
            return static::$name;
        }

        return strtolower(static::className());
    }

    public function uid(): string
    {
        return $this->data['uid'];
    }

    public function path(Locale $locale = null): string
    {
        $paths = json_decode($this->data['paths'], true);

        if (!$locale) {
            $locale = $this->request->get('locale');
        }

        while ($locale) {
            if (isset($paths[$locale->id])) {
                return $paths[$locale->id];
            }

            $locale = $locale->fallback();
        }

        error_log(print_r($this->data, true));

        throw new RuntimeException('No url path found');
    }

    public static function template(): ?string
    {
        if (!empty(static::$template)) {
            return static::$template;
        }

        return static::name();
    }

    public function response(): Response
    {
        $request = $this->request;

        return match ($request->method()) {
            'GET' => $this->get(),
            'POST' => $this->post(),
            'PUT' => $this->put(),
            'DELETE' => $this->delete(),
            default => throw new HttpBadRequest(),
        };
    }

    public function get(): Response
    {
        // Create a JSON response if the URL ends with .json
        if ($this->request->get('isJson', false)) {
            return Response::fromFactory($this->factory)->json($this->json());
        }

        return $this->render();
    }

    public function post(): Response
    {
        throw new HttpBadRequest();
    }

    public function put(): Response
    {
        throw new HttpBadRequest();
    }

    public function delete(): Response
    {
        throw new HttpBadRequest();
    }

    public function json(array $context = []): array
    {
        $data = $this->data;

        unset($data['classname']);

        $content = [
            'content' => $this->getJsonContent(),
        ];

        return array_merge($data, $content, $context);
    }

    protected function render(array $context = []): Response
    {
        $context = array_merge([
            'page' => $this,
            'find' => $this->find,
            'locale' => $this->request->get('locale'),
            'locales' => $this->config->locales,
        ], $context);

        try {
            $render = new Render('template', self::template());

            return $render->response($this->registry, $context);
        } catch (Throwable $e) {
            if ($this->config->debug()) {
                throw $e;
            }

            throw new HttpBadRequest();
        }
    }

    protected function initFields(): void
    {
        $rc = new ReflectionClass(static::class);

        foreach ($rc->getProperties() as $property) {
            $name = $property->getName();

            if (!$property->hasType()) {
                continue;
            }

            $fieldType = $property->getType()->getName();

            if (is_subclass_of($fieldType, Field::class)) {
                if (isset($this->{$name})) {
                    continue;
                }

                $this->{$name} = $this->initField($property, $fieldType);
            }
        }

        $this->init();
    }

    protected function initField(ReflectionProperty $property, string $fieldType): Field
    {
        $fieldName = $property->getName();
        $content = $this->data['content'][$fieldName] ?? [];
        $field = new $fieldType($fieldName, $this, new ValueContext($fieldName, $content));

        foreach ($property->getAttributes() as $attr) {
            switch ($attr->getName()) {
                case Required::class:
                    $field->required(true);
                    break;
                case Translate::class:
                    $field->translate(true);
                    break;
                case Label::class:
                    $field->label($attr->newInstance()->label);
                    break;
                case Description::class:
                    $field->description($attr->newInstance()->description);
                    break;
                case Fulltext::class:
                    $field->fulltext($attr->newInstance()->fulltextWeight);
                    break;
                case Width::class:
                    $field->width($attr->newInstance()->width);
                    break;
                case Height::class:
                    $field->height($attr->newInstance()->height);
                    break;
                case Multiple::class:
                    $field->multiple(true);

                    break;
                case TranslateImage::class:
                    if (!$field instanceof \Conia\Core\Field\Image) {
                        throw new RuntimeException('Cannot apply attribute Multiple to ' . $field::class);
                    }

                    $field->translateImage(true);

                    break;
            }
        }

        return $field;
    }

    protected function getJsonContent(): array
    {
        $result = [];

        foreach ($this->list as $field) {
            $result[$field] = $this->__get($field)->json();
        }

        return $result;
    }

    protected function locale(): Locale
    {
        return $this->request->get('locale');
    }
}