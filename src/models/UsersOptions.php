<?php
declare(strict_types = 1);

namespace pozitronik\users_options\models;

use Throwable;
use Yii;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\helpers\ArrayHelper;

/**
 * @property null|int $user_id -- the user identification key. Defaults to null, meaning use current active user id.
 * @property Connection|array|string $db -- the DB connection object or the application component ID of the DB connection.
 * @property null|array $serializer -- the functions used to serialize and unserialize values. Defaults to null, meaning
 * using the default PHP `serialize()` and `unserialize()` functions
 * @property bool $cacheEnabled -- enable intermediate caching via Yii::$app->cache (must be configured in framework)
 * @property-read string $tableName
 *
 * Функции доступны у модели пользователя как $user->options->get($key) и $user->options->set($key, $value);
 * $value может быть любым сериализуемым типом данных.
 *
 * Для работы в фоне см. controllers\AjaxController и UsersOptionsAsset.
 *
 * При подключении ассета становится доступна асинхронная js-функция set_option(key, value), она сохранит настройку для текущего пользователя. Тип данных value может быть любой.
 * Js-геттера нет, т.к. не пригодился.
 */
class UsersOptions extends Model {

	/**
	 * @var null|int the user identification key. Defaults to null, meaning use current active user id.
	 */
	public $user_id;

	/**
	 * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
	 * After the UsersOptions object is created, if you want to change this property, you should only assign it
	 * with a DB connection object.
	 * This can also be a configuration array for creating the object.
	 */
	public $db = 'db';

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
	 * @var string
	 */
	private $_tableName = 'users_options';

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		parent::init();
		if (null === $this->user_id) $this->user_id = Yii::$app->user->id;
		$this->db = Instance::ensure($this->db, Connection::class);
		$this->_tableName = ArrayHelper::getValue(Yii::$app->modules, 'usersoptions.params.tableName', 'users_options');
		$this->cacheEnabled = ArrayHelper::getValue(Yii::$app->modules, 'usersoptions.params.cacheEnabled', false);
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
		return ArrayHelper::getValue((new Query())->select('value')->from($this->_tableName)->where(['option' => $option, 'user_id' => $this->user_id])->one(), 'value', '');
	}

	/**
	 * @param string $option
	 * @param string $value
	 * @return bool
	 */
	private function setDbValue(string $option, string $value):bool {
		try {
			$this->db->noCache(function(Connection $db) use ($option, $value) {
				$db->createCommand()->upsert($this->_tableName, [
					'user_id' => $this->user_id,
					'option' => $option,
					'value' => $value
				])->execute();
				return true;
			});
		} catch (Throwable $e) {
			Yii::warning("Unable to update or insert table value: {$e->getMessage()}", __METHOD__);
		}
		return false;
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
		TagDependency::invalidate(Yii::$app->cache, [static::class."::get({$this->user_id},{$option})"]);
		return $this->setDbValue($option, $this->serialize($value));
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

	/**
	 * @return string
	 */
	public function getTableName():string {
		return $this->_tableName;
	}

}