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
	 * @var \MichalSpacekCz\Files
	 */
	protected $files;

	/**
	 * @var \MichalSpacekCz\Talks
	 */
	protected $talks;

	/**
	 * @var \MichalSpacekCz\Trainings
	 */
	protected $trainings;

	/**
	 * @var \MichalSpacekCz\TrainingApplications
	 */
	protected $trainingApplications;

	/**
	 * @var \MichalSpacekCz\Interviews
	 */
	protected $interviews;

	/**
	 * @var \MichalSpacekCz\UserManager
	 */
	protected $authenticator;


	/**
	 * @param \MichalSpacekCz\Articles
	 */
	public function injectArticles(\MichalSpacekCz\Articles $articles)
	{
		if ($this->articles) {
			throw new \Nette\InvalidStateException('Articles has already been set');
		}
		$this->articles = $articles;
	}


	/**
	 * @param \MichalSpacekCz\Files
	 */
	public function injectFiles(\MichalSpacekCz\Files $files)
	{
		if ($this->files) {
			throw new \Nette\InvalidStateException('Files has already been set');
		}
		$this->files = $files;
	}


	/**
	 * @param \MichalSpacekCz\Talks
	 */
	public function injectTalks(\MichalSpacekCz\Talks $talks)
	{
		if ($this->talks) {
			throw new \Nette\InvalidStateException('Talks has already been set');
		}
		$this->talks = $talks;
	}


	/**
	 * @param \MichalSpacekCz\Trainings
	 */
	public function injectTrainings(\MichalSpacekCz\Trainings $trainings)
	{
		if ($this->trainings) {
			throw new \Nette\InvalidStateException('Trainings has already been set');
		}
		$this->trainings = $trainings;
	}


	/**
	 * @param \MichalSpacekCz\TrainingApplications
	 */
	public function injectTrainingApplications(\MichalSpacekCz\TrainingApplications $trainingApplications)
	{
		if ($this->trainingApplications) {
			throw new \Nette\InvalidStateException('TrainingApplications has already been set');
		}
		$this->trainingApplications = $trainingApplications;
	}


	/**
	 * @param \MichalSpacekCz\Interviews
	 */
	public function injectInterviews(\MichalSpacekCz\Interviews $interviews)
	{
		if ($this->interviews) {
			throw new \Nette\InvalidStateException('Interviews has already been set');
		}
		$this->interviews = $interviews;
	}


	/**
	 * @param \MichalSpacekCz\UserManager
	 */
	public function injectAuthenticator(\MichalSpacekCz\UserManager $userManager)
	{
		if ($this->authenticator) {
			throw new \Nette\InvalidStateException('Authenticator has already been set');
		}
		$this->authenticator = $userManager;
	}


	protected function isForbidden()
	{
		return false;
	}


	protected function startup()
	{
		parent::startup();
		if ($this->isForbidden()) {
			$this->forward('Forbidden:');
		}
	}


	public function beforeRender()
	{
		$parameters = $this->getContext()->getParameters();
		$this->template->debugMode = (isset($parameters['debugMode']) ? $parameters['debugMode'] : false);
	}


	protected function createTemplate($class = null)
	{
		$template = parent::createTemplate($class);
		$template->registerHelperLoader(new \Nette\Callback(new \Bare\Next\Templating\Helpers($this->getContext()), 'loader'));
		return $template;
	}


	public function getSlidesEmbedType($href)
	{
		$type = false;

		switch (parse_url($href, PHP_URL_HOST)) {
			case 'www.slideshare.net':
				$type = 'slideshare';
				break;
			case 'speakerdeck.com':
				$type = 'speakerdeck';
				break;
		}

		return $type;
	}


	public function getVideoEmbedType($href)
	{
		$type = false;

		switch (parse_url($href, PHP_URL_HOST)) {
			case 'www.youtube.com':
				$type = 'youtube';
				break;
			case 'vimeo.com':
				$type = 'vimeo';
				break;
		}

		return $type;
	}


}
