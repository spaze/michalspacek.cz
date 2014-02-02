<?php
namespace Companies20\Form;

class MediaForm extends \Nette\Application\UI\Form
{


	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);
		$this->addText('url', 'URL:')
			->setRequired('Need URL');
		$this->addText('title', 'Title:')
			->setRequired('Need title');
		$this->addText('published', 'Date:')
			->setRequired('Need date of publishing');
		$field = new \Bare\Next\Forms\Controls\HiddenFieldWithLabel('Tags:');
		$field->setHtmlId('tags');
		$this->addComponent($field, 'tags');
		$this->addSubmit('submit', 'Add');
	}


}