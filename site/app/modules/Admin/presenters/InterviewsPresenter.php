<?php
namespace App\AdminModule\Presenters;

/**
 * Interviews presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class InterviewsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Interviews */
	protected $interviews;

	/** @var \Nette\Database\Row */
	private $interview;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Interviews $interviews
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Interviews $interviews
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->interviews = $interviews;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.interviews');
		$this->template->interviews = $this->interviews->getAll();
	}


	public function actionInterview($param)
	{
		$this->interview = $this->interviews->getById($param);
		if (!$this->interview) {
			throw new \Nette\Application\BadRequestException("Interview id {$param} does not exist, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.interview', [strip_tags($this->interview->title)]);
		$this->template->interview = $this->interview;
	}


	protected function createComponentEditInterview($formName)
	{
		$form = new \MichalSpacekCz\Form\Interview($this, $formName);
		$form->setInterview($this->interview);
		$form->onSuccess[] = [$this, 'submittedEditInterview'];
		return $form;
	}


	public function submittedEditInterview(\MichalSpacekCz\Form\Interview $form, $values)
	{
		$this->interviews->update(
			$this->interview->interviewId,
			$values->action,
			$values->title,
			$values->description,
			$values->date,
			$values->href,
			$values->audioHref,
			$values->audioEmbed,
			$values->videoHref,
			$values->videoEmbed,
			$values->sourceName,
			$values->sourceHref
		);
		$this->flashMessage('Rozhovor upraven');
		$this->redirect('Interviews:');
	}


	protected function createComponentAddInterview($formName)
	{
		$form = new \MichalSpacekCz\Form\Interview($this, $formName);
		$form->onSuccess[] = [$this, 'submittedAddInterview'];
		return $form;
	}


	public function submittedAddInterview(\MichalSpacekCz\Form\Interview $form, $values)
	{
		$this->interviews->add(
			$values->action,
			$values->title,
			$values->description,
			$values->date,
			$values->href,
			$values->audioHref,
			$values->audioEmbed,
			$values->videoHref,
			$values->videoEmbed,
			$values->sourceName,
			$values->sourceHref
		);
		$this->flashMessage('Rozhovor přidán');
		$this->redirect('Interviews:');
	}

}
