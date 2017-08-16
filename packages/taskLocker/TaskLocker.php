<?php

namespace TaskLocker;

class TaskLocker
{
    const LOCK_FILE_DIR = '/lockFiles/';

    private $lockFileDescriptor;
    private $lockFilename;

    public function __construct($lockFilename) {
        $this->lockFilename = dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR . $lockFilename;

        $this->lockFileDescriptor = fopen($this->lockFilename, 'w+');
    }

    /**
     * @return bool
     */
    public function isLocked() {
        $this->checkLockDirExisting();

        if (!flock($this->lockFileDescriptor, LOCK_EX | LOCK_NB)) {
            echo 'Process already started' . PHP_EOL;
            fclose($this->lockFileDescriptor);

            return true;
        }

        return false;
    }

    public function unlock() {
        flock($this->lockFileDescriptor, LOCK_UN);
        fclose($this->lockFileDescriptor);

        if (file_exists($this->lockFilename)) {
            unlink($this->lockFilename);
        }
    }

    private function checkLockDirExisting() {
        if (!file_exists(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR)) {
            mkdir(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR);
        }
    }
}