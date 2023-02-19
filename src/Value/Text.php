<?php

declare(strict_types=1);

namespace Conia\Core\Value;

class Text extends Value
{
    protected string $value;

    public function __toString(): string
    {
        return htmlspecialchars($this->unwrap());
    }

    public function unwrap(): string
    {
        if (isset($this->value)) {
            return $this->value;
        }

        if ($this->translate) {
            $locale = $this->locale;

            while ($locale) {
                $value = $this->data['value'][$locale->id] ?? null;

                if ($value) {
                    $this->value = $value;

                    return $value;
                }

                $locale = $locale->fallback();
            }

            $this->value = '';

            return '';
        }

        $this->value = isset($this->data['value'][$this->defaultLocale->id]) ?
            $this->data['value'][$this->defaultLocale->id] : '';

        return $this->value;
    }

    public function strip(array|string|null $allowed = null): string
    {
        /**
         * As of now (early 2023), psalm does not support the
         * type array as arguments to strip_tags's $allowed_tags.
         *
         * @psalm-suppress PossiblyInvalidArgument
         */
        return strip_tags((string)$this->unwrap(), $allowed);
    }

    public function json(): mixed
    {
        return $this->unwrap();
    }

    public function isset(): bool
    {
        return $this->unwrap() ?? null ? true : false;
    }
}
