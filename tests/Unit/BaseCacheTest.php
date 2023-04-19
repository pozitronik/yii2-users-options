<?php
declare(strict_types = 1);

namespace Unit;

use app\models\Users;
use Codeception\Test\Unit;
use pozitronik\users_options\models\UsersOptions;
use Tests\Support\Helper\MigrationHelper;
use Tests\Support\UnitTester;
use Throwable;
use Yii;
use yii\base\Exception as BaseException;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\caching\FileCache;
use yii\console\Exception;

/**
 * Component tests with enabled cache
 */
class BaseCacheTest extends Unit {

	protected UnitTester $tester;

	/**
	 * @return void
	 * @throws Exception
	 * @throws InvalidRouteException
	 * @throws InvalidConfigException
	 */
	protected function _before():void {
		MigrationHelper::migrateFresh(['migrationPath' => ['@app/migrations/', '@app/../../migrations']]);
		Yii::$app->set('cache', [
			'class' => FileCache::class
		]);
//		Yii::$app->cache->flush();
	}

	/**
	 * @return void
	 */
	public function testCachePresent():void {
		$this->tester->assertInstanceOf(FileCache::class, Yii::$app->cache);
	}

	/**
	 * @return void
	 * @throws Throwable
	 * @throws BaseException
	 */
	public function testSetGet():void {
		$user = Users::CreateUser();
		$userOptions = new UsersOptions(['user_id' => $user->id]);
		$randomString = Yii::$app->security->generateRandomString();
		$randomInt = random_int(PHP_INT_MIN, PHP_INT_MAX);
		$randomFloat = random_int(PHP_INT_MIN, PHP_INT_MAX) / random_int(PHP_INT_MIN, PHP_INT_MAX);
		$randomArray = $this->tester::GetRandomArray();
		static::assertTrue($userOptions->set('string', $randomString));
		static::assertTrue($userOptions->set('int', $randomInt));
		static::assertTrue($userOptions->set('float', $randomFloat));
		static::assertTrue($userOptions->set('array', $randomArray));

		static::assertEquals($randomString, $userOptions->get('string'));
		static::assertEquals($randomInt, $userOptions->get('int'));
		static::assertEquals($randomFloat, $userOptions->get('float'));
		static::assertEquals($randomArray, $userOptions->get('array'));

		static::assertNull($userOptions->get('bool'));

	}

	/**
	 * @return void
	 * @throws Throwable
	 * @throws BaseException
	 */
	public function testSetGetStatic():void {
		$user = Users::CreateUser();
		$randomString = Yii::$app->security->generateRandomString();
		$randomInt = random_int(PHP_INT_MIN, PHP_INT_MAX);
		$randomFloat = random_int(PHP_INT_MIN, PHP_INT_MAX) / random_int(PHP_INT_MIN, PHP_INT_MAX);
		$randomArray = $this->tester::GetRandomArray();
		static::assertTrue(UsersOptions::setStatic($user->id, 'string', $randomString));
		static::assertTrue(UsersOptions::setStatic($user->id, 'int', $randomInt));
		static::assertTrue(UsersOptions::setStatic($user->id, 'float', $randomFloat));
		static::assertTrue(UsersOptions::setStatic($user->id, 'array', $randomArray));

		static::assertEquals($randomString, UsersOptions::getStatic($user->id, 'string'));
		static::assertEquals($randomInt, UsersOptions::getStatic($user->id, 'int'));
		static::assertEquals($randomFloat, UsersOptions::getStatic($user->id, 'float'));
		static::assertEquals($randomArray, UsersOptions::getStatic($user->id, 'array'));

		static::assertNull(UsersOptions::getStatic($user->id, 'bool'));
	}

