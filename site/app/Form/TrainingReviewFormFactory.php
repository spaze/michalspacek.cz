<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Reviews\TrainingReview;
use MichalSpacekCz\Training\Reviews\TrainingReviews;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Html;

class TrainingReviewFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Applications $trainingApplications,
		private readonly TrainingReviews $trainingReviews,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 */
	public function create(callable $onSuccess, int $dateId, ?TrainingReview $review = null): Form
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
			->addRule($form::MAX_LENGTH, 'Maximální délka firmy je %d znaků', 200); // No min length to allow _removal_ of company name from a review by using an empty string
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

		$form->onSuccess[] = function (Form $form) use ($onSuccess, $review, $dateId): void {
			$values = $form->getValues();
			if ($review) {
				$this->trainingReviews->updateReview(
					$review->getId(),
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


	private function setReview(Form $form, TrainingReview $review, SubmitButton $submit): void
	{
		$values = [
			'name' => $review->getName(),
			'company' => $review->getCompany(),
			'jobTitle' => $review->getJobTitle(),
			'review' => $review->getReviewTexy(),
			'href' => $review->getHref(),
			'hidden' => $review->isHidden(),
			'ranking' => $review->getRanking(),
			'note' => $review->getNote(),
		];
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
			$reviewApplicationNames[] = $review->getName();
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
