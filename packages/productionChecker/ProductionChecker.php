<?php

namespace ProductionChecker;

class ProductionChecker
{
	use FolderSelector;

	/**
	 * @var ProductionChecker
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private $branchName = '';

	/**
	 * @var string
	 */
	private $productionBranchName = '';

	/**
	 * @var float
	 */
	private $gitDelay = 0.2;

	/**
	 * @return string
	 */
	public function getBranchName() {
		return $this->branchName;
	}

	/**
	 * @param string $branchName
	 * @return $this
	 */
	public function setBranchName($branchName) {
		$this->branchName = $branchName;

		return $this;
	}

	public function getGitDelay() {
		return $this->gitDelay;
	}

	public function setGitDelay($delay) {
		$this->gitDelay = $delay;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getProductionBranchName() {
		return $this->productionBranchName;
	}

	/**
	 * @param string $productionBranchName
	 * @return $this
	 */
	public function setProductionBranchName($productionBranchName) {
		$this->productionBranchName = $productionBranchName;

		return $this;
	}

	public function run() {
	    $this->removeLockFile();
        $this->mergeRollback();
        $this->clean();

		$this->checkoutAll();
		$this->refreshRepository();
		$this->selectProductionBranch();
		$this->refreshRepository();
		$this->selectBranch();
		$this->refreshRepository();

		return $this->isActualProductionInBranch() && $this->attemptMergeProductionBranch();
	}

	private function removeLockFile() {
        echo PHP_EOL . $this->getFolderSelector() . 'rm .git/index.lock' . PHP_EOL;
	    @unlink($this->getFolderSelector() . 'rm .git/index.lock');
    }

	private function checkoutAll() {
		sleep($this->getGitDelay());
		echo PHP_EOL . $this->getFolderSelector() . 'git checkout .' . PHP_EOL;
		exec($this->getFolderSelector() . 'git checkout .');
	}

	private function refreshRepository() {
		sleep($this->getGitDelay());

		echo PHP_EOL . $this->getFolderSelector() . 'git pull --ff-only' . PHP_EOL;
		exec($this->getFolderSelector() . 'git pull --ff-only 2>&1', $out, $err);
        $out = implode(', ', $out);

		if (strpos($out, 'git prune') !== false) {
            echo PHP_EOL . $this->getFolderSelector() . 'rm .git/gc.log' . PHP_EOL;
            exec($this->getFolderSelector() . 'rm .git/gc.log');

            echo PHP_EOL . $this->getFolderSelector() . 'git prune' . PHP_EOL;
            exec($this->getFolderSelector() . 'git prune');

            echo PHP_EOL . $this->getFolderSelector() . 'git pull --ff-only' . PHP_EOL;
            exec($this->getFolderSelector() . 'git pull --ff-only');
        }
	}

	private function selectProductionBranch() {
		sleep($this->getGitDelay());
		echo PHP_EOL . $this->getFolderSelector() . 'git checkout ' . $this->getProductionBranchName() . PHP_EOL;
		exec($this->getFolderSelector() . 'git checkout ' . $this->getProductionBranchName());
	}

	private function selectBranch() {
		sleep($this->getGitDelay());
		echo PHP_EOL . $this->getFolderSelector() . 'git checkout ' . $this->getBranchName() . PHP_EOL;
		exec($this->getFolderSelector() . 'git checkout ' . $this->getBranchName());
	}

	private function attemptMergeProductionBranch() {
		sleep($this->getGitDelay());
		echo PHP_EOL . $this->getFolderSelector() . 'git merge --no-ff  ' . $this->getProductionBranchName() . PHP_EOL;
		exec($this->getFolderSelector() . 'git merge --no-ff  ' . $this->getProductionBranchName());

		if (!empty(exec($this->getFolderSelector() . 'git diff --name-only --diff-filter=U'))) {
			$this->mergeAbort();

			return false;
		}

		$this->checkoutAll();
		$this->mergeRollback();
		$this->mergeAbort();

		return true;
	}

	private function isActualProductionInBranch() {
		sleep($this->getGitDelay());
		echo PHP_EOL . $this->getFolderSelector() . 'git log | grep "' . $this->getProductionBranchName() . '"' . PHP_EOL;
		return !empty(exec($this->getFolderSelector() . 'git log | grep "' . $this->getProductionBranchName() . '"'));
	}

	private function mergeAbort() {
		sleep($this->getGitDelay());
		echo PHP_EOL . $this->getFolderSelector() . 'git merge --abort' . PHP_EOL;
		exec($this->getFolderSelector() . 'git merge --abort');
	}

	private function mergeRollback() {
		sleep($this->getGitDelay());
		echo PHP_EOL . $this->getFolderSelector() . 'git reset --hard HEAD^' . PHP_EOL;
		exec($this->getFolderSelector() . 'git reset --hard HEAD^');
	}

	private function clean() {
		sleep($this->getGitDelay());
		echo PHP_EOL . $this->getFolderSelector() . 'git clean -f -d' . PHP_EOL;
		exec($this->getFolderSelector() . 'git clean -f -d');
	}

	private function __construct() {}

	private function __clone() {}

	static function me() {
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}
		return static::$instance;
	}
}
