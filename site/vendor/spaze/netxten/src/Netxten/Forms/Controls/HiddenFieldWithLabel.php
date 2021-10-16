<?php
declare(strict_types = 1);

namespace Netxten\Forms\Controls;

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
		/** @var Html $control */
		$control = parent::getControl();
		$input = $control
			->value($this->value === null ? '' : $this->value)
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
