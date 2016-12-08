<?php

namespace ProductionChecker;

trait FolderSelector
{
	/**
	 * @var string
	 */
	private $projectRepositoryPath = '';

	/**
	 * @return string
	 */
	public function getProjectRepositoryPath() {
		return $this->projectRepositoryPath;
	}

	/**
	 * @param string $path
	 * @return $this
	 */
	public function setProjectRepositoryPath($path) {
		$this->projectRepositoryPath = $path;

		return $this;
	}

	public function getFolderSelector() {
		return 'cd ' . $this->getProjectRepositoryPath() . ' && ';
	}
}