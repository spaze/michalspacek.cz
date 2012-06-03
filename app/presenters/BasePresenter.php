<?php
/**
 * Base class for all application presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{
	const TRAINING_PHP = 1;
	const TRAINING_PHP5 = 2;
	const TRAINING_SECURITY = 3;
	const TRAINING_PERFORMANCE = 4;

	/**
	 * Training id to action mapping.
	 *
	 * @var array
	 */
	protected $trainings = array(
		self::TRAINING_PHP => array(
			'action' => 'uvodDoPhp',
			'name' => 'Úvod do PHP',
			'date' => null,  // or YYYY-MM-DD
			'tentative' => 'září 2012',  // or null
			'placeName' => 'Internet Info',
			'placeUrl' => 'http://www.skolici-mistnost.cz/',
			'placeAddress' => 'Milady Horákové 109/116, Praha 6',
			'originalUrl' => 'http://php.vrana.cz/skoleni-uvod-do-php.php',
		),
		self::TRAINING_PHP5 => array(
			'action' => 'programovaniVPhp5',
			'name' => 'Programování v PHP 5',
			'date' => null,  // or YYYY-MM-DD
			'tentative' => 'září 2012',  // or null
			'placeName' => 'Internet Info',
			'placeUrl' => 'http://www.skolici-mistnost.cz/',
			'placeAddress' => 'Milady Horákové 109/116, Praha 6',
			'originalUrl' => 'http://php.vrana.cz/skoleni-programovani-v-php-5.php',
		),
		self::TRAINING_SECURITY => array(
			'action' => 'bezpecnostPhpAplikaci',
			'name' => 'Bezpečnost PHP aplikací',
			'date' => null,  // or YYYY-MM-DD
			'tentative' => 'září 2012',  // or null
			'placeName' => 'Classic 7, budova N',
			'placeUrl' => 'http://www.classic7.cz/cs/faze-1/lokalita',
			'placeAddress' => 'Jankovcova 1037/49, Praha 7',
			'originalUrl' => 'http://php.vrana.cz/skoleni-bezpecnost-php-aplikaci.php',
		),
		self::TRAINING_PERFORMANCE => array(
			'action' => 'vykonnostWebovychAplikaci',
			'name' => 'Výkonnost webových aplikací',
			'date' => null,  // or YYYY-MM-DD
			'tentative' => 'září 2012',  // or null
			'placeName' => 'Classic 7, budova N',
			'placeUrl' => 'http://www.classic7.cz/cs/faze-1/lokalita',
			'placeAddress' => 'Jankovcova 1037/49, Praha 7',
			'originalUrl' => 'http://php.vrana.cz/skoleni-vykonnost-webovych-aplikaci.php',
		),
	);

	private function getTrainings()
	{
		$trainings = array();
		foreach ($this->trainings as $value) {
			$trainings[] = array(
				'action' => $value['action'],
				'name' => $value['name'],
				'date' => $value['date'],
				'tentative' => $value['tentative'],
			);
		}

		return $trainings;
	}

	public function beforeRender()
	{
		$this->template->trainings = $this->getTrainings();
		$this->template->debugMode = $this->context->parameters['debugMode'];
	}
}
