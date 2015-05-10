<?php
namespace Companies20\Form;

class SearchForm extends \Nette\Application\UI\Form
{


	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);
		$field = new \Netxten\Forms\Controls\HiddenFieldWithLabel('Search:');
		$field->setHtmlId('search');
		$this->addComponent($field, 'search');
		$this->addSubmit('submit', 'Search');
	}


	public function setTags(array $tags)
	{
		$this->setDefaults(['search' => implode(' ', $tags)]);
		return $this;
	}


}