<?php
declare(strict_types = 1);

namespace pozitronik\users_options\models;

use Throwable;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property int $user_id System user id
 * @property string $option Option name
 * @property array $value Option value in JSON
 *
 * Функции доступны у модели пользователя как $user->options->get($key, $decoded = false) и $user->options->set($key, $value);
 * По умолчанию ожидается, что $value -- массив (модель изначально проектировалась для хранения наборов данных - фильтров, закладок, куков, спамов), но можно хранить и скалярные типы данных, преобразуя их к array
 * В этом случае доставать опцию через get нужно с параметром $decoded = true.
 *
 * Для работы в фоне см. controllers\AjaxController и UsersOptionsAsset.
 *
 * При подключении ассета становится доступна асинхронная js-функция set_option(key, value), она сохранит настройку для текущего пользователя. Тип данных value может быть любой.
 * Js-геттера нет, т.к. не пригодился.
 */
class UsersOptions extends ActiveRecord {

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return ArrayHelper::getValue(Yii::$app->modules, 'usersoptions.params.tableName', 'users_options');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['id', 'user_id'], 'integer'],
			[['option'], 'required'],
			[['value'], 'safe'],
			[['option'], 'string', 'max' => 32],
			[['user_id', 'option'], 'unique', 'targetAttribute' => ['user_id', 'option']]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'user_id' => 'System user id',
			'option' => 'Option name',
			'value' => 'Option value in JSON'
		];
	}

	/**
	 * @param string $option
	 * @param bool $decoded -- true, если ожидается, что в настройку сохранён не массив, а единичная опция (строка, логическое значение или цифра)
	 * @return mixed
	 * @throws Throwable
	 */
	public function get(string $option, bool $decoded = false) {
		$value = (null === $result = self::find()->where(['option' => $option, 'user_id' => $this->user_id])->one())?[]:$result->value;
		return ($decoded)?json_decode(ArrayHelper::getValue($value, 0, '')):$value;
	}

	/**
	 * @param string $option
	 * @param array $value
	 * @return bool
	 */
	public function set(string $option, array $value):bool {
		if (null === $userOptions = self::find()->where(['option' => $option, 'user_id' => $this->user_id])->one()) {
			$userOptions = new self(['user_id' => $this->user_id, 'option' => $option, 'value' => $value]);
		} else {
			$userOptions->value = $value;
		}
		return $userOptions->save();
	}

}
