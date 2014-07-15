<?php
namespace AdminModule;

/**
 * Tracking presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrackingPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\WebTracking */
	protected $webTracking;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\WebTracking $webTracking
	)
	{
		$this->webTracking = $webTracking;
		parent::__construct($translator);
	}


	public function actionEnable()
	{
		$this->webTracking->enable();
		$this->redirect('Homepage:');
	}


	public function actionDisable()
	{
		$this->webTracking->disable();
		$this->redirect('Homepage:');
	}


}
