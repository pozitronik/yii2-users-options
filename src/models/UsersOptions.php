<?php
declare(strict_types = 1);

namespace pozitronik\users_options\models;

use Throwable;
use Yii;
use yii\caching\TagDependency;
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
	 * @var null|array the functions used to serialize and unserialize values. Defaults to null, meaning
	 * using the default PHP `serialize()` and `unserialize()` functions. If you want to use some more efficient
	 * serializer (e.g. [igbinary](https://pecl.php.net/package/igbinary)), you may configure this property with
	 * a two-element array. The first element specifies the serialization function, and the second the deserialization
	 * function.
	 */
	public $serializer;
	/**
	 * @var bool enable intermediate caching via Yii::$app->cache (must be configured in framework). Default option
	 * value can be set in module configuration, e.g.
	 * ...
	 * 'usersoptions' => [
	 *        'class' => UsersOptionsModule::class,
	 *            'params' => [
	 *                'cacheEnabled' => true//defaults to false
	 *            ]
	 *        ],
	 * ...
	 */
	public $cacheEnabled = false;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return ArrayHelper::getValue(Yii::$app->modules, 'usersoptions.params.tableName', 'users_options');
	}

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		parent::init();
		$this->cacheEnabled = ArrayHelper::getValue(Yii::$app->modules, 'usersoptions.params.cacheEnabled', false);
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['id', 'user_id'], 'integer'],
			[['option'], 'required'],
			[['value', 'rawValue'], 'safe'],
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
			'value' => 'Option value',
		];
	}

	/**
	 * @param $value
	 * @return string
	 */
	private function serialize($value):string {
		return (null === $this->serializer)?serialize($value):call_user_func($this->serializer[0], $value);
	}

	/**
	 * @param string $value
	 * @return mixed
	 */
	private function unserialize(string $value) {
		return (null === $this->serializer)?unserialize($value, ['allowed_classes' => true]):call_user_func($this->serializer[1], $value);
	}

	/**
	 * @param string $option
	 * @return string
	 */
	private function getDbValue(string $option):string {
		return (null === $result = self::find()->where(['option' => $option, 'user_id' => $this->user_id])->one())?'':$result->value;
	}

	/**
	 * @param string $option
	 * @return mixed
	 * @throws Throwable
	 */
	public function get(string $option) {
		if ($this->cacheEnabled) {
			$value = Yii::$app->cache->getOrSet(static::class."::get({$this->user_id},{$option})", function() use ($option) {
				return $this->getDbValue($option);
			}, null, new TagDependency(['tags' => static::class."::get({$this->user_id},{$option})"]));
		} else {
			$value = $this->getDbValue($option);
		}
		return $this->unserialize($value);
	}

	/**
	 * @param string $option
	 * @param mixed $value
	 * @return bool
	 */
	public function set(string $option, $value):bool {
		$serializedValue = $this->serialize($value);
		TagDependency::invalidate(Yii::$app->cache, [static::class."::get({$this->user_id},{$option})"]);
		if (null === $userOptions = self::find()->where(['option' => $option, 'user_id' => $this->user_id])->one()) {
			$userOptions = new self(['user_id' => $this->user_id, 'option' => $option, 'value' => $serializedValue]);
		} else {
			$userOptions->value = $serializedValue;
		}
		return $userOptions->save();
	}

	/**
	 * Статический вызов с той же логикой, что у get()
	 * @param int $user_id
	 * @param string $option
	 * @return mixed
	 * @throws Throwable
	 */
	public static function getStatic(int $user_id, string $option) {
		return (new self(['user_id' => $user_id]))->get($option);
	}

	/**
	 * Статический вызов с той же логикой, что у set()
	 * @param int $user_id
	 * @param string $option
	 * @param mixed $value
	 * @return bool
	 */
	public static function setStatic(int $user_id, string $option, $value):bool {
		return (new self(['user_id' => $user_id]))->set($option, $value);
	}

}
