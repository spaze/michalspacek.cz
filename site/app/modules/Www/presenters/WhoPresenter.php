<?php
namespace App\WwwModule\Presenters;

/**
 * Who presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class WhoPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Talks */
	protected $talks;


	/**
	 * @param \MichalSpacekCz\Talks $talks
	 */
	public function __construct(\MichalSpacekCz\Talks $talks)
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
