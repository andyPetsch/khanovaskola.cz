<?php

namespace FrontModule;

use Nette\Application\UI\Form;


class ExercisePresenter extends BaseFrontPresenter
{

	/**
	 * @persistent
	 * @var public
	 */
	public $eid;

	/** @var Exercise */
	protected $exercise;



	public function startup()
	{
		parent::startup();
		$this->exercise = $this->context->exercises->findOneBy(['id' => $this->eid]);
	}



	public function renderDefault()
	{
		if (!$this->exercise) {
			$this->redirect('list');
		}
		$this->template->exercise = $this->exercise;
	}



	public function renderList()
	{
		$this->template->exercises = $this->context->exercises->findAll();
	}



	public function renderEdit()
	{
		$form = $this['editForm'];
		$ex = $this->exercise;

		$form['label']->setValue($ex->label);
		$form['file']->setValue($ex->file);
	}



	public function createComponentEditForm($name)
	{
		$form = new Form($this, $name);

		$form->addText('label', 'Název');
		$form->addText('file', 'Soubor');

		$form->addSubmit('send');
		$form->onSuccess[] = callback($this, 'onSuccessEditForm');

		return $form;
	}



	public function onSuccessEditForm(Form $form)
	{
		$v = $form->values;
		$ex = $this->exercise;

		$ex->label = $v->label;
		$ex->slug = \Nette\Utils\Strings::webalize($ex->label);
		$ex->file = $v->file;

		$ex->update();

		$this->redirect(':Front:Exercise:');
	}



	public function handleSaveAnswer($name, $correct)
	{
		if (TRUE || $this->ajax) {
			$this->user->entity->saveExerciseAnswer($name, $correct);
			$this->sendJson(['status' => 'success']);
		}
		$this->terminate();
	}

}
