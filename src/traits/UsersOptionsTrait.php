<?php
declare(strict_types = 1);

namespace pozitronik\users_options\traits;

use pozitronik\users_options\models\UsersOptions;
use yii\db\ActiveRecord;

/**
 * Trait UserOptionsTrait
 * @package pozitronik\users_options\traits
 *
 * @property UsersOptions $options -- атрибут для обращения к правам пользователя (не реляционный, см. класс)
 */
trait UsersOptionsTrait {
	/**
	 * @return UsersOptions
	 */
	public function getOptions():UsersOptions {
		/** @var ActiveRecord $this */
		return new UsersOptions(['user_id' => $this->id]);
	}
}