<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

/**
 * Projects presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ProjectsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(\MichalSpacekCz\Formatter\Texy $texyFormatter)
	{
		$this->texyFormatter = $texyFormatter;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.projects');
	}

}
