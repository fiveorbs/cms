<?php

declare(strict_types=1);

namespace FiveOrbs\Cms\Field;

use FiveOrbs\Cms\Value\Date as DateValue;

class Date extends Field
{
	public function value(): DateValue
	{
		return new DateValue($this->node, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getSimpleStructure('date', $value);
	}
}
