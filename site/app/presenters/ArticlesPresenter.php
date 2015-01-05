<?php
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
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Articles $articles
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\Articles $articles
	)
	{
		$this->articles = $articles;
		parent::__construct($translator);
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.articles');
		$this->template->articles  = $this->articles->getAll();
	}


}
