<?php
namespace MichalSpacekCz\Notifier;

/**
 * php.vrana.cz notifier.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Vrana
{

	/**
	 * Training action to training id mapping.
	 *
	 * @var array
	 */
	protected $trainingMapping = array(
		'uvodDoPhp' => '4',
		'programovaniVPhp5' => '3',
		'bezpecnostPhpAplikaci' => '1',
		'vykonnostWebovychAplikaci' => '7',
	);

	/**
	 * The URL of the sign-up form action.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * A note in the training sign-up form.
	 *
	 * @var string
	 */
	protected $note;

	/**
	 * No-spam protection value.
	 *
	 * @var string
	 */
	protected $noSpam;

	/**
	 * Success marker.
	 *
	 * @var string
	 */
	protected $successMarker;


	/** @var string */
	public function setUrl($url)
	{
		$this->url = $url;
	}


	/** @var string */
	public function setNote($note)
	{
		$this->note = $note;
	}


	/** @var string */
	public function setNoSpam($noSpam)
	{
		$this->noSpam = $noSpam;
	}


	/** @var string */
	public function setSuccessMarker($successMarker)
	{
		$this->successMarker = $successMarker;
	}


	public function addTrainingApplication(\Nette\Database\Row $application)
	{
		$postdata = array(
			'jmeno'    => $application->name,
			'email'    => $application->email,
			'firma'    => '',
			'adresa'   => '',
			'mesto'    => '',
			'psc'      => '',
			'ico'      => '',
			'dic'      => '',
			'poznamka' => $this->note,
			'robot'    => $this->noSpam,
			'skoleni'  => $this->trainingMapping[$application->action],
			'termin'   => $application->trainingStart->format('Y-m-d'),
			'submit'   => 'Registrovat se',
		);
		$options = array(
			'http' => array(
				'method' => 'POST',  // must be UPPERCASE
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query($postdata),
			)
		);
		$context = stream_context_create($options);
		$result = file_get_contents($this->url, false, $context);
		if (strpos($result, $this->successMarker) === false) {
			\Nette\Diagnostics\Debugger::log(sprintf('Success marker "%s" not found in "%s"', $this->successMarker, $result), 'notifier');
			throw new \UnexpectedValueException('Success marker not found');
		}
	}


}