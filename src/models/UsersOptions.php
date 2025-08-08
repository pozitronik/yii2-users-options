<?php
declare(strict_types = 1);

namespace pozitronik\users_options\models;

use Exception;
use Throwable;
use Yii;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\helpers\ArrayHelper;

/**
 * @property null|int $user_id The user identification key. Defaults to null, meaning use current active user id.
 * @property Connection|array|string $db The DB connection object or the application component ID of the DB connection.
 * @property null|array $serializer The functions used to serialize and unserialize values. Defaults to null, meaning
 * using the default PHP `serialize()` and `unserialize()` functions
 * @property bool $cacheEnabled Enable intermediate caching via Yii::$app->cache (must be configured in framework)
 * @property-read string $tableName Configured storage table name
 *
 * Функции доступны у модели пользователя как $user->options->get($key) и $user->options->set($key, $value);
 * $value может быть любым сериализуемым типом данных.
 *
 * Для работы в фоне см. controllers\AjaxController и UsersOptionsAsset.
 *
 * При подключении ассета становятся доступны
 * 1) асинхронная js-функция set_option(key, value), она сохранит настройку для текущего пользователя. Тип данных value может быть любой.
 * 2) асинхронная js-функция get_option(key, callback, defaultValue), вызывающая функцию callback с ранее полученным значением в качестве параметра (либо со значением defaultValue, если значение не сохранялось ранее)
 */
class UsersOptions extends Model {

	/**
	 * @var null|int the user identification key. Defaults to null, meaning use current active user id.
	 */
	public ?int $user_id = null;

	/**
	 * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
	 * After the UsersOptions object is created, if you want to change this property, you should only assign it
	 * with a DB connection object.
	 * This can also be a configuration array for creating the object.
	 */
	public Connection|array|string $db = 'db';

	/**
	 * @var null|array the functions used to serialize and unserialize values. Defaults to null, meaning
	 * using the default PHP `serialize()` and `unserialize()` functions. If you want to use some more efficient
	 * serializer (e.g. [igbinary](https://pecl.php.net/package/igbinary)), you may configure this property with
	 * a two-element array. The first element specifies the serialization function, and the second the deserialization
	 * function.
	 */
	public ?array $serializer = null;
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
	public bool $cacheEnabled = false;
	private string $_tableName = 'users_options';

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
	 * @param mixed $value
	 * @return string
	 */
	protected function serialize(mixed $value):string {
		return (null === $this->serializer)
			?serialize($value)
			:call_user_func($this->serializer[0], $value);
	}

	/**
	 * @param string $value
	 * @return mixed
	 */
	protected function unserialize(string $value) {
		return (null === $this->serializer)
			?unserialize($value, ['allowed_classes' => true])
			:call_user_func($this->serializer[1], $value);
	}

	/**
	 * @return array
	 */
	protected function retrieveAllValues():array {
		$values = ArrayHelper::map(new Query()->select(['option', 'value'])->from($this->_tableName)->where(['user_id' => $this->user_id])->all(), 'option', 'value');
		return array_map(static function($value):string {
			if (is_resource($value) && 'stream' === get_resource_type($value)) {
				$result = stream_get_contents($value);
				fseek($value, 0);
				return $result;
			}
			return $value;
		}, $values);
	}

	/**
	 * @param string $option
	 * @return string
	 * @throws Exception
	 */
	protected function retrieveDbValue(string $option):string {
		$value = ArrayHelper::getValue(new Query()->select('value')->from($this->_tableName)->where(['option' => $option, 'user_id' => $this->user_id])->one(), 'value', serialize(null));
		if (is_resource($value) && 'stream' === get_resource_type($value)) {
			$result = stream_get_contents($value);
			fseek($value, 0);
			return $result;
		}
		return $value;
	}

