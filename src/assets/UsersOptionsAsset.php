<?php
declare(strict_types = 1);

namespace pozitronik\users_options\assets;

use yii\web\AssetBundle;

/**
 * Class UsersOptionsAsset
 */
class UsersOptionsAsset extends AssetBundle {
	/**
	 * @inheritdoc
	 */
	public function init() {
		$this->basePath = '@webroot';
		$this->js = ['js/users-options.js'];
		$this->publishOptions = ['forceCopy' => false];
		parent::init();
	}
}