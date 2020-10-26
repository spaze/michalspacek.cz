<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Formatter\Texy;

class ProjectsPresenter extends BasePresenter
{

	/** @var Texy */
	protected $texyFormatter;


	public function __construct(Texy $texyFormatter)
	{
		$this->texyFormatter = $texyFormatter;
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.projects');
	}

}
