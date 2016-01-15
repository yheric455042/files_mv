<?php

namespace OCA\Files_mv;
use OC\Files\Filesystem;
use OC\Files\View;
use OCP\Activity\IManager;
use OCA\Activity\UserSettings;
use OCA\Files_mv\Extension\Files;
use OCP\IGroupManager;
use OCP\IDBConnection;
use OCA\Activity\Data;
use OCP\Util;
use OCP\Share;
class Hook {
   	protected $manager;

	/** @var \OCA\Activity\Data */
	protected $activityData;

	/** @var \OCA\Activity\UserSettings */
	protected $userSettings;

	/** @var \OCP\IGroupManager */
	protected $groupManager;

	/** @var \OCP\IDBConnection */
	protected $connection;

	/** @var \OC\Files\View */
	protected $view;

	/** @var string|false */
	protected $currentUser;




   	public function __construct(IManager $manager, Data $activityData, UserSettings $userSettings, IGroupManager $groupManager, View $view, IDBConnection $connection, $currentUser) {
		$this->manager = $manager;
		$this->activityData = $activityData;
		$this->userSettings = $userSettings;
		$this->groupManager = $groupManager;
		$this->view = $view;
		$this->connection = $connection;
		$this->currentUser = $currentUser;
	}
    
    public function fileRenameOrMove($params) {
        $oldpath = explode('/',$params['oldpath']);
        $newpath = explode('/',$params['newpath']);
        
        if($this->MoveOrRename($oldpath, $newpath)) {
            $this->addNotificationsForFileAction($params['oldpath'], $params['newpath'], 'file_moved', 'moved_self');
        } else {
            $this->addNotificationsForFileAction($params['oldpath'], $params['newpath'], 'file_renamed', 'renamed_self');
        }



    }
    
    protected function MoveOrRename($oldpath,$newpath) {
        if(count($oldpath) != count($newpath)) {
            return 1; //means action is move
        } else {
            for($i = 1; $i < count($oldpath) - 2; $i++) {
                if($oldpath[$i] != $newpath[$i]) {
                    return 1;
                }
            }

            return 0; //means action is rename
        }

    }

	protected function addNotificationsForFileAction($oldfilePath, $newfilePath, $activityType, $subject) {
		// Do not add activities for .part-files
		if (substr($newfilePath, -5) === '.part') {
			return;
		}
        
        list($filePath, $uidOwner, $fileId) = $this->getSourcePathAndOwner($newfilePath);
        
		$affectedUsers = $this->getUserPathsFromPath($filePath, $uidOwner);
		$filteredStreamUsers = $this->userSettings->filterUsersBySetting(array_keys($affectedUsers), 'stream', $activityType);
		$filteredEmailUsers = $this->userSettings->filterUsersBySetting(array_keys($affectedUsers), 'email', $activityType);

        foreach($affectedUsers as $user=>$path) {
            if (empty($filteredStreamUsers[$user]) && empty($filteredEmailUsers[$user])) {
				continue;
			}

			if ($user === $this->currentUser) {
				$userSubject = $subject;
				$userParams = array($oldfilePath, $path);
			} else {
                
                continue;
            }

            $this->addNotificationsForUser(
                $user, $userSubject, $userParams,
                $fileId, $path, true,
                !empty($filteredStreamUsers[$this->currentUser]),
                !empty($filteredEmailUsers[$this->currentUser]) ? $filteredEmailUsers[$this->currentUser] : 0,
                $activityType
            );
        }

    }
    
    protected function getSourcePathAndOwner($path) {
		$uidOwner = Filesystem::getOwner($path);
		$fileId = 0;

		if ($uidOwner !== $this->currentUser) {
			Filesystem::initMountPoints($uidOwner);
		}
		$info = Filesystem::getFileInfo($path);
		if ($info !== false) {
			$ownerView = new View('/' . $uidOwner . '/files');
			$fileId = (int) $info['fileid'];
			$path = $ownerView->getPath($fileId);
		}

		return array($path, $uidOwner, $fileId);
	}

    protected function getUserPathsFromPath($path, $uidOwner) {
		return Share::getUsersSharingFile($path, $uidOwner, true, true);
	}


    protected function addNotificationsForUser($user, $subject, $subjectParams, $fileId, $path, $isFile, $streamSetting, $emailSetting, $type = Files_Sharing::TYPE_SHARED) {
		if (!$streamSetting && !$emailSetting) {
			return;
		}

		$selfAction = $user === $this->currentUser;
		$app = 'files_mv';
		$link = Util::linkToAbsolute('files', 'index.php', array(
			'dir' => ($isFile) ? dirname($path) : $path,
		));

		$objectType = ($fileId) ? 'files' : '';

		$event = $this->manager->generateEvent();
		$event->setApp($app)
			->setType($type)
			->setAffectedUser($user)
			->setAuthor($this->currentUser)
			->setTimestamp(time())
			->setSubject($subject, $subjectParams)
			->setObject($objectType, $fileId, $path)
			->setLink($link);

		// Add activity to stream
		if ($streamSetting && (!$selfAction || $this->userSettings->getUserSetting($this->currentUser, 'setting', 'self'))) {
			$this->activityData->send($event);
		}

		// Add activity to mail queue
		if ($emailSetting && (!$selfAction || $this->userSettings->getUserSetting($this->currentUser, 'setting', 'selfemail'))) {
			$latestSend = time() + $emailSetting;
			$this->activityData->storeMail($event, $latestSend);
		}
	}

    
    


}


?>
