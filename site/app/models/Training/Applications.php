<?php
namespace MichalSpacekCz\Training;

use Nette\Application\UI\Form;

/**
 * Training applications model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Applications
{

	const SOURCE_MICHAL_SPACEK  = 'michal-spacek';
	const SOURCE_JAKUB_VRANA  = 'jakub-vrana';

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var Trainings */
	protected $trainings;

	/** @var Dates */
	protected $trainingDates;

	/** @var Statuses */
	protected $trainingStatuses;

	/** @var \MichalSpacekCz\Encryption\Email */
	protected $emailEncryption;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;

	/** @var \MichalSpacekCz\Training\Resolver\Vrana */
	protected $vranaResolver;

	/** @var \MichalSpacekCz\WebTracking */
	protected $webTracking;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var array */
	private $byDate = array();


	/**
	 * @param \Nette\Database\Context $context
	 * @param Trainings $trainings
	 * @param Dates $trainingDates
	 * @param Statuses $trainingStatuses
	 * @param \MichalSpacekCz\Encryption\Email $emailEncryption
	 * @param \MichalSpacekCz\Vat $vat
	 * @param \MichalSpacekCz\Training\Resolver\Vrana $vranaResolver
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(
		\Nette\Database\Context $context,
		Trainings $trainings,
		Dates $trainingDates,
		Statuses $trainingStatuses,
		\MichalSpacekCz\Encryption\Email $emailEncryption,
		\MichalSpacekCz\Vat $vat,
		\MichalSpacekCz\Training\Resolver\Vrana $vranaResolver,
		\MichalSpacekCz\WebTracking $webTracking,
		\Nette\Localization\ITranslator $translator
	)
	{
		$this->database = $context;
		$this->trainings = $trainings;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->emailEncryption = $emailEncryption;
		$this->vat = $vat;
		$this->vranaResolver = $vranaResolver;
		$this->webTracking = $webTracking;
		$this->translator = $translator;
	}


	public function getByStatus($status)
	{
		$result = $this->database->fetchAll(
			'SELECT
				a.id_application AS id,
				a.name,
				a.email,
				a.familiar,
				a.company,
				s.status,
				a.status_time AS statusTime,
				d.id_date AS dateId,
				t.id_training AS trainingId,
				d.start AS trainingStart,
				d.end AS trainingEnd,
				d.public AS publicDate,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				v.action AS venueAction,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.discount,
				a.invoice_id AS invoiceId,
				a.paid,
				a.access_token AS accessToken
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				s.status = ?
			ORDER BY
				d.start, a.status_time',
			$status
		);

		if ($result) {
			foreach ($result as $row) {
				if ($row->email) {
					$row->email = $this->emailEncryption->decrypt($row->email);
				}
				$row->training = $this->trainings->getById($row->trainingId);
			}
		}

		return $result;
	}


	public function getByDate($dateId)
	{
		if (!isset($this->byDate[$dateId])) {
			$this->byDate[$dateId] = $this->database->fetchAll(
				'SELECT
					a.id_application AS id,
					a.name,
					a.email,
					a.company,
					s.status,
					a.status_time AS statusTime,
					a.note,
					a.price,
					a.vat_rate AS vatRate,
					a.price_vat AS priceVat,
					a.invoice_id AS invoiceId,
					a.paid,
					sr.name AS sourceName
				FROM
					training_applications a
					JOIN training_application_status s ON a.key_status = s.id_status
					JOIN training_application_sources sr ON a.key_source = sr.id_source
				WHERE
					key_date = ?',
				$dateId
			);
			if ($this->byDate[$dateId]) {
				$discardedStatuses = $this->trainingStatuses->getDiscardedStatuses();
				$attendedStatuses = $this->trainingStatuses->getAttendedStatuses();
				foreach ($this->byDate[$dateId] as $row) {
					if ($row->email) {
						$row->email = $this->emailEncryption->decrypt($row->email);
					}
					$row->sourceNameInitials = $this->getSourceNameInitials($row->sourceName);
					$row->discarded = in_array($row->status, $discardedStatuses);
					$row->attended = in_array($row->status, $attendedStatuses);
				}
			}
		}
		return $this->byDate[$dateId];
	}


	public function getValidByDate($dateId)
	{
		$discardedStatuses = $this->trainingStatuses->getDiscardedStatuses();
		return array_filter($this->getByDate($dateId), function($value) use ($discardedStatuses) {
			return !in_array($value->status, $discardedStatuses);
		});
	}


	public function getValidUnpaidByDate($dateId)
	{
		return array_filter($this->getValidByDate($dateId), function($value) {
			return (isset($value->invoiceId) && !isset($value->paid));
		});
	}


	public function getValidUnpaidCount()
	{
		$result = $this->database->fetchField(
			'SELECT
				COUNT(1)
			FROM
				training_applications
			WHERE
				key_status NOT IN(?)
				AND invoice_id IS NOT NULL
				AND paid IS NULL',
			array_keys($this->trainingStatuses->getDiscardedStatuses())
		);
		return $result;
	}


	/**
	 * Get canceled but already paid applications by date id.
	 *
	 * @param integer $dateId
	 * @return \Nette\Database\Row[]
	 */
	public function getCanceledPaidByDate($dateId)
	{
		$canceledStatus = $this->trainingStatuses->getCanceledStatus();
		return array_filter($this->getByDate($dateId), function($value) use ($canceledStatus) {
			return ($value->paid && in_array($value->status, $canceledStatus));
		});
	}


	private function insertData($data)
	{
		$data['access_token'] = $this->generateAccessCode();
		try {
			$this->database->query('INSERT INTO training_applications', $data);
		} catch (\PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == \Nette\Database\Drivers\MySqlDriver::ERROR_DUPLICATE_ENTRY) {
					// regenerate the access code and try harder this time
					\Tracy\Debugger::log("Regenerating access token, {$data['access_token']} already exists. Full data: " . implode(', ', $data));
					return $this->insertData($data);
				}
			}
			throw $e;
		}
		return $data['access_token'];
	}


	public function addInvitation(\Nette\Database\Row $training, $dateId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note)
	{
		return $this->insertApplication(
			$training,
			$dateId,
			$name,
			$email,
			$company,
			$street,
			$city,
			$zip,
			$country,
			$companyId,
			$companyTaxId,
			$note,
			Statuses::STATUS_TENTATIVE,
			$this->resolveSource($note)
		);
	}


	public function addApplication(\Nette\Database\Row $training, $dateId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note)
	{
		return $this->insertApplication(
			$training,
			$dateId,
			$name,
			$email,
			$company,
			$street,
			$city,
			$zip,
			$country,
			$companyId,
			$companyTaxId,
			$note,
			Statuses::STATUS_SIGNED_UP,
			$this->resolveSource($note)
		);
	}


	/**
	 * Add preliminary invitation, to a training with no date set.
	 *
	 * @param \Nette\Database\Row $training
	 * @param string $name
	 * @param string $email
	 * @return integer application id
	 */
	public function addPreliminaryInvitation(\Nette\Database\Row $training, $name, $email)
	{
		return $this->insertApplication($training, null, $name, $email, null, null, null, null, null, null, null, null, Statuses::STATUS_TENTATIVE, $this->resolveSource());
	}


	public function insertApplication(\Nette\Database\Row $training, $dateId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note, $status, $source, $date = null)
	{
		if (!in_array($status, $this->trainingStatuses->getInitialStatuses())) {
			throw new \RuntimeException("Invalid initial status {$status}");
		}

		$statusId = $this->trainingStatuses->getStatusId(Statuses::STATUS_CREATED);
		$datetime = new \DateTime($date);

		list($price, $vatRate, $priceVat, $discount) = $this->resolvePriceDiscountVat($training, $status, $source, $note);

		$data = array(
			'key_date'             => $dateId,
			'name'                 => $name,
			'email'                => $this->emailEncryption->encrypt($email),
			'company'              => $company,
			'street'               => $street,
			'city'                 => $city,
			'zip'                  => $zip,
			'country'              => $country,
			'company_id'           => $companyId,
			'company_tax_id'       => $companyTaxId,
			'note'                 => $note,
			'key_status'           => $statusId,
			'status_time'          => $datetime,
			'status_time_timezone' => $datetime->getTimezone()->getName(),
			'key_source'           => $this->getTrainingApplicationSource($source),
			'price'                => $price,
			'vat_rate'             => $vatRate,
			'price_vat'            => $priceVat,
			'discount'             => $discount,
		);
		if ($dateId === null) {
			$data['key_training'] = $training->trainingId;
		}
		return $this->trainingStatuses->updateStatusCallback(function () use ($data) {
			$this->insertData($data);
			return $this->database->getInsertId();
		}, $status, $date);
	}


	public function updateApplication(\Nette\Database\Row $training, $applicationId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note)
	{
		$this->trainingStatuses->updateStatusReturnCallback($applicationId, Statuses::STATUS_SIGNED_UP, null, function () use ($training, $applicationId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note) {
			$source = $this->getSourceByApplicationId($applicationId)->alias;
			list($price, $vatRate, $priceVat, $discount) = $this->resolvePriceDiscountVat($training, Statuses::STATUS_SIGNED_UP, $source, $note);
			$this->database->query(
				'UPDATE training_applications SET ? WHERE id_application = ?',
				array(
					'name'           => $name,
					'email'          => $this->emailEncryption->encrypt($email),
					'company'        => $company,
					'street'         => $street,
					'city'           => $city,
					'zip'            => $zip,
					'country'        => $country,
					'company_id'     => $companyId,
					'company_tax_id' => $companyTaxId,
					'note'           => $note,
					'price'          => $price,
					'vat_rate'       => $vatRate,
					'price_vat'      => $priceVat,
					'discount'       => $discount,
				),
				$applicationId
			);
		});
		return $applicationId;
	}


	public function updateApplicationData($applicationId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note, $source, $price = null, $vatRate = null, $priceVat = null, $discount = null, $invoiceId = null, $paid = null, $familiar = false, $dateId = false)
	{
		if ($paid) {
			$paid = new \DateTime($paid);
		}

		$data = array(
			'name'           => $name,
			'email'          => ($email ? $this->emailEncryption->encrypt($email) : null),
			'company'        => $company,
			'familiar'       => $familiar,
			'street'         => $street,
			'city'           => $city,
			'zip'            => $zip,
			'country'        => $country,
			'company_id'     => $companyId,
			'company_tax_id' => $companyTaxId,
			'note'           => $note,
			'key_source'     => $this->getTrainingApplicationSource($source),
			'price'          => ($price || $discount ? $price : null),
			'vat_rate'       => ($vatRate ?: null),
			'price_vat'      => ($priceVat ?: null),
			'discount'       => ($discount ?: null),
			'invoice_id'     => ($invoiceId ?: null),
			'paid'           => ($paid ?: null),
			'paid_timezone'  => ($paid ? $paid->getTimezone()->getName() : null),
		);
		if ($dateId !== false) {
			$data['key_date'] = $dateId;
		}
		$this->database->query('UPDATE training_applications SET ? WHERE id_application = ?', $data, $applicationId);
	}


	public function updateApplicationInvoiceData($applicationId, $invoiceId)
	{
		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			array(
				'invoice_id' => ($invoiceId ?: null),
			),
			$applicationId
		);
	}


	/**
	 * Resolves price, VAT rate, discount.
	 *
	 * @param \Nette\Database\Row $training
	 * @param string $status
	 * @param integer $source
	 * @param string $note
	 * @return array with price, VAT rate, price inluding VAT, discount
	 */
	private function resolvePriceDiscountVat(\Nette\Database\Row $training, $status, $source, $note)
	{
		if (in_array($status, [Statuses::STATUS_NON_PUBLIC_TRAINING, Statuses::STATUS_TENTATIVE])) {
			$price = null;
			$discount = null;
		} elseif (stripos($note, 'student') === false) {
			$price = $training->price;
			$discount = null;
		} else {
			$price = $training->price * (100 - $training->studentDiscount) / 100;
			$discount = $training->studentDiscount;
		}

		if ($price === null) {
			$vatRate = null;
			$priceVat = null;
		} else {
			$vatRate = $this->vat->getRate();
			$priceVat = $this->vat->addVat($price);
		}

		return [$price, $vatRate, $priceVat, $discount];
	}


	/**
	 * Resolves training source.
	 *
	 * @param string|null $note
	 * @return string
	 */
	private function resolveSource($note = null)
	{
		if ($note && $this->vranaResolver->isTrainingApplicationOwner($note)) {
			$source = self::SOURCE_JAKUB_VRANA;
		} else {
			$source = self::SOURCE_MICHAL_SPACEK;
		}
		return $source;
	}


	/**
	 * Get source for application by id
	 * @param integer $id application id
	 * @return \Nette\Database\Row
	 */
	private function getSourceByApplicationId($id)
	{
		return $this->database->fetch(
			'SELECT
				s.alias
			FROM
				training_applications a
				JOIN training_application_sources s ON a.key_source = s.id_source
			WHERE
				a.id_application = ?',
			$id
		);
	}


	private function generateAccessCode()
	{
		return \Nette\Utils\Random::generate(mt_rand(32, 48), '0-9a-zA-Z');
	}


	public function getApplicationById($id)
	{
		$result = $this->database->fetch(
			'SELECT
				ua.action AS trainingAction,
				d.id_date AS dateId,
				d.start AS trainingStart,
				d.end AS trainingEnd,
				a.id_application AS applicationId,
				s.status,
				a.status_time AS statusTime,
				a.name,
				a.email,
				a.familiar,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.country,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.discount,
				a.invoice_id AS invoiceId,
				a.paid,
				a.access_token AS accessToken,
				sr.alias AS sourceAlias,
				sr.name AS sourceName
			FROM
				training_applications a
				LEFT JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON (d.key_training = t.id_training OR a.key_training = t.id_training)
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_application_sources sr ON a.key_source = sr.id_source
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.id_application = ?
				AND l.language = ?',
			$id,
			$this->translator->getDefaultLocale()
		);

		if ($result->email) {
			$result->email = $this->emailEncryption->decrypt($result->email);
		}

		return $result;
	}


	public function getPreliminary()
	{
		$trainings = array();
		$result = $this->database->fetchAll(
			'SELECT
				t.id_training AS idTraining,
				ua.action,
				t.name
			FROM trainings t
				JOIN training_applications a ON a.key_training = t.id_training
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.key_date IS NULL
				AND l.language = ?',
			$this->translator->getDefaultLocale()
		);
		foreach ($result as $row) {
			$row->name = $this->translator->translate($row->name);
			$row->applications = array();
			$trainings[$row->idTraining] = $row;
		}

		$applications = $this->database->fetchAll(
			'SELECT
				a.id_application AS id,
				a.key_training AS idTraining,
				a.name,
				a.email,
				a.company,
				s.status,
				a.status_time AS statusTime,
				a.note,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.invoice_id AS invoiceId,
				a.paid,
				sr.name AS sourceName
			FROM
				training_applications a
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_application_sources sr ON a.key_source = sr.id_source
			WHERE
				a.key_date IS NULL'
		);

		if ($applications) {
			foreach ($applications as $row) {
				if ($row->email) {
					$row->email = $this->emailEncryption->decrypt($row->email);
				}
				$row->sourceNameInitials = $this->getSourceNameInitials($row->sourceName);
				$trainings[$row->idTraining]->applications[] = $row;
			}
		}

		return $trainings;
	}


	public function getPreliminaryCounts()
	{
		$upcoming = array_keys($this->trainingDates->getPublicUpcoming());

		$total = $dateSet = 0;
		foreach ($this->getPreliminary() as $training) {
			if (in_array($training->action, $upcoming)) {
				$dateSet += count($training->applications);
			}
			$total += count($training->applications);
		}

		return array($total, $dateSet);
	}


	public function getApplicationByToken($token)
	{
		$result = $this->database->fetch(
			'SELECT
				ua.action AS trainingAction,
				d.id_date AS dateId,
				a.id_application AS applicationId,
				a.name,
				a.email,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.country,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.access_token = ?
				AND l.language = ?',
			$token,
			$this->translator->getDefaultLocale()
		);

		if ($result->email) {
			$result->email = $this->emailEncryption->decrypt($result->email);
		}

		return $result;
	}


	public function setPaidDate($invoiceId, $paid)
	{
		if ($paid) {
			$paid = new \DateTime($paid);
		}

		$result = $this->database->query(
			'UPDATE training_applications SET ? WHERE invoice_id = ?',
			array(
				'paid'           => ($paid ?: null),
				'paid_timezone'  => ($paid ? $paid->getTimezone()->getName() : null),
			),
			$invoiceId
		);
		return $result->getRowCount();
	}


	private function getTrainingApplicationSource($source)
	{
		return $this->database->fetchField('SELECT id_source FROM training_application_sources WHERE alias = ?', $source);
	}


	public function getTrainingApplicationSources()
	{
		return $this->database->fetchAll(
			'SELECT
				id_source AS sourceId,
				alias,
				name
			FROM
				training_application_sources'
		);
	}


	public function setAccessTokenUsed(\Nette\Database\Row $application)
	{
		if ($application->status != Statuses::STATUS_ACCESS_TOKEN_USED && $this->webTracking->isEnabled()) {
			$this->trainingStatuses->updateStatus($application->applicationId, Statuses::STATUS_ACCESS_TOKEN_USED);
		}
	}


	/**
	 * Shorten source name.
	 *
	 * Removes Czech private limited company designation, if any, and uses only initials from the original name.
	 * Example:
	 *   Michal Špaček -> MŠ
	 *   Internet Info, s.r.o. -> II
	 *
	 * @param string $name
	 * @return string
	 */
	private function getSourceNameInitials($name)
	{
		$name = preg_replace('/,? s\.r\.o./', '', $name);
		preg_match_all('/(?<=\s|\b)\pL/u', $name, $matches);
		return strtoupper(implode('', current($matches)));
	}


	/**
	 * Set familiar flag for the application.
	 *
	 * @param integer $applicationId
	 */
	public function setFamiliar($applicationId)
	{
		$this->database->query('UPDATE training_applications SET familiar = TRUE WHERE id_application = ?', $applicationId);
	}


}
