<?php

namespace OCA\Files_mv\Hooks;

use OCP\Util;

class FilesHook {
    
    static protected function getHooks() {
		$app = new \OCA\Files_mv\AppInfo\Application();
		return $app->getContainer()->query('Hooks');
	}

    public static function fileRename($params) {
        file_put_contents('action.txt','123');
		self::getHooks()->fileRenameOrMove($params);
    }

    public static function register() {
		Util::connectHook('OC_Filesystem', 'post_rename', 'OCA\Files_mv\Hooks\FilesHook', 'fileRename');
    }
}

?>
