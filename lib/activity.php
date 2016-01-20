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
        static $imgOrtext = 0;

        $params[0] = $this->oldpathHandler($params[0]);
        $params[1] = $this->newpathHandler($params[1], $text);

		switch ($text) {
			case 'renamed_self':
                $params[1] = preg_replace('/(<a class="filename".*>)(.*)(<.*>)/', '<strong>${2}</strong>',$params[1]);

				return (string) $l->t('Changed the filename %1$s to %2$s', $params);

			case 'moved_self':
                $imgOrtext++;
                $imgOrtext %= 2;

                if($this->isHomeDirectory($params[1], $imgOrtext)) {
                    $params[1] = preg_replace('/(<a class="filename".*>)(.*)(<.*>)/','<strong>'.$l->t('home directory') .'</strong>',$params[1]);
                    
                    return (string) $l->t('Moved from %1$s to your %2$s', $params);

                } else {
                    $params[1] = preg_replace('/(<a class="filename has-tooltip".*>)(.*)(<.*>)/', '<strong>${2}</strong>', $params[1]);

                    return (string) $l->t('Moved from %1$s to %2$s', $params);
                }

			case 'copyed_self':
                $imgOrtext++;
                $imgOrtext %= 2;
                file_put_contents('123.txt','fdf');
                if($this->isHomeDirectory($params[1], $imgOrtext, 'copyed_self')) {
                    $params[1] = preg_replace('/(<a class="filename".*>)(.*)(<.*>)/', '<strong>'. $l->t('home directory').'</strong>',$params[1]);

                    return (string) $l->t('Copyed the file %1$s to your %2$s', $params);

                } else {
                    $params[1] = preg_replace('/(<a class="filename has-tooltip".*>)(.*)(<.*>)/', '<strong>${2}</strong>', $params[1]);

                    return (string) $l->t('Copyed the file %1$s to %2$s', $params);
                }

						
			default:
				return false;
		}

	}

    protected function translateLong($text, IL10N $l, array $params) {
        static $imgOrtext = 0;

        $params[0] = $this->oldpathHandler($params[0]);
        $params[1] = $this->newpathHandler($params[1], $text);

		switch ($text) {
			case 'renamed_self':
				return (string) $l->t('You changed the filename %1$s to %2$s', $params);

			case 'moved_self':
                $imgOrtext++;
                $imgOrtext %= 2;

                if($this->isHomeDirectory($params[1], $imgOrtext)) {
                    $params[1] = preg_replace('/(<a class="filename".*>)(.*)(<.*>)/','${1}'.$l->t('home directory') .'${3}',$params[1]);
                    
                    return (string) $l->t('You moved the file %1$s to your %2$s', $params);
                } else {
                    return (string) $l->t('You moved the file %1$s to %2$s', $params);
                }

			case 'copyed_self':
                $imgOrtext++;
                $imgOrtext %= 2;

                if($this->isHomeDirectory($params[1], $imgOrtext)) {
                    $params[1] = preg_replace('/(<a class="filename".*>)(.*)(<.*>)/','${1}'. $l->t('home directory').'${3}',$params[1]);

                    return (string) $l->t('You copyed the file %1$s to your %2$s', $params);

                } else {
                    
                    return (string) $l->t('You copyed the file %1$s to %2$s', $params);
                }

						
			default:
				return false;
		}
	}

	function getSpecialParameterList($app, $text) {
		if ($app === 'files_mv') {
			switch ($text) {
				case 'renamed_self':
                case 'copyed_self':
                case 'moved_self':
                    return [
                        1 => 'file',
                    ];

			}
		}

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
    
    protected function isHomeDirectory($newpath, $imgOrtext) {
        if($imgOrtext) {
            $pattern = '/(<a class="filename has-tooltip".*>)(.*)(<.*>)/';
            preg_match($pattern, $newpath, $matches);

            return empty($matches[2]);

        /*} else if($text === 'copyed_self') {
            preg_match('/.*%2Ffiles(.*)&/', $newpath, $matches);
            return empty($matches[1]);
*/
        }
        return false;

    }


    protected function oldpathHandler($oldpath) {
        $pattern = '/(<.*>)(.*)(<.*>)/';
        preg_match($pattern, $oldpath, $matches);
        $file = count($matches) === 0 ? $oldpath : $matches[2];

        $path = explode("/",$file);
         
        $path = count($matches) === 0 ? $path[count($path) - 1] : '<strong>'.$path[count($path) - 1].'</strong>';

        return $path;  
       
    }

    protected function newpathHandler($newpath, $text) {
        if($text === 'renamed_self') {
           return $newpath;

        } else {
            $matches = array(); 
            $pattern = '/(<a class="filename has-tooltip".*>)(.*)(<.*>)/';
            preg_match($pattern, $newpath, $matches);
            
            if(count($matches)) {
                preg_match('/.*=%2F(.*)&/', $matches[1], $dirArray);
                $dir = $dirArray[1];
                $dir = explode('%2F',$dir);
                $replacement = '${1}'.$dir[count($dir) - 1].'${3}';
                
                return preg_replace($pattern,$replacement,$newpath);
            }

            return $newpath;
        }

    }

}

?>
