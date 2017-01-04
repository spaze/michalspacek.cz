<?php
namespace App\Presenters;

/**
 * Articles presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ArticlesPresenter extends BasePresenter
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
		$this->template->pageTitle = $this->translator->translate('messages.title.articles');
		$this->template->articles  = $this->articles->getAll();
	}


}
