<?php
namespace App\AdminModule\Presenters;

/**
 * DryRun presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class DryRunPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\DryRun */
	protected $dryRun;


	/**
	 * @param \MichalSpacekCz\DryRun $dryRun
	 */
	public function __construct(\MichalSpacekCz\DryRun $dryRun)
	{
		$this->dryRun = $dryRun;
		parent::__construct();
	}


	public function actionEnable()
	{
		$this->dryRun->enable();
		$this->redirect('Homepage:');
	}


	public function actionDisable()
	{
		$this->dryRun->disable();
		$this->redirect('Homepage:');
	}

}
