<?php
declare(strict_types = 1);

namespace Tests\Unit;

use Codeception\Test\Unit;
use Tests\Support\Helper\MigrationHelper;
use Tests\Support\UnitTester;
use Yii;
use yii\base\Application;
use yii\base\InvalidRouteException;
use yii\console\Exception;

/**
 *
 */
class BaseTest extends Unit {

	protected UnitTester $tester;

	/**
	 * @return void
	 * @throws Exception
	 * @throws InvalidRouteException
	 */
	protected function _before():void {
		MigrationHelper::migrateFresh(['migrationPath' => ['@app/migrations/', '@app/../../migrations']]);
	}

	/**
	 * @return void
	 */
	public function testYiiPresent():void {
		$this->tester->assertInstanceOf(Application::class, Yii::$app);
	}

}
