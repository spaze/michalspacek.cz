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
			'pastTrainings' => array(
				'spacek' => array(
					'2012-05-09',
					'2011-12-07',
				),
				'vrana' => array(
					'2011-09-07', '2011-04-20',
					'2010-12-01', '2010-03-02',
					'2009-12-01', '2009-09-14', '2009-06-25', '2009-04-22', '2009-01-20',
					'2008-12-02', '2008-10-13', '2008-02-29',
					'2007-10-25', '2007-02-26',
				),
			),
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
			'pastTrainings' => array(
				'spacek' => array(
					'2011-05-10',
					'2011-12-08',
				),
				'vrana' => array(
					'2011-09-08', '2011-04-21',
					'2010-12-02', '2010-06-08', '2010-03-03',
					'2009-12-02', '2009-09-29', '2009-09-15', '2009-06-26', '2009-04-23', '2009-01-21',
					'2008-12-03', '2008-10-14', '2008-04-08',
					'2007-10-26',
					'2006-11-16', '2006-06-12',
				),
			),
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
			'pastTrainings' => array(
				'spacek' => array(
					'2011-05-11',
					'2011-12-09',
				),
				'vrana' => array(
					'2011-09-16', '2011-09-05', '2011-04-29',
					'2010-12-09', '2010-10-08', '2010-06-11', '2010-03-12', '2010-03-09',
					'2009-12-08', '2009-09-17', '2009-06-08', '2009-03-12', '2009-03-10',
					'2008-12-08', '2008-10-21', '2008-06-24', '2008-02-28', '2008-02-25',
					'2007-10-29', '2007-10-23', '2007-06-26', '2007-04-16',
					'2006-10-27', '2006-06-22', '2006-04-25',
				),
			),
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
			'pastTrainings' => array(
				'spacek' => array(
					'2011-05-14',
				),
				'vrana' => array(
					'2011-09-14', '2011-04-27',
					'2010-12-07', '2010-03-10',
					'2009-09-21', '2009-03-11',
					'2008-12-09', '2008-10-22', '2008-06-27',
				),
			),
		),
	);

	public function beforeRender()
	{
		$this->template->debugMode = $this->context->parameters['debugMode'];
	}

	protected function createTemplate($class = null)
	{
		$template = parent::createTemplate($class);
		$template->registerHelperLoader(callback(new \Bare\Nette\Templating\Helpers($this->getContext()), 'loader'));
		return $template;
	}
}
