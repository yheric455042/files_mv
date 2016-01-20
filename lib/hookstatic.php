<?php

namespace OCA\Files_mv;

use OCP\Util;

class HookStatic {
    public static function register() {
		Util::connectHook('OC_Filesystem', 'post_rename', 'OCA\Files_mv\HookStatic', 'fileRename');
    }

    static protected function getHooks() {
		$app = new \OCA\Files_mv\AppInfo\Application();
		return $app->getContainer()->query('Hooks');
	}

    public static function fileRename($params) {
		self::getHooks()->fileRenameOrMove($params);
    }
}

?>
