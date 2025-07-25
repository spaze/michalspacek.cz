<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Forms\Controls;

use Nette;
use Nette\Forms\Form;
use Stringable;
use function in_array, is_scalar, max, min;


/**
 * Single line text input control.
 */
class TextInput extends TextBase
{
	public function __construct(string|Stringable|null $label = null, ?int $maxLength = null)
	{
		parent::__construct($label);
		$this->control->maxlength = $maxLength;
		$this->setOption('type', 'text');
	}


	public function loadHttpData(): void
	{
		$this->setValue($this->getHttpData(Form::DataLine));
	}


	/**
	 * Changes control's type attribute.
	 */
	public function setHtmlType(string $type): static
	{
		$this->control->type = $type;
		return $this;
	}


	/**
	 * @deprecated  use setHtmlType()
	 */
	public function setType(string $type): static
	{
		return $this->setHtmlType($type);
	}


	public function getControl(): Nette\Utils\Html
	{
		return parent::getControl()->addAttributes([
			'value' => $this->control->type === 'password' ? $this->control->value : $this->getRenderedValue(),
			'type' => $this->control->type ?: 'text',
		]);
	}


	/** @return static */
	public function addRule(
		callable|string $validator,
		string|Stringable|null $errorMessage = null,
		mixed $arg = null,
	) {
		foreach ($this->getRules() as $rule) {
			if (!$rule->canExport() && !$rule->branch) {
				return parent::addRule($validator, $errorMessage, $arg);
			}
		}

		if ($this->control->type === null && in_array($validator, [Form::Email, Form::URL, Form::Integer], true)) {
			$types = [Form::Email => 'email', Form::URL => 'url', Form::Integer => 'number'];
			$this->control->type = $types[$validator];

		} elseif (
			in_array($validator, [Form::Min, Form::Max, Form::Range], true)
			&& in_array($this->control->type, ['number', 'range', 'datetime-local', 'datetime', 'date', 'month', 'week', 'time'], true)
		) {
			if ($validator === Form::Min) {
				$range = [$arg, null];
			} elseif ($validator === Form::Max) {
				$range = [null, $arg];
			} else {
				$range = $arg;
			}

			if (isset($range[0]) && is_scalar($range[0])) {
				$this->control->min = isset($this->control->min)
					? max($this->control->min, $range[0])
					: $range[0];
			}

			if (isset($range[1]) && is_scalar($range[1])) {
				$this->control->max = isset($this->control->max)
					? min($this->control->max, $range[1])
					: $range[1];
			}

		} elseif (
			$validator === Form::Pattern
			&& is_scalar($arg)
			&& in_array($this->control->type, [null, 'text', 'search', 'tel', 'url', 'email', 'password'], true)
		) {
			$this->control->pattern = $arg;
		}

		return parent::addRule($validator, $errorMessage, $arg);
	}
}
