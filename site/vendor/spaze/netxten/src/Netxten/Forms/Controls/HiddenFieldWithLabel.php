<?php
declare(strict_types = 1);

namespace Netxten\Forms\Controls;

use InvalidArgumentException;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

/**
 * Hidden form control used to store a non-displayed value but with label and optional text
 *
 * @author Michal Špaček
 */
class HiddenFieldWithLabel extends BaseControl
{

	public function __construct(
		Html|string|null $label = null,
		Html|string|null $value = null,
		protected Html|string|null $text = null,
	) {
		parent::__construct($label);
		$this->control->type = 'hidden';
		$this->value = $value;
	}


	/**
	 * Generates control's HTML element.
	 *
	 * @return Html
	 */
	public function getControl(): Html
	{
		if (!$this->value instanceof Html && !is_string($this->value) && !is_null($this->value)) {
			throw new InvalidArgumentException("This shouldn't happen, unexpected type: " . gettype($this->value));
		}

		/** @var Html $control */
		$control = parent::getControl();
		$input = $control
			->value((string)$this->value)
			->data('nette-rules', null);

		$container = Html::el();
		if ($this->text !== null) {
			$container->addText($this->text);
		}
		$container->addHtml($input);
		return $container;
	}


	/**
	 * Generates label's HTML element.
	 *
	 * @param string|null $caption
	 * @return Html
	 */
	public function getLabel($caption = null): Html
	{
		/** @var Html $label */
		$label = parent::getLabel($caption);
		unset($label->for);
		return $label;
	}

}
