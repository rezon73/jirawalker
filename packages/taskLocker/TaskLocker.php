<?php

namespace TaskLocker;

class TaskLocker
{
    const LOCK_FILE_DIR = '/lockFiles/';

    /**
     * @var TaskLocker
     */
    private static $instance = null;

    public static function me() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param string $lockFilename
     */
    public function isLocked($lockFilename) {
        $this->checkLockDirExisting();

        return file_exists(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR . $lockFilename);
    }

    /**
     * @param string $lockFilename
     */
    public function lock($lockFilename) {
        if (!file_exists(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR . $lockFilename)) {
            touch(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR . $lockFilename);
        }
    }

    /**
     * @param string $lockFilename
     */
    public function unlock($lockFilename) {
        if (file_exists(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR . $lockFilename)) {
            unlink(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR . $lockFilename);
        }
    }

    private function checkLockDirExisting() {
        if (!file_exists(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR)) {
            mkdir(dirname(__FILE__) . TaskLocker::LOCK_FILE_DIR);
        }
    }

    private function __construct() {}

    private function __clone() {}
}