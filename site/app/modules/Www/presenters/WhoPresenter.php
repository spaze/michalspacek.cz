<?php
namespace App\WwwModule\Presenters;

use MichalSpacekCz\Talks;

/**
 * Who presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class WhoPresenter extends BasePresenter
{

	/** @var Talks */
	protected $talks;


	/**
	 * @param Talks $talks
	 */
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
