<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Exceptions\CannotUpdateTrainingApplicationStatusException;
use MichalSpacekCz\Training\Price;
use MichalSpacekCz\Training\Prices;
use MichalSpacekCz\Training\Statuses;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Random;
use RuntimeException;
use Spaze\Encryption\Symmetric\StaticKey;
use Tracy\Debugger;

class TrainingApplicationStorage
{

	public function __construct(
		private readonly Explorer $database,
		private readonly Statuses $trainingStatuses,
		private readonly TrainingApplicationSources $trainingApplicationSources,
		private readonly StaticKey $emailEncryption,
		private readonly Prices $prices,
	) {
	}


	/**
	 * @throws CannotUpdateTrainingApplicationStatusException
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
			Statuses::STATUS_TENTATIVE,
			$this->trainingApplicationSources->resolveSource($note),
		);
	}


	/**
	 * @throws CannotUpdateTrainingApplicationStatusException
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
			Statuses::STATUS_SIGNED_UP,
			$this->trainingApplicationSources->resolveSource($note),
		);
	}


	/**
	 * Add preliminary invitation, to a training with no date set.
	 *
	 * @return int application id
	 * @throws CannotUpdateTrainingApplicationStatusException
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
			Statuses::STATUS_TENTATIVE,
			$this->trainingApplicationSources->getDefaultSource(),
		);
	}


	/**
	 * @throws CannotUpdateTrainingApplicationStatusException
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
		string $status,
		string $source,
		?string $date = null,
	): int {
		if (!in_array($status, $this->trainingStatuses->getInitialStatuses())) {
			throw new RuntimeException("Invalid initial status {$status}");
		}

		$statusId = $this->trainingStatuses->getStatusId(Statuses::STATUS_CREATED);
		$datetime = new DateTime($date ?? '');

		$customerPrice = $this->prices->resolvePriceDiscountVat($price, $studentDiscount, $status, $note ?? '');

		$timeZone = $datetime->getTimezone()->getName();
		$data = [
			'key_date' => $dateId,
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
		return $this->trainingStatuses->updateStatusCallbackReturnId(function () use ($data): int {
			$this->insertData($data);
			return (int)$this->database->getInsertId();
		}, $status, $date);
	}


	/**
	 * @param array<string, string|int|float|DateTime|null> $data
	 * @return string Generated access token
	 */
	private function insertData(array $data): string
	{
		$data['access_token'] = $token = $this->generateAccessCode();
		try {
			$this->database->query('INSERT INTO training_applications', $data);
		} catch (UniqueConstraintViolationException) {
			// regenerate the access code and try harder this time
			Debugger::log("Regenerating access token, {$token} already exists");
			return $this->insertData($data);
		}
		return $token;
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
	): int {
		$this->trainingStatuses->updateStatusCallback(
			$applicationId,
			Statuses::STATUS_SIGNED_UP,
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
				$price = $this->prices->resolvePriceDiscountVat($date->getPrice(), $date->getStudentDiscount(), Statuses::STATUS_SIGNED_UP, $note);
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
		return $applicationId;
	}


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
		?float $price = null,
		?float $vatRate = null,
		?float $priceVat = null,
		?int $discount = null,
		?string $invoiceId = null,
		string $paid = null,
		bool $familiar = false,
		?int $dateId = null,
	): void {
		$paidDate = ($paid ? new DateTime($paid) : null);
		$timeZone = $paidDate?->getTimezone()->getName();
		$data = [
			'name' => $name,
			'email' => ($email ? $this->emailEncryption->encrypt($email) : null),
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
			'price' => ($price || $discount ? $price : null),
			'vat_rate' => ($vatRate ?: null),
			'price_vat' => ($priceVat ?: null),
			'discount' => ($discount ?: null),
			'invoice_id' => ((int)$invoiceId ?: null),
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
				'invoice_id' => ((int)$invoiceId ?: null),
			],
			$applicationId,
		);
	}

}