	/**
	 * @param string $option
	 * @param string $value
	 * @return bool
	 */
	protected function applyDbValue(string $option, string $value):bool {
		try {
			return $this->db->noCache(function(Connection $db) use ($option, $value) {
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
	 * @return bool
	 */
	private function removeDbValue(string $option):bool {
		try {
			return $this->db->noCache(function(Connection $db) use ($option) {
				$db->createCommand()->delete($this->_tableName, [
					'user_id' => $this->user_id,
					'option' => $option,
				])->execute();
				return true;
			});
		} catch (Throwable $e) {
			Yii::warning("Unable to delete table value: {$e->getMessage()}", __METHOD__);
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function removeAllDBValues():bool {
		try {
			return $this->db->noCache(function(Connection $db) {
				$db->createCommand()->delete($this->_tableName, [
					'user_id' => $this->user_id,
				])->execute();
				return true;
			});
		} catch (Throwable $e) {
			Yii::warning("Unable to delete table value: {$e->getMessage()}", __METHOD__);
		}
		return false;
	}

	/**
	 * @param string $option
	 * @return mixed
	 * @throws Throwable
	 */
	public function get(string $option) {
		$dbValue = ($this->cacheEnabled)
			?Yii::$app->cache->getOrSet(
				static::class."::get({$option})",
				fn() => $this->retrieveDbValue($option),
				null,
				new TagDependency(['tags' => static::class."::get({$option})", static::class."::dropAll()"])
			)
			:$this->retrieveDbValue($option);
		return $this->unserialize($dbValue);
	}

	/**
	 * @param string $option
	 * @param mixed $value
	 * @return bool
	 */
	public function set(string $option, mixed $value):bool {
		TagDependency::invalidate(Yii::$app->cache, [static::class."::get({$this->user_id},{$option})", static::class."::list()"]);
		return $this->applyDbValue($option, $this->serialize($value));
	}

	/**
	 * @param string $option
	 * @return bool
	 */
	public function drop(string $option):bool {
		TagDependency::invalidate(Yii::$app->cache, [static::class."::get({$this->user_id},{$option})", static::class."::list()"]);
		return $this->removeDbValue($option);
	}

	/**
	 * @return array
	 */
	public function list():array {
		$dbValues = ($this->cacheEnabled)
			?Yii::$app->cache->getOrSet(static::class."::list()", fn() => $this->retrieveAllValues(), null, new TagDependency(['tags' => static::class."::list()"]))
			:$this->retrieveAllValues();
		return array_map(fn(string $value):mixed => $this->unserialize($value), $dbValues);
	}

	/**
	 * @return bool
	 */
	public function dropAll():bool {
		TagDependency::invalidate(Yii::$app->cache, [static::class."::dropAll()", static::class."::list()"]);
		return $this->removeAllDBValues();
	}

	/**
	 * Статический вызов с той же логикой, что у get()
	 * @param int $user_id
	 * @param string $option
	 * @return mixed
	 * @throws Throwable
	 */
	public static function getStatic(int $user_id, string $option):mixed {
		return new self(['user_id' => $user_id])->get($option);
	}

	/**
	 * Статический вызов с той же логикой, что у set()
	 * @param int $user_id
	 * @param string $option
	 * @param mixed $value
	 * @return bool
	 */
	public static function setStatic(int $user_id, string $option, mixed $value):bool {
		return new self(['user_id' => $user_id])->set($option, $value);
	}

	/**
	 * Статический вызов с той же логикой, что у drop()
	 * @param int $user_id
	 * @param string $option
	 * @return bool
	 */
	public static function dropStatic(int $user_id, string $option):bool {
		return new self(['user_id' => $user_id])->drop($option);
	}

	/**
	 * Статический вызов с той же логикой, что у list()
	 * @param int $user_id
	 * @return array
	 */
	public static function listStatic(int $user_id):array {
		return new self(['user_id' => $user_id])->list();
	}

	/**
	 * Статический вызов с той же логикой, что у dropAll()
	 * @param int $user_id
	 * @return bool
	 */
	public static function dropAllStatic(int $user_id):bool {
		return new self(['user_id' => $user_id])->dropAll();
	}

	/**
	 * @return string
	 */
	public function getTableName():string {
		return $this->_tableName;
	}

}