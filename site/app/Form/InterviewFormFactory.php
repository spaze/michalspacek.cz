<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Interviews\Interviews;
use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Controls\SubmitButton;
use stdClass;

class InterviewFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Interviews $interviews,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 * @param Row|null $interview
	 * @return Form
	 */
	public function create(callable $onSuccess, ?Row $interview = null): Form
	{
		$form = $this->factory->create();
		$form->addText('action', 'Akce:')
			->setRequired('Zadejte prosím akci')
			->addRule($form::MAX_LENGTH, 'Maximální délka akce je %d znaků', 200);
		$form->addText('title', 'Název:')
			->setRequired('Zadejte prosím název')
			->addRule($form::MAX_LENGTH, 'Maximální délka názvu je %d znaků', 200);
		$form->addTextArea('description', 'Popis:')
			->setRequired(false);
		$this->trainingControlsFactory->addDate(
			$form,
			'date',
			'Datum:',
			true,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})',
		);
		$form->addText('href', 'Odkaz na rozhovor:')
			->setRequired('Zadejte prosím odkaz na rozhovor')
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na rozhovor je %d znaků', 200);
		$form->addText('audioHref', 'Odkaz na audio:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na audio je %d znaků', 200);
		$form->addText('audioEmbed', 'Embed odkaz na audio:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka embed odkazu na audio je %d znaků', 200);
		$form->addText('videoHref', 'Odkaz na video:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na video je %d znaků', 200);
		$form->addText('videoEmbed', 'Embed odkaz na video:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka embed odkazu na video je %d znaků', 200);
		$form->addText('sourceName', 'Název zdroje:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka názvu zdroje je %d znaků', 200);
		$form->addText('sourceHref', 'Odkaz na zdroj:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na zdroj je %d znaků', 200);
		$submit = $form->addSubmit('submit', 'Přidat');
		if ($interview) {
			$this->setInterview($form, $interview, $submit);
		}

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($interview, $onSuccess): void {
			if ($interview) {
				$this->interviews->update(
					$interview->interviewId,
					$values->action,
					$values->title,
					$values->description,
					$values->date,
					$values->href,
					$values->audioHref,
					$values->audioEmbed,
					$values->videoHref,
					$values->videoEmbed,
					$values->sourceName,
					$values->sourceHref,
				);
			} else {
				$this->interviews->add(
					$values->action,
					$values->title,
					$values->description,
					$values->date,
					$values->href,
					$values->audioHref,
					$values->audioEmbed,
					$values->videoHref,
					$values->videoEmbed,
					$values->sourceName,
					$values->sourceHref,
				);
			}
			$onSuccess();
		};

		return $form;
	}


	/**
	 * @param Form $form
	 * @param Row<mixed> $interview
	 * @param SubmitButton $submit
	 */
	public function setInterview(Form $form, Row $interview, SubmitButton $submit): void
	{
		$values = [
			'action' => $interview->action,
			'title' => $interview->title,
			'description' => $interview->descriptionTexy,
			'date' => $interview->date->format('Y-m-d H:i'),
			'href' => $interview->href,
			'audioHref' => $interview->audioHref,
			'audioEmbed' => $interview->audioEmbed,
			'videoHref' => $interview->videoHref,
			'videoEmbed' => $interview->videoEmbed,
			'sourceName' => $interview->sourceName,
			'sourceHref' => $interview->sourceHref,
		];
		$form->setDefaults($values);
		$submit->caption = 'Upravit';
	}

}
