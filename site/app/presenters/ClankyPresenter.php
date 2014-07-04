<?php
/**
 * Články presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ClankyPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Articles */
	protected $articles;


	/**
	 * @param \MichalSpacekCz\Articles $articles
	 */
	public function __construct(\MichalSpacekCz\Articles $articles)
	{
		$this->articles = $articles;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle = 'Články';
		$this->template->articles  = $this->articles->getAll();
	}


}
