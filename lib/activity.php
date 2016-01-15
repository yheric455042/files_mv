<?php

namespace OCA\Files_mv;
use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OC\L10N\Factory;

class Activity implements IExtension {

    const FILTER_FILES = 'files';
	const FILTER_FAVORITES = 'files_favorites';
    const TYPE_SHARE_MOVED = 'file_moved';
    const TYPE_SHARE_RENAMED = 'file_renamed';
    const TYPE_SHARE_COPYED = 'file_copyed';


	/** @var Factory */
	protected $languageFactory;

	/** @var IURLGenerator */
	protected $URLGenerator;

	/** @var \OCP\Activity\IManager */
	protected $activityManager;

    public function __construct(Factory $languageFactory, IURLGenerator $URLGenerator, IManager $activityManager) {
		$this->languageFactory = $languageFactory;
        $this->URLGenerator = $URLGenerator;
        $this->activityManager = $activityManager;
    }

    
    protected function getL10N($languageCode = null) {
		return $this->languageFactory->get('files_mv', $languageCode);
	}

    public function getNotificationTypes($languageCode) {
        
        $l = $this->getL10N($languageCode);

        return [
            self::TYPE_SHARE_MOVED => (string) $l->t('A file or folder has been <strong>moved</strong>'),
            self::TYPE_SHARE_RENAMED => (string) $l->t('A file or folder has been <strong>renamed</strong>'),
            self::TYPE_SHARE_COPYED => (string) $l->t('A file or folder has been <strong>copyed</strong>'),
        ];
    }

    public function getDefaultTypes($method) {
		if ($method === self::METHOD_STREAM) {
			$settings = array();
			$settings[] = self::TYPE_SHARE_MOVED;
			$settings[] = self::TYPE_SHARE_RENAMED;
			$settings[] = self::TYPE_SHARE_COPYED;
			return $settings;
		}

		return false;
	}

    public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		if ($app !== 'files_mv') {
			return false;
		}

		$l = $this->getL10N($languageCode);

		if ($this->activityManager->isFormattingFilteredObject()) {
			$translation = $this->translateShort($text, $l, $params);
			if ($translation !== false) {
				return $translation;
			}
		}

		return $this->translateLong($text, $l, $params);
	}

    protected function translateShort($text, IL10N $l, array $params) {
		switch ($text) {
			case 'renamed_self':
				return (string) $l->t('Renamed by %2$s', $params);
			case 'copyed_self':
				return (string) $l->t('Copyed by %2$s', $params);
			case 'moved_self':
				return (string) $l->t('Moved by %2$s', $params);

			default:
				return false;
		}
	}

    protected function translateLong($text, IL10N $l, array $params) {
        $params[0] = $this->oldpathHandler($params[0]);
        $params[1] = $this->newpathHandler($params[1], $text);
		switch ($text) {
            
			case 'renamed_self':
				return (string) $l->t('You changed the filename %1$s to %2$s', $params);
			case 'moved_self':
				return (string) $l->t('You moved the file %1$s to %2$s', $params);
			case 'copyed_self':
				return (string) $l->t('You copyed th file %1$s to %2$s', $params);
						
			default:
				return false;
		}
	}

	function getSpecialParameterList($app, $text) {
		/*if ($app === 'files_mv') {
			switch ($text) {
				case 'renamed_self':
                case 'copyed_self':
                case 'moved_self':
                    return [
                        1 => 'file',
                    ];

			}
		}*/

		return false;
	}

    public function getTypeIcon($type) {
        switch ($type) {
			case self::TYPE_SHARE_MOVED:
			case self::TYPE_SHARE_COPYED:
			case self::TYPE_SHARE_RENAMED:
				return 'icon-change';

			default:
				return false;
		}
	}

    public function getGroupParameter($activity) {
		
		return false;
	}

    public function getNavigation() {
        return false;
    }
    
    public function isFilterValid($filterValue) {
		return $filterValue === self::FILTER_FILES || $filterValue === self::FILTER_FAVORITES;
	}
    
    public function filterNotificationTypes($types, $filter) {
		if ($filter === self::FILTER_FILES || $filter === self::FILTER_FAVORITES) {
			return array_intersect([
				self::TYPE_SHARE_RENAMED,
				self::TYPE_SHARE_COPYED,
				self::TYPE_SHARE_MOVED,
			], $types);
		}
		return false;
	}
    public function getQueryForFilter($filter) {
        if ($filter === 'files') {
			return [
				'`app` = ?',
				['files',],
			];
		}
        return false;
    }
    
    protected function oldpathHandler($oldpath) {
        $pattern = '/(<.*>)(.*)(<.*>)/';
        preg_match($pattern, $oldpath, $matches);

        $file = count($matches) === 1 ? $matches[0] : $matches[2];

        $path = explode("/",$file);
       
        $path = count($matches) === 1 ? $path[count($path) - 1] : '<strong>'.$path[count($path) - 1].'</strong>';

        return $path;  
       
    }

    protected function newpathHandler($newpath, $text) {
        
        if($text === 'renamed_self') {
           return $newpath;

        } else {

            if(substr($newpath,0,2) === "<a") {
                $pattern = "/(<.*>)(.*)(<.*>)/";
                preg_match($pattern, $newpath, $matches);

                $file = explode("/", $matches[2]);
                unset($file[count($file) - 1]);
                $path = implode("/", $file);
                $replacement = '${1}'.$path.'${3}';
                 
                file_put_contents('path.txt', preg_replace($pattern, $replacement, $newpath));

                return $newpath;
            }

            $path = explode("/", $newpath);
            unset($path[count($path) - 1]);
            $transpath = implode("/", $path);
            $transpath = '<strong>/'.$transpath.'</strong>';
            return $transpath;

        }

    }

}

?>
