<?php

declare(strict_types = 1);

namespace Tests\Support;

use Codeception\Actor;
use Yii;
use yii\base\Exception;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class UnitTester extends Actor {
	use _generated\UnitTesterActions;

	/**
	 * @return array
	 * @throws Exception
	 */
	public static function GetRandomArray():array {
		return array_map(static function() {
			return match (random_int(1, 3)) {
				1 => Yii::$app->security->generateRandomString(),
				2 => random_int(PHP_INT_MIN, PHP_INT_MAX),
				3 => random_int(PHP_INT_MIN, PHP_INT_MAX) / random_int(PHP_INT_MIN, PHP_INT_MAX),
			};
		}, range(1, random_int(1, 100)));
	}
}
