<?php

declare(strict_types=1);

namespace Conia\Cms\Schema;

use Conia\Cms\Field\Field;
use Conia\Cms\Field\File;
use Conia\Cms\Field\Image;
use Conia\Cms\Field\Picture;
use Conia\Cms\Locales;
use Conia\Cms\Node\Node;
use Conia\Sire\Schema;

class NodeSchemaFactory
{
    protected readonly Schema $schema;

    public function __construct(
        protected readonly Node $node,
        protected readonly Locales $locales,
    ) {
        $this->schema = new Schema(keepUnknown: true);
        $this->schema->add('uid', 'text', 'required', 'maxlen:64');
        $this->schema->add('published', 'bool', 'required');
        $this->schema->add('locked', 'bool', 'required');
        $this->schema->add('hidden', 'bool', 'required');
    }

    public function create(): Schema
    {
        $contentSchema = new Schema(title: 'Content', keepUnknown: true);

        foreach ($this->node->fieldNames() as $fieldName) {
            $this->add($contentSchema, $fieldName, $this->node->getField($fieldName));
        }

        $this->schema->add('content', $contentSchema);

        return $this->schema;
    }

    protected function add(Schema $schema, string $fieldName, Field $field): void
    {
        $validators = $field->validators();

        $schema->add($fieldName, match ($field::class) {
            \Conia\Cms\Field\Checkbox::class => $this->addBool($field, 'checkbox', $validators),
            \Conia\Cms\Field\Date::class => $this->addText($field, 'date', $validators),
            \Conia\Cms\Field\DateTime::class => $this->addText($field, 'datetime', $validators),
            \Conia\Cms\Field\Decmial::class => $this->addText($field, 'decimal', $validators),
            \Conia\Cms\Field\File::class => $this->addFile($field, 'file', $validators),
            \Conia\Cms\Field\Grid::class => $this->addGrid($field, 'grid', $validators),
            \Conia\Cms\Field\Html::class => $this->addText($field, 'html', $validators),
            \Conia\Cms\Field\Iframe::class => $this->addText($field, 'iframe', $validators),
            \Conia\Cms\Field\Image::class => $this->addImage($field, 'image', $validators),
            \Conia\Cms\Field\Number::class => $this->addNumber($field, 'number', $validators),
            \Conia\Cms\Field\Option::class => $this->addOption($field, 'option', $validators),
            \Conia\Cms\Field\Picture::class => $this->addImage($field, 'picture', $validators),
            \Conia\Cms\Field\Radio::class => $this->addText($field, 'radio', $validators),
            \Conia\Cms\Field\Text::class => $this->addText($field, 'text', $validators),
            \Conia\Cms\Field\Textarea::class => $this->addText($field, 'textarea', $validators),
            \Conia\Cms\Field\Time::class => $this->addText($field, 'time', $validators),
            \Conia\Cms\Field\Youtube::class => $this->addText($field, 'youtube', $validators),
        }, ...$validators)->label($field->getLabel());
    }

    protected function getTypedSchema(string $title, string $type): Schema
    {
        $schema = new Schema(title: $title, keepUnknown: true);
        $schema->add('type', 'text', 'required', 'in:' . $type);

        return $schema;
    }

    protected function addText(Field $field, string $type, array $validators): Schema
    {
        $schema = $this->getTypedSchema($field->getLabel(), $type);

        if ($field->isTranslatable()) {
            $schema->add(
                'value',
                $this->getTranslatableSchema($field, $field->isRequired(), $validators),
                ...$validators
            );
        } else {
            $schema->add('value', 'text', ...$validators);
        }

        return $schema;
    }

    protected function addOption(Field $field, string $type, array $validators): Schema
    {
        $schema = $this->getTypedSchema($field->getLabel(), $type);
        $schema->add('value', 'text', ...$validators);

        return $schema;
    }

    protected function addNumber(Field $field, string $type, array $validators): Schema
    {
        $schema = $this->getTypedSchema($field->getLabel(), $type);
        $schema->add('value', 'float', ...$validators);

        return $schema;
    }

