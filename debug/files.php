<?php
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Diag\Debug;

class listImportFiles {
	private $arResulter = [];
	private $option = [];

	public function __construct($arg) {
		$this->option = [
			'path' => $arg["path"],
			'absolute_path' => $arg["absolute_path"],
		];

		$dir = new Directory($this->option['path']);
		if ($dir->isExists()) {
			$arDir = $dir->getChildren();

			foreach($arDir as $dirItem){
				self::getFiles($dirItem);
				if ($dirItem->isDirectory()) {
					$this->arResulter['DIRECTORY'][] = $dirItem;
					self::getChildDir($dirItem);
				}
			}
		};
	}

	public function getListFiles() {
		return $this->arResulter["FILES"];
	}

	protected function getFiles($dirItem) {
		if ($dirItem->isFile() && preg_match('/.*\.xml$/', $dirItem->getName())) {
			if ($this->option['absolute_path']) {
				$pattern = '/' . preg_replace('/\//', '\/', \Bitrix\Main\Application::getDocumentRoot() . '/upload/1c_catalog')  . '/';
				$this->arResulter['FILES'][] = preg_replace($pattern, '', $dirItem->getPath());
			} else {
				$this->arResulter['FILES'][] = $dirItem->getPath();
			}
		}
	}

	protected function getChildDir($dirItem) {
		$dirChild = new Directory($dirItem->getPath());
		if ($dirChild->isExists()) {
			$arDir = $dirChild->getChildren();
			foreach($arDir as $dirItem){
				self::getFiles($dirItem);
				if ($dirItem->isDirectory()) {
					$this->arResulter['DIRECTORY'][] = $dirItem;
					self::getChildDir($dirItem);
				}
			}
		}
	}

}
?>