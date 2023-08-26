<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Dates\TrainingDate;
use Nette\Http\SessionSection;
use stdClass;

class TrainingApplicationSessionSection extends SessionSection
{

	public function setApplicationForTraining(string $trainingAction, TrainingApplication $application): void
	{
		$data = (array)parent::get('application');
		$data[$trainingAction] = ['id' => $application->getId(), 'dateId' => $application->getDateId()];
		parent::set('application', $data);
		parent::set('name', $application->getName());
		parent::set('email', $application->getEmail());
		parent::set('company', $application->getCompany());
		parent::set('street', $application->getStreet());
		parent::set('city', $application->getCity());
		parent::set('zip', $application->getZip());
		parent::set('country', $application->getCountry());
		parent::set('companyId', $application->getCompanyId());
		parent::set('companyTaxId', $application->getCompanyTaxId());
		parent::set('note', $application->getNote());
	}


	public function getApplicationIdByDateId(string $trainingAction, int $dateId): ?int
	{
		$applicationKey = 'application';
		$application = parent::get($applicationKey);
		$dateIdKey = 'dateId';
		$applicationIdKey = 'id';
		if ($application === null) {
			return null;
		} elseif (!is_array($application)) {
			throw new ShouldNotHappenException("Session key {$applicationKey} type should be an array, but it's a " . get_debug_type($application));
		} elseif (!isset($application[$trainingAction])) {
			return null;
		} elseif (!is_array($application[$trainingAction])) {
			throw new ShouldNotHappenException("Session key {$applicationKey} > {$trainingAction} type should be array, but it's a " . get_debug_type($application[$trainingAction]));
		} elseif (!isset($application[$trainingAction][$dateIdKey]) || $application[$trainingAction][$dateIdKey] !== $dateId) {
			return null;
		} elseif (!isset($application[$trainingAction][$applicationIdKey]) || !is_int($application[$trainingAction][$applicationIdKey])) {
			throw new ShouldNotHappenException("Session key {$applicationKey} > {$trainingAction} > {$applicationIdKey} type should be int, but it's a " . get_debug_type($application[$trainingAction][$applicationIdKey]));
		}
		return $application[$trainingAction][$applicationIdKey];
	}


	public function removeApplication(string $trainingAction): void
	{
		$key = 'application';
		$application = parent::get($key);
		if ($application === null) {
			return;
		} elseif (!is_array($application)) {
			throw new ShouldNotHappenException("Session key {$key} type should be array, but it's a " . get_debug_type($application));
		}
		parent::set($key, array_merge($application, [$trainingAction => null]));
	}


	public function setOnSuccess(TrainingDate $date, stdClass $values): void
	{
		parent::set('trainingId', $date->getId());
		parent::set('name', $values->name);
		parent::set('email', $values->email);
		parent::set('company', $values->company);
		parent::set('street', $values->street);
		parent::set('city', $values->city);
		parent::set('zip', $values->zip);
		parent::set('country', $values->country);
		parent::set('companyId', $values->companyId);
		parent::set('companyTaxId', $values->companyTaxId);
		parent::set('note', $values->note);
	}


	public function getDateId(): ?int
	{
		$key = 'trainingId';
		$dateId = parent::get($key);
		if ($dateId !== null && !is_int($dateId)) {
			throw new ShouldNotHappenException("Session key {$key} type should be null|int, but it's a " . get_debug_type($dateId));
		}
		return $dateId;
	}


	/**
	 * @return array<string, mixed>
	 */
	public function getApplicationValues(): array
	{
		return [
			'name' => parent::get('name'),
			'email' => parent::get('email'),
			'company' => parent::get('company'),
			'street' => parent::get('street'),
			'city' => parent::get('city'),
			'zip' => parent::get('zip'),
			'country' => parent::get('country'),
			'companyId' => parent::get('companyId'),
			'companyTaxId' => parent::get('companyTaxId'),
			'note' => parent::get('note'),
		];
	}

}
