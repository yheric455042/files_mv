<?php
/**
 * ownCloud - files_mv
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author eotryx <mhfiedler@gmx.de>
 * @copyright eotryx 2015
 */

namespace OCA\Files_mv\AppInfo;

\OCP\Util::addScript( 'files_mv', "move" );
\OCP\Util::addStyle('files_mv', 'mv');

/*
$app = new Application();
$app->getContainer()->query('FilesHook')->register();
*/

\OCA\Files_mv\Hooks\FilesHook::register();

\OC::$server->getActivityManager()->registerExtension(function() {
            return new \OCA\Files_mv\Activity(
                \OC::$server->query('L10NFactory'),
                \OC::$server->getURLGenerator(),
                \OC::$server->getActivityManager()
            );
    });

