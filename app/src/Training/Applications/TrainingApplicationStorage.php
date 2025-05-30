<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Exceptions\CannotUpdateTrainingApplicationStatusException;
use MichalSpacekCz\Training\Price;
use MichalSpacekCz\Training\Prices;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Random;
use ParagonIE\Halite\Alerts\HaliteAlert;
use RuntimeException;
use SodiumException;
use Spaze\Encryption\SymmetricKeyEncryption;
use Tracy\Debugger;

final readonly class TrainingApplicationStorage
{

	public function __construct(
		private Explorer $database,
		private TrainingApplicationStatuses $trainingApplicationStatuses,
		private TrainingApplicationSources $trainingApplicationSources,
		private SymmetricKeyEncryption $emailEncryption,
		private Prices $prices,
	) {
	}


	/**
	 * @throws CannotUpdateTrainingApplicationStatusException
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function addInvitation(
		TrainingDate $date,
		string $name,
		string $email,
		string $company,
		string $street,
		string $city,
		string $zip,
		string $country,
		string $companyId,
		string $companyTaxId,
		string $note,
	): int {
		return $this->insertApplication(
			$date->getTrainingId(),
			$date->getId(),
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
			$date->getPrice(),
			$date->getStudentDiscount(),
			TrainingApplicationStatus::Tentative,
			$this->trainingApplicationSources->resolveSource($note),
		);
	}


	/**
	 * @throws CannotUpdateTrainingApplicationStatusException
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function addApplication(
		TrainingDate $date,
		string $name,
		string $email,
		string $company,
		string $street,
		string $city,
		string $zip,
		string $country,
		string $companyId,
		string $companyTaxId,
		string $note,
	): int {
		return $this->insertApplication(
			$date->getTrainingId(),
			$date->getId(),
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
			$date->getPrice(),
			$date->getStudentDiscount(),
			TrainingApplicationStatus::SignedUp,
			$this->trainingApplicationSources->resolveSource($note),
		);
	}


	/**
	 * Add preliminary invitation, to a training with no date set.
	 *
	 * @return int application id
	 * @throws CannotUpdateTrainingApplicationStatusException
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function addPreliminaryInvitation(int $trainingId, string $name, string $email): int
	{
		return $this->insertApplication(
			$trainingId,
			null,
			$name,
			$email,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			TrainingApplicationStatus::Tentative,
			$this->trainingApplicationSources->getDefaultSource(),
		);
	}


	/**
	 * @throws CannotUpdateTrainingApplicationStatusException
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function insertApplication(
		int $trainingId,
		?int $dateId,
		string $name,
		string $email,
		?string $company,
		?string $street,
		?string $city,
		?string $zip,
		?string $country,
		?string $companyId,
		?string $companyTaxId,
		?string $note,
		?Price $price,
		?int $studentDiscount,
		TrainingApplicationStatus $status,
		string $source,
		?string $date = null,
	): int {
		if (!in_array($status, $this->trainingApplicationStatuses->getInitialStatuses(), true)) {
			throw new RuntimeException("Invalid initial status {$status->value}");
		}

		$statusId = $this->trainingApplicationStatuses->getStatusId(TrainingApplicationStatus::Created);
		$datetime = new DateTime($date ?? '');

		$customerPrice = $this->prices->resolvePriceDiscountVat($price, $studentDiscount, $status, $note ?? '');

		$timeZone = $datetime->getTimezone()->getName();
		$data = [
			'key_date' => $dateId,
			'name' => $name,
			'email' => $this->emailEncryption->encrypt($email),
			'company' => $company !== '' ? $company : null,
			'street' => $street !== '' ? $street : null,
			'city' => $city !== '' ? $city : null,
			'zip' => $zip !== '' ? $zip : null,
			'country' => $country !== '' ? $country : null,
			'company_id' => $companyId !== '' ? $companyId : null,
			'company_tax_id' => $companyTaxId !== '' ? $companyTaxId : null,
			'note' => $note !== '' ? $note : null,
			'key_status' => $statusId,
			'status_time' => $datetime,
			'status_time_timezone' => $timeZone,
			'key_source' => $this->trainingApplicationSources->getSourceId($source),
			'price' => $customerPrice->getPrice(),
			'vat_rate' => $customerPrice->getVatRate(),
			'price_vat' => $customerPrice->getPriceVat(),
			'discount' => $customerPrice->getDiscount(),
		];
		if ($dateId === null) {
			$data['key_training'] = $trainingId;
		}
		return $this->trainingApplicationStatuses->updateStatusCallbackReturnId(function () use ($data): int {
			$this->insertData($data);
			return (int)$this->database->getInsertId();
		}, $status, $date);
	}


	/**
	 * @param array<string, string|int|float|DateTime|null> $data
	 */
	private function insertData(array $data): void
	{
		$data['access_token'] = $this->generateAccessCode();
		try {
			$this->database->query('INSERT INTO training_applications', $data);
		} catch (UniqueConstraintViolationException) {
			// regenerate the access code and try harder this time
			Debugger::log("Regenerating access token, {$data['access_token']} already exists");
			$this->insertData($data);
		}
	}


	private function generateAccessCode(): string
	{
		return Random::generate(14, '0-9a-zA-Z');
	}


	public function updateApplication(
		TrainingDate $date,
		int $applicationId,
		string $name,
		string $email,
		string $company,
		string $street,
		string $city,
		string $zip,
		string $country,
		string $companyId,
		string $companyTaxId,
		string $note,
	): void {
		$this->trainingApplicationStatuses->updateStatusCallback(
			$applicationId,
			TrainingApplicationStatus::SignedUp,
			null,
			function () use (
				$date,
				$applicationId,
				$name,
				$email,
				$company,
				$street,
				$city,
				$zip,
				$country,
				$companyId,
				$companyTaxId,
				$note
			): void {
				$price = $this->prices->resolvePriceDiscountVat($date->getPrice(), $date->getStudentDiscount(), TrainingApplicationStatus::SignedUp, $note);
				$this->database->query(
					'UPDATE training_applications SET ? WHERE id_application = ?',
					[
						'name' => $name,
						'email' => $this->emailEncryption->encrypt($email),
						'company' => $company,
						'street' => $street,
						'city' => $city,
						'zip' => $zip,
						'country' => $country,
						'company_id' => $companyId,
						'company_tax_id' => $companyTaxId,
						'note' => $note,
						'price' => $price->getPrice(),
						'vat_rate' => $price->getVatRate(),
						'price_vat' => $price->getPriceVat(),
						'discount' => $price->getDiscount(),
					],
					$applicationId,
				);
			},
		);
	}


	/**
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function updateApplicationData(
		int $applicationId,
		?string $name,
		?string $email,
		?string $company,
		?string $street,
		?string $city,
		?string $zip,
		?string $country,
		?string $companyId,
		?string $companyTaxId,
		?string $note,
		string $source,
		?float $price,
		?float $vatRate,
		?float $priceVat,
		?int $discount,
		?string $invoiceId,
		string $paid,
		bool $familiar,
		?int $dateId,
	): void {
		$paidDate = $paid !== '' ? new DateTime($paid) : null;
		$timeZone = $paidDate?->getTimezone()->getName();
		if ($discount === 0) {
			$discount = null;
		}
		$data = [
			'name' => $name,
			'email' => $email !== null ? $this->emailEncryption->encrypt($email) : null,
			'company' => $company,
			'familiar' => $familiar,
			'street' => $street,
			'city' => $city,
			'zip' => $zip,
			'country' => $country,
			'company_id' => $companyId,
			'company_tax_id' => $companyTaxId,
			'note' => $note,
			'key_source' => $this->trainingApplicationSources->getSourceId($source),
			'price' => ($price !== null && $price !== 0.0) || $discount !== null ? $price : null,
			'vat_rate' => $vatRate !== null && $vatRate !== 0.0 ? $vatRate : null,
			'price_vat' => $priceVat !== null && $priceVat !== 0.0 ? $priceVat : null,
			'discount' => $discount,
			'invoice_id' => $invoiceId !== '' ? (int)$invoiceId : null,
			'paid' => $paidDate,
			'paid_timezone' => $timeZone,
		];
		if ($dateId !== null) {
			$data['key_date'] = $dateId;
		}
		$this->database->query('UPDATE training_applications SET ? WHERE id_application = ?', $data, $applicationId);
	}


	public function updateApplicationInvoiceData(int $applicationId, string $invoiceId): void
	{
		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			[
				'invoice_id' => $invoiceId !== '' ? (int)$invoiceId : null,
			],
			$applicationId,
		);
	}

}
