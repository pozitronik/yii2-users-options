<?php
declare(strict_types = 1);

namespace Tests\Functional;

use app\models\Users;
use Codeception\Test\Unit;
use pozitronik\users_options\controllers\AjaxController;
use pozitronik\users_options\models\UsersOptions;
use Tests\Support\Helper\MigrationHelper;
use Tests\Support\FunctionalTester;
use Throwable;
use Yii;
use yii\base\Exception as BaseException;
use yii\base\InvalidRouteException;
use yii\console\Exception;

/**
 * Test AJAX controller functionality
 */
class AjaxControllerTest extends Unit {

	protected FunctionalTester $tester;

	/**
	 * @return void
	 * @throws Exception
	 * @throws InvalidRouteException
	 */
	protected function _before():void {
		MigrationHelper::migrateFresh(['migrationPath' => ['@app/migrations/', '@app/../../migrations']]);
	}

	/**
	 * Test user set option action
	 * @return void
	 * @throws BaseException
	 * @throws Throwable
	 */
	public function testUserSetOptionAction():void {
		$user = Users::CreateUser()->saveAndReturn();
		
		// Mock user component properly
		$userComponent = Yii::$app->user;
		$originalIdentity = $userComponent->identity;
		$userComponent->setIdentity($user);

		// Set body params directly on the request component
		Yii::$app->request->setBodyParams([
			'key' => 'test_option',
			'value' => 'test_value'
		]);
		
		$controller = new AjaxController('ajax', Yii::$app);
		$result = $controller->actionUserSetOption();

		static::assertEquals([], $result);
		
		// Verify the value was actually set
		static::assertEquals('test_value', UsersOptions::getStatic($user->id, 'test_option'));
		
		// Restore original identity and clean up
		$userComponent->setIdentity($originalIdentity);
		Yii::$app->request->setBodyParams([]);
	}

	/**
	 * Test user get option action
	 * @return void
	 * @throws BaseException
	 * @throws Throwable
	 */
	public function testUserGetOptionAction():void {
		$user = Users::CreateUser()->saveAndReturn();
		
		// Pre-set a value
		UsersOptions::setStatic($user->id, 'get_test', 'retrieved_value');

		// Mock user component properly
		$userComponent = Yii::$app->user;
		$originalIdentity = $userComponent->identity;
		$userComponent->setIdentity($user);

		// Set body params directly on the request component
		Yii::$app->request->setBodyParams([
			'key' => 'get_test'
		]);

		$controller = new AjaxController('ajax', Yii::$app);
		$result = $controller->actionUserGetOption();

		static::assertEquals([
			'key' => 'get_test',
			'value' => 'retrieved_value'
		], $result);
		
		// Restore original identity and clean up
		$userComponent->setIdentity($originalIdentity);
		Yii::$app->request->setBodyParams([]);
	}

	/**
	 * Test user drop option action
	 * @return void
	 * @throws BaseException
	 * @throws Throwable
	 */
	public function testUserDropOptionAction():void {
		$user = Users::CreateUser()->saveAndReturn();
		
		// Pre-set a value to drop
		UsersOptions::setStatic($user->id, 'drop_test', 'to_be_dropped');

		// Mock user component properly
		$userComponent = Yii::$app->user;
		$originalIdentity = $userComponent->identity;
		$userComponent->setIdentity($user);

		// Set body params directly on the request component
		Yii::$app->request->setBodyParams([
			'key' => 'drop_test'
		]);

		$controller = new AjaxController('ajax', Yii::$app);
		$result = $controller->actionUserDropOption();

		static::assertArrayHasKey('key', $result);
		static::assertArrayHasKey('value', $result);
		static::assertEquals('drop_test', $result['key']);

		// Verify the value was actually dropped
		static::assertNull(UsersOptions::getStatic($user->id, 'drop_test'));
		
		// Restore original identity and clean up
		$userComponent->setIdentity($originalIdentity);
		Yii::$app->request->setBodyParams([]);
	}

	/**
	 * Test unauthorized access
	 * @return void
	 */
	public function testUnauthorizedAccess():void {
		// Mock user component with no identity
		$userComponent = Yii::$app->user;
		$originalIdentity = $userComponent->identity;
		$userComponent->setIdentity(null);

		// Set body params directly on the request component
		Yii::$app->request->setBodyParams([
			'key' => 'test_option'
		]);

		$controller = new AjaxController('ajax', Yii::$app);
		
		$result = $controller->actionUserSetOption();
		static::assertEquals(['user' => 'Unauthorized'], $result);

		$result = $controller->actionUserGetOption();
		static::assertEquals(['user' => 'Unauthorized'], $result);

		$result = $controller->actionUserDropOption();
		static::assertEquals(['user' => 'Unauthorized'], $result);

		$result = $controller->actionUserDropAllOptions();
		static::assertEquals(['user' => 'Unauthorized'], $result);

		$result = $controller->actionUserListOptions();
		static::assertEquals(['user' => 'Unauthorized'], $result);
		
		// Restore original identity and clean up
		$userComponent->setIdentity($originalIdentity);
		Yii::$app->request->setBodyParams([]);
	}

	/**
	 * Test missing key parameter
	 * @return void
	 * @throws BaseException
	 */
	public function testMissingKeyParameter():void {
		$user = Users::CreateUser()->saveAndReturn();
		
		// Mock user component properly
		$userComponent = Yii::$app->user;
		$originalIdentity = $userComponent->identity;
		$userComponent->setIdentity($user);

		// Set body params with no key parameter
		Yii::$app->request->setBodyParams([
			'value' => 'test_value' // No 'key' parameter
		]);

		$controller = new AjaxController('ajax', Yii::$app);
		
		$result = $controller->actionUserSetOption();
		static::assertEquals(['key' => 'Not specified'], $result);

		$result = $controller->actionUserGetOption();
		static::assertEquals(['key' => 'Not specified'], $result);

		$result = $controller->actionUserDropOption();
		static::assertEquals(['key' => 'Not specified'], $result);
		
		// Restore original identity and clean up
		$userComponent->setIdentity($originalIdentity);
		Yii::$app->request->setBodyParams([]);
	}
}