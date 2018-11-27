<?php
declare(strict_types = 1);

namespace Netxten\Forms\Controls;

use Nette\Utils\Html;

/**
 * Hidden form control used to store a non-displayed value but with label and optional text
 *
 * @author     Michal Špaček
 */
class HiddenFieldWithLabel extends \Nette\Forms\Controls\BaseControl
{

	/** @param  string field text */
	protected $text;


	/**
	 * @param string|Html|null $label
	 * @param string|Html|null $value
	 * @param string|Html|null $text 
	 */
	public function __construct($label = null, $value = null, $text = null)
	{
		parent::__construct($label);
		$this->control->type = 'hidden';
		$this->value = $value;
		$this->text = $text;
	}


	/**
	 * Generates control's HTML element.
	 *
	 * @return Html
	 */
	public function getControl(): Html
	{
		$input = parent::getControl()
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
	 * @param string|null
	 * @return Html|string
	 */
	public function getLabel($caption = null)
	{
		$label = parent::getLabel($caption);
		unset($label->for);
		return $label;
	}

}
