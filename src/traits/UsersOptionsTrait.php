<?php
declare(strict_types = 1);

namespace pozitronik\users_options\traits;

use pozitronik\users_options\models\UsersOptions;

/**
 * Trait UsersOptionsTrait
 *
 * @property UsersOptions $options Атрибут для обращения к правам пользователя (не реляционный, см. класс)
 */
trait UsersOptionsTrait {
	/**
	 * @return UsersOptions
	 */
	public function getOptions():UsersOptions {
		return new UsersOptions(['user_id' => $this->primaryKey]);
	}
}