<?php

namespace ModeratorModule;

use Nette\Caching\Cache;
use Model\NetteUser as ROLE;


class DashboardPresenter extends BaseModeratorPresenter
{

	public function renderDefault()
	{
		$cat = [];
		$cat['description'] = $this->context->categories->findBy(['description' => '']);
		$this->template->cat = $cat;

		$vid = [];
		$vid['description'] = $this->context->videos->findBy(['description' => '']);

		$ids = [];
		foreach ($this->context->videos->findBy(['exercise_id IS NOT NULL']) as $video) {
			$ids[] = $video['exercise_id'];
		}
		$vid['exercise'] = $this->context->exercises->findBy(['id NOT IN ?' => $ids]);
		$vid['nogroupex'] = $this->context->exercises->findWithoutCategory();

		$this->template->limit = 10;
		$this->template->to_publish = $this->context->articles->findPublished(FALSE);
		$this->template->vid = $vid;
		$this->template->github = new \Model\Github($this->context);

		$this->template->wanted_cats = $this->context->categories->findByVotes();
	}



	public function renderVideos()
	{
		if (!$this->user->isInrole(ROLE::VERIFIER)) {
			throw new \Nette\Application\ForbiddenRequestException;
		}

		$dubbed = "khanacademyczech";
		$this->template->notVerified = $this->context->videos->findNotVerified()->where('uploader <> ?', $dubbed);
		$this->template->oneVerification = $this->context->videos->findWithVerification(1)->where('uploader <> ?', $dubbed);
		$this->template->forDubbing = $this->context->videos->findReadyForDubbing()->where('uploader <> ?', $dubbed);
	}



	public function renderSubtitles()
	{
		if (!$this->user->isInrole(ROLE::VERIFIER)) {
			throw new \Nette\Application\ForbiddenRequestException;
		}

		$errors = [];
		foreach ($this->context->videos->findAll() as $video) {
			$errors[] = (object) [
				'errors' => $this->context->subsChecker->getErrors($video),
				'video' => $video,
			];
		}
		$this->template->errors = $errors;
	}



	public function handleAddExercise()
	{
		$exercise = $this->context->exercises->insert([]);
		$this->redirect(':Front:Exercise:edit', ['eid' => $exercise->id]);
	}



	public function renderDetachedExercises()
	{
		$this->template->detached = $this->getDetachedExercises();
	}



	public function handlePopulateDb($file = NULL)
	{
		if (!$this->user->isInrole(ROLE::ADMIN)) {
			$this->redirect(':Moderator:Dashboard:');
		}

		$exercises = $this->getDetachedExercises();
		if (!$file) {
			foreach ($exercises as $file => $label) {
				$ex = $this->context->exercises->insert([
					'file' => $file,
					'label' => $label,
				]);
				$ex->addSlug($label);
			}

		} else {
			$label = $exercises[$file];
			$ex = $this->context->exercises->insert([
				'file' => $file,
				'label' => $label,
			]);
			$ex->addSlug($label);
			$this->redirect(':Front:Exercise:', ['eid' => $ex->id]);
		}
		$this->redirect('this');
	}



	private function getDetachedExercises()
	{
		$detached = [];
		$path = WWW_DIR . "/exercise/translated";

		foreach (scandir($path) as $fullfile) {
			if (in_array($fullfile, ['.', '..'])) {
				continue;
			}

			$file = substr($fullfile, 0, -5);
			$exercise = $this->context->exercises->findOneBy(['file' => $file]);
			if (!$exercise) {
				$template = file_get_contents("$path/$fullfile");
				$match = [];
				preg_match('~(?<=<title>).*?(?=</title>)~', $template, $match);
				$detached[$file] = $match[0];
			}
		}

		return $detached;
	}



	public function handleAttachToGithub()
	{
		if (!$this->user->isInrole(ROLE::ADMIN)) {
			$this->redirect(':Moderator:Dashboard:');
		}

		$github = new \Model\Github($this->context);
		$github->redirectAuth($this);
	}



	public function handleClearCache()
	{
		if (!$this->user->isInrole(ROLE::ADMIN)) {
			$this->redirect(':Moderator:Dashboard:');
		}

		$cache = new Cache($this->context->cacheStorage);
		$cache->clean([Cache::ALL => TRUE]);

		$this->flashMessage('Cache byla smazána.');
		$this->redirect('this');
	}



	public function handleRefreshIssues()
	{
		$cache = new Cache($this->context->cacheStorage);
		$cache->clean([Cache::TAGS => ['issues']]);

		$this->flashMessage('List problému byl obnoven.');
		$this->redirect('this');
	}



	public function handleTrimYoutubeIds()
	{
		if (!$this->user->isInrole(ROLE::ADMIN)) {
			$this->redirect(':Moderator:Dashboard:');
		}

		$count = $this->context->videos->trimYoutubeIds();

		$this->flashMessage('Youtube_id bylo opraveno u ' . $count . ' videí.');
		$this->redirect('this');
	}



	public function handleAddDubbedTagToVideos()
	{
		if (!$this->user->isInrole(ROLE::ADMIN)) {
			$this->redirect(':Moderator:Dashboard:');
		}

		$count = $this->context->videos->addDubbedTagToVideos();
		if ($count === FALSE) {
			$this->flashMessage('Tag pro dabovaná videa neexistuje!');
		} else {
			$this->flashMessage("Dabovací tag byl přidán k $count videím.");
		}

		$this->redirect('this');
	}



	public function handleDownloadAutocomplete()
	{
		$dir = APP_DIR . '/../temp/autocomplete';
		@mkdir($dir);
		$file = "$dir/khanova-skola-autocomplete-" . date('y-m-d') . ".xml";

		$content = "<Autocompletions>\n";
		foreach ($this->context->database->table('_autocomplete') as $row) {
			$label = str_replace('"', '\"', $row['label']);
			$content .= "\t<Autocompletion term=\"$label\" type=\"1\" language=\"\" />\n";
		}
		$content .= "</Autocompletions>\n";
		file_put_contents($file, $content);

		$this->sendResponse(new \Nette\Application\Responses\FileResponse($file));
	}



	public function handleSaveMetadata()
	{
		$videos = $this->context->videos->findBy(['duration' => 0]);
		$updated = 0;
		$depleted = FALSE;
		foreach ($videos as $video) {
			if (!$video->updateMetaData()) {
				$depleted = TRUE;
				break;
			}
			$video->update();
			$updated++;
		}

		$cache = new Cache($this->context->cacheStorage);
		$cache->clean([Cache::TAGS => ['categories', 'videos/count']]);

		if ($depleted) {
			$this->flashMessage("Vyčerpali jsme limit Amara API, stáhněte prosím další metadata za chvíli.");
		}
		$this->flashMessage("Meta data byla doplněna u $updated videí.");

		$this->redirect('this');
	}



	public function handleCacheSubtitles()
	{
		set_time_limit(0);
		$videos = $this->context->videos->findAll();

		foreach ($videos as $video) {
			$subs = $this->context->amara->getSubtitles($video);
			if (!$subs) {
				$this->context->amara->clearCache($video);
				$this->context->amara->getSubtitles($video);
			}
		}

		$this->flashMessage("Cache titulků byla obnovena.");
		$this->redirect('this');
	}

}