    protected function addBool(Field $field, string $type, array $validators): Schema
    {
        $schema = $this->getTypedSchema($field->getLabel(), $type);
        $schema->add('value', 'bool', ...$validators);

        return $schema;
    }

    protected function addFile(File $field, string $type, array $validators): Schema
    {
        $schema = $this->getTypedSchema($field->getLabel(), $type);

        if ($field->isFileTranslatable()) {
            $schema->add(
                'files',
                $this->getTranslatableFileSchema($field, ['file', 'title']),
                ...$validators
            );
        } elseif ($field->isTranslatable()) {
            $fileSchema = new Schema(list: true, keepUnknown: true);
            $fileSchema->add('file', 'text', 'required');
            $fileSchema->add('title', $this->getTranslatableSchema($field, false, $validators));

            $schema->add('files', $fileSchema, ...$validators);
        } else {
            $fileSchema = new Schema(list: true, keepUnknown: true);
            $fileSchema->add('file', 'text', 'required');
            $fileSchema->add('title', 'text');
            $schema->add('files', $fileSchema, ...$validators);
        }

        return $schema;
    }

    protected function addImage(Image|Picture $field, string $type, array $validators): Schema
    {
        $schema = $this->getTypedSchema($field->getLabel(), $type);

        if ($field->isFileTranslatable()) {
            $schema->add(
                'files',
                $this->getTranslatableFileSchema($field, ['file', 'title', 'alt']),
                ...$validators
            );
        } elseif ($field->isTranslatable()) {
            $fileSchema = new Schema(list: true, keepUnknown: true);
            $fileSchema->add('file', 'text', 'required');
            $fileSchema->add('title', $this->getTranslatableSchema($field, false, $validators));
            $fileSchema->add('alt', $this->getTranslatableSchema($field, false, $validators));

            $schema->add('files', $fileSchema, ...$validators);
        } else {
            $fileSchema = new Schema(list: true, keepUnknown: true);
            $fileSchema->add('file', 'text', 'required');
            $fileSchema->add('title', 'text');
            $fileSchema->add('alt', 'text');
            $schema->add('files', $fileSchema, ...$validators);
        }

        return $schema;
    }

    protected function addGrid(Field $field, string $type, array $validators): Schema
    {
        $title = $field->getLabel();
        $schema = $this->getTypedSchema($title, $type);
        $schema->add('columns', 'int', 'required');

        $itemSchema = new GridItemSchema(list: true, title: $title, keepUnknown: true);

        if ($field->isTranslatable()) {
            $defaultLocale = $this->locales->getDefault()->id;
            $i18nSchema = new Schema(title: $title, keepUnknown: true);

            foreach ($this->locales as $locale) {
                $innerValidators = [];

                if ($field->isRequired() && $locale->id === $defaultLocale) {
                    $innerValidators[] = 'required';
                }

                $i18nSchema->add($locale->id, $itemSchema, ...$innerValidators);
            }

            $schema->add('value', $i18nSchema, ...$validators);
        } else {
            $schema->add('value', $itemSchema, ...$validators);
        }

        return $schema;
    }

    protected function getTranslatableFileSchema(Field $field, array $fields): Schema
    {
        $subSchema = new Schema(list: true, title: $field->getLabel(), keepUnknown: true);

        foreach ($fields as $fieldName) {
            $subSchema->add($fieldName, 'text');
        }

        $schema = new Schema(title: $field->getLabel(), keepUnknown: true);

        foreach ($this->locales as $locale) {
            $schema->add($locale->id, $subSchema);
        }

        return $schema;
    }

    protected function getTranslatableSchema(Field $field, bool $required, array $validators): Schema
    {
        $defaultLocale = $this->locales->getDefault()->id;
        $validators = array_filter($validators, fn ($validator) => $validator !== 'required');
        $schema = new Schema(title: $field->getLabel(), keepUnknown: true);

        foreach ($this->locales as $locale) {
            $validators = [];

            if ($required && $locale->id === $defaultLocale) {
                $validators[] = 'required';
            }

            $schema->add($locale->id, 'text', ...$validators);
        }

        return $schema;
    }
}