	/**
	 * @return void
	 * @throws BaseException
	 * @throws Throwable
	 */
	public function testSetGetViaTrait():void {
		$user = Users::CreateUser();
		$randomString = Yii::$app->security->generateRandomString();
		$randomInt = random_int(PHP_INT_MIN, PHP_INT_MAX);
		$randomFloat = random_int(PHP_INT_MIN, PHP_INT_MAX) / random_int(PHP_INT_MIN, PHP_INT_MAX);
		$randomArray = $this->tester::GetRandomArray();
		static::assertTrue($user->options->set('string', $randomString));
		static::assertTrue($user->options->set('int', $randomInt));
		static::assertTrue($user->options->set('float', $randomFloat));
		static::assertTrue($user->options->set('array', $randomArray));

		static::assertEquals($randomString, $user->options->get('string'));
		static::assertEquals($randomInt, $user->options->get('int'));
		static::assertEquals($randomFloat, $user->options->get('float'));
		static::assertEquals($randomArray, $user->options->get('array'));

		static::assertNull($user->options->get('bool'));
	}

	/**
	 * @return void
	 * @throws BaseException
	 */
	public function testList():void {
		$user = Users::CreateUser();
		$randomString = Yii::$app->security->generateRandomString();
		$randomInt = random_int(PHP_INT_MIN, PHP_INT_MAX);
		$randomFloat = random_int(PHP_INT_MIN, PHP_INT_MAX) / random_int(PHP_INT_MIN, PHP_INT_MAX);
		$randomArray = $this->tester::GetRandomArray();
		static::assertTrue($user->options->set('string', $randomString));
		static::assertTrue($user->options->set('int', $randomInt));
		static::assertTrue($user->options->set('float', $randomFloat));
		static::assertTrue($user->options->set('array', $randomArray));

		static::assertEquals([
			'string' => $randomString,
			'int' => $randomInt,
			'float' => $randomFloat,
			'array' => $randomArray,
		], $user->options->list());

		static::assertEquals([
			'string' => $randomString,
			'int' => $randomInt,
			'float' => $randomFloat,
			'array' => $randomArray,
		], UsersOptions::listStatic($user->id));
	}

	/**
	 * @return void
	 * @throws BaseException
	 * @throws Throwable
	 */
	public function testDrop():void {
		$user = Users::CreateUser();
		$randomString = Yii::$app->security->generateRandomString();
		static::assertTrue($user->options->set('string', $randomString));
		static::assertEquals($randomString, $user->options->get('string'));
		static::assertTrue($user->options->drop('string'));
		static::assertNull($user->options->get('string'));

		static::assertTrue(UsersOptions::setStatic($user->id, 'string', $randomString));
		static::assertEquals($randomString, UsersOptions::getStatic($user->id, 'string'));
		static::assertTrue(UsersOptions::dropStatic($user->id, 'string'));
		static::assertNull(UsersOptions::getStatic($user->id, 'string'));
	}

	/**
	 * @return void
	 * @throws BaseException
	 * @throws Throwable
	 */
	public function testDropAll():void {
		$user = Users::CreateUser();
		$randomString = Yii::$app->security->generateRandomString();
		$randomInt = random_int(PHP_INT_MIN, PHP_INT_MAX);
		static::assertTrue($user->options->set('string', $randomString));
		static::assertTrue($user->options->set('int', $randomInt));
		static::assertEquals($randomString, $user->options->get('string'));
		static::assertEquals($randomInt, $user->options->get('int'));
		static::assertTrue($user->options->dropAll());
		static::assertNull($user->options->get('string'));
		static::assertNull($user->options->get('int'));

		static::assertTrue(UsersOptions::setStatic($user->id, 'string', $randomString));
		static::assertTrue(UsersOptions::setStatic($user->id, 'int', $randomInt));
		static::assertEquals($randomString, UsersOptions::getStatic($user->id, 'string'));
		static::assertEquals($randomInt, UsersOptions::getStatic($user->id, 'int'));
		static::assertTrue(UsersOptions::dropAllStatic($user->id));
		static::assertNull(UsersOptions::getStatic($user->id, 'string'));
		static::assertNull(UsersOptions::getStatic($user->id, 'int'));
	}

}
