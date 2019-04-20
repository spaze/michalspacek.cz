<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Formatter\Texy;

/**
 * Projects presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ProjectsPresenter extends BasePresenter
{

	/** @var Texy */
	protected $texyFormatter;


	/**
	 * @param Texy $texyFormatter
	 */
	public function __construct(Texy $texyFormatter)
	{
		$this->texyFormatter = $texyFormatter;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.projects');
	}

}
