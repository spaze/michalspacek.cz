<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Reviews;
use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Html;
use stdClass;

class TrainingReviewFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Applications $trainingApplications,
		private readonly Reviews $trainingReviews,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 */
	public function create(callable $onSuccess, int $dateId, ?Row $review = null): Form
	{
		$form = $this->factory->create();

		if (!$review) {
			$form->addSelect('application', 'Šablona:', $this->getApplications($dateId))
				->setRequired(false)
				->setPrompt('- vyberte účastníka -');
		}
		$form->addText('name', 'Jméno:')
			->setRequired('Zadejte prosím jméno')
			->addRule($form::MIN_LENGTH, 'Minimální délka jména je %d znaky', 3)
			->addRule($form::MAX_LENGTH, 'Maximální délka jména je %d znaků', 200);
		$form->addText('company', 'Firma:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka firmy je %d znaků', 200);  // No min length to allow _removal_ of company name from a review by using an empty string
		$form->addText('jobTitle', 'Pozice:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka pozice je %d znaků', 200);
		$form->addTextArea('review', 'Ohlas:')
			->setRequired('Zadejte prosím ohlas')
			->addRule($form::MIN_LENGTH, 'Minimální délka ohlasu je %d znaky', 3)
			->addRule($form::MAX_LENGTH, 'Maximální délka ohlasu je %d znaků', 2000);
		$form->addText('href', 'Odkaz:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu je %d znaků', 200);
		$form->addCheckbox('hidden', 'Skrýt:');
		$form->addText('ranking', 'Pořadí:')
			->setRequired(false)
			->setHtmlType('number');
		$form->addText('note', 'Poznámka:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
		$submit = $form->addSubmit('submit', 'Přidat');
		if ($review) {
			$this->setReview($form, $review, $submit);
		}

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess, $review, $dateId): void {
			if ($review) {
				$this->trainingReviews->updateReview(
					$review->reviewId,
					$dateId,
					$values->name,
					$values->company,
					$values->jobTitle ?: null,
					$values->review,
					$values->href ?: null,
					$values->hidden,
					$values->ranking ?: null,
					$values->note ?: null,
				);
			} else {
				$this->trainingReviews->addReview(
					$dateId,
					$values->name,
					$values->company,
					$values->jobTitle ?: null,
					$values->review,
					$values->href ?: null,
					$values->hidden,
					$values->ranking ?: null,
					$values->note ?: null,
				);
			}
			$onSuccess($dateId);
		};

		return $form;
	}


	/**
	 * @param Form $form
	 * @param Row<mixed> $review
	 * @param SubmitButton $submit
	 */
	private function setReview(Form $form, Row $review, SubmitButton $submit): void
	{
		$values = array(
			'name' => $review->name,
			'company' => $review->company,
			'jobTitle' => $review->jobTitle,
			'review' => $review->review,
			'href' => $review->href,
			'hidden' => $review->hidden,
			'ranking' => $review->ranking,
			'note' => $review->note,
		);
		$form->setDefaults($values);
		$submit->caption = 'Upravit';
	}


	/**
	 * @return array<int, Html>
	 */
	private function getApplications(int $dateId): array
	{
		$reviewApplicationNames = [];
		foreach ($this->trainingReviews->getReviewsByDateId($dateId) as $review) {
			if ($review->name !== null) {
				$reviewApplicationNames[] = $review->name;
			}
		}

		$applications = [];
		foreach ($this->trainingApplications->getByDate($dateId) as $application) {
			if (!$application->discarded) {
				$option = Html::el('option');
				if (in_array($application->name, $reviewApplicationNames)) {
					$option->disabled = true;
				}
				$option->setText(($application->name ?? 'smazáno') . ($application->company ? ", {$application->company}" : ''));
				$option->addAttributes([
					'data-name' => $application->name ?? '',
					'data-company' => $application->company ?? '',
				]);
				$applications[(int)$application->id] = $option;
			}
		}
		return $applications;
	}

}
