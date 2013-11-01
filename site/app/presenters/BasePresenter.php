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
	 * @var \MichalSpacekCz\TrainingDates
	 */
	protected $trainingDates;

	/**
	 * @var \MichalSpacekCz\TrainingMails
	 */
	protected $trainingMails;

	/**
	 * @var \MichalSpacekCz\Interviews
	 */
	protected $interviews;

	/**
	 * @var \MichalSpacekCz\UserManager
	 */
	protected $authenticator;

	/**
	 * @var \MichalSpacekCz\Embed
	 */
	protected $embed;

	/**
	 * @var \MichalSpacekCz\WebTracking
	 */
	protected $webTracking;


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
	 * @param \MichalSpacekCz\TrainingDates
	 */
	public function injectTrainingDates(\MichalSpacekCz\TrainingDates $trainingDates)
	{
		if ($this->trainingDates) {
			throw new \Nette\InvalidStateException('TrainingDates has already been set');
		}
		$this->trainingDates = $trainingDates;
	}


	/**
	 * @param \MichalSpacekCz\TrainingMails
	 */
	public function injectTrainingMails(\MichalSpacekCz\TrainingMails $trainingMails)
	{
		if ($this->trainingMails) {
			throw new \Nette\InvalidStateException('TrainingMails has already been set');
		}
		$this->trainingMails = $trainingMails;
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


	/**
	 * @param \MichalSpacekCz\Embed
	 */
	public function injectEmbed(\MichalSpacekCz\Embed $embed)
	{
		if ($this->embed) {
			throw new \Nette\InvalidStateException('Embed has already been set');
		}
		$this->embed = $embed;
	}


	/**
	 * @param \MichalSpacekCz\WebTracking
	 */
	public function injectWebTracking(\MichalSpacekCz\WebTracking $webTracking)
	{
		if ($this->webTracking) {
			throw new \Nette\InvalidStateException('WebTracking has already been set');
		}
		$this->webTracking = $webTracking;
	}


	protected function startup()
	{
		parent::startup();
		if ($this->authenticator->isForbidden()) {
			$this->forward('Forbidden:');
		}
	}


	public function beforeRender()
	{
		$this->template->trackingCode = $this->webTracking->isEnabled();
	}


	protected function createTemplate($class = null)
	{
		$template = parent::createTemplate($class);
		$template->registerHelperLoader(new \Nette\Callback(new \Bare\Next\Templating\Helpers($this->getContext()), 'loader'));
		return $template;
	}


}
