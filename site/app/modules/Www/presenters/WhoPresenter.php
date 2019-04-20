<?php
namespace App\WwwModule\Presenters;

use MichalSpacekCz\Talks;

class WhoPresenter extends BasePresenter
{

	/** @var Talks */
	protected $talks;


	public function __construct(Talks $talks)
	{
		$this->talks = $talks;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.who');
		$this->template->pageHeader = $this->translator->translate('messages.header.who');
		$this->template->talksApproxCount = $this->talks->getApproxCount();
	}


}
