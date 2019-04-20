<?php
namespace App\WwwModule\Presenters;

use MichalSpacekCz\Articles;

/**
 * Articles presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ArticlesPresenter extends BasePresenter
{

	/** @var Articles */
	protected $articles;


	/**
	 * @param Articles $articles
	 */
	public function __construct(Articles $articles)
	{
		$this->articles = $articles;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.articles');
		$this->template->articles  = $this->articles->getAll();
	}


}
