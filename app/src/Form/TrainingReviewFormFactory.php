<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\Reviews\TrainingReview;
use MichalSpacekCz\Training\Reviews\TrainingReviews;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Utils\Html;

final readonly class TrainingReviewFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingApplications $trainingApplications,
		private TrainingReviews $trainingReviews,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 */
	public function create(callable $onSuccess, int $dateId, ?TrainingReview $review = null): UiForm
	{
		$form = $this->factory->create();

		if ($review === null) {
			$form->addSelect('application', 'Šablona:', $this->getApplications($dateId))
				->setRequired(false)
				->setPrompt('- vyberte účastníka -');
		}
		$form->addText('name', 'Jméno:')
			->setRequired('Zadejte prosím jméno')
			->addRule(Form::MinLength, 'Minimální délka jména je %d znaky', 3)
			->addRule(Form::MaxLength, 'Maximální délka jména je %d znaků', 200);
		$form->addText('company', 'Firma:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka firmy je %d znaků', 200); // No min length to allow _removal_ of company name from a review by using an empty string
		$form->addText('jobTitle', 'Pozice:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka pozice je %d znaků', 200);
		$form->addTextArea('review', 'Ohlas:')
			->setRequired('Zadejte prosím ohlas')
			->addRule(Form::MinLength, 'Minimální délka ohlasu je %d znaky', 3)
			->addRule(Form::MaxLength, 'Maximální délka ohlasu je %d znaků', 2000);
		$form->addText('href', 'Odkaz:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu je %d znaků', 200);
		$form->addCheckbox('hidden', 'Skrýt:');
		$form->addInteger('ranking', 'Pořadí:')
			->setRequired(false)
			->addRule(Form::Min, 'Minimální hodnota pořadí je %d', 0);
		$form->addText('note', 'Poznámka:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka poznámky je %d znaků', 2000);
		$submit = $form->addSubmit('submit', 'Přidat');
		if ($review !== null) {
			$this->setReview($form, $review, $submit);
		}

		$form->onSuccess[] = function (UiForm $form) use ($onSuccess, $review, $dateId): void {
			$values = $form->getFormValues();
			assert(is_string($values->name));
			assert(is_string($values->company));
			assert(is_string($values->jobTitle));
			assert(is_string($values->review));
			assert(is_string($values->href));
			assert(is_bool($values->hidden));
			assert(is_int($values->ranking) || $values->ranking === null);
			assert(is_string($values->note));
			if ($review !== null) {
				$this->trainingReviews->updateReview(
					$review->getId(),
					$dateId,
					$values->name,
					$values->company,
					$values->jobTitle !== '' ? $values->jobTitle : null,
					$values->review,
					$values->href !== '' ? $values->href : null,
					$values->hidden,
					$values->ranking !== 0 ? $values->ranking : null,
					$values->note !== '' ? $values->note : null,
				);
			} else {
				$this->trainingReviews->addReview(
					$dateId,
					$values->name,
					$values->company,
					$values->jobTitle !== '' ? $values->jobTitle : null,
					$values->review,
					$values->href !== '' ? $values->href : null,
					$values->hidden,
					$values->ranking !== 0 ? $values->ranking : null,
					$values->note !== '' ? $values->note : null,
				);
			}
			$onSuccess($dateId);
		};

		return $form;
	}


	private function setReview(UiForm $form, TrainingReview $review, SubmitButton $submit): void
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
			if (!$application->isDiscarded()) {
				$option = Html::el('option');
				if (in_array($application->getName(), $reviewApplicationNames, true)) {
					$option->disabled = true;
				}
				$option->setText(($application->getName() ?? 'smazáno') . ($application->getCompany() !== null ? ", {$application->getCompany()}" : ''));
				$option->addAttributes([
					'data-name' => $application->getName() ?? '',
					'data-company' => $application->getCompany() ?? '',
				]);
				$applications[$application->getId()] = $option;
			}
		}
		return $applications;
	}

}
