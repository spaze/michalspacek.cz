<?php
/**
 * Base class for all application presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

	/**
	 * @var \MichalSpacekCz\Articles
	 */
	protected $articles;

	/**
	 * @var \MichalSpacekCz\Talks
	 */
	protected $talks;

	/**
	 * @var \MichalSpacekCz\Trainings
	 */
	protected $trainings;

	/**
	 * @var \MichalSpacekCz\Interviews
	 */
	protected $interviews;


	/**
	 * @param \MichalSpacekCz\Articles
	 */
	public function injectArticles(\MichalSpacekCz\Articles $articles)
	{
		if ($this->articles) {
			throw new Nette\InvalidStateException('Articles has already been set');
		}
		$this->articles = $articles;
	}


	/**
	 * @param \MichalSpacekCz\Talks
	 */
	public function injectTalks(\MichalSpacekCz\Talks $talks)
	{
		if ($this->talks) {
			throw new Nette\InvalidStateException('Talks has already been set');
		}
		$this->talks = $talks;
	}


	/**
	 * @param \MichalSpacekCz\Trainings
	 */
	public function injectTrainings(\MichalSpacekCz\Trainings $trainings)
	{
		if ($this->trainings) {
			throw new Nette\InvalidStateException('Trainings has already been set');
		}
		$this->trainings = $trainings;
	}


	/**
	 * @param \MichalSpacekCz\Interviews
	 */
	public function injectInterviews(\MichalSpacekCz\Interviews $interviews)
	{
		if ($this->interviews) {
			throw new Nette\InvalidStateException('Interviews has already been set');
		}
		$this->interviews = $interviews;
	}


	public function beforeRender()
	{
		$parameters = $this->getContext()->getParameters();
		$this->template->debugMode = (isset($parameters['debugMode']) ? $parameters['debugMode'] : false);
	}


	protected function createTemplate($class = null)
	{
		$template = parent::createTemplate($class);
		$template->registerHelperLoader(new \Nette\Callback(new \Bare\Templating\Nette\Helpers($this->getContext()), 'loader'));
		return $template;
	}


}
