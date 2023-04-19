<?php
declare(strict_types = 1);

namespace pozitronik\users_options\controllers;

use pozitronik\users_options\models\UsersOptions;
use Throwable;
use Yii;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class AjaxController
 */
class AjaxController extends Controller {

	/**
	 * {@inheritDoc}
	 */
	public function init():void {
		parent::init();
		$this->enableCsrfValidation = false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function behaviors():array {
		return [
			[
				'class' => ContentNegotiator::class,
				'formats' => [
					'application/json' => Response::FORMAT_JSON
				]
			]
		];
	}

	/**
	 * Запоминает настройку пользователя
	 * @return array
	 * @throws Throwable
	 */
	public function actionUserSetOption():array {
		if (false !== $key = Yii::$app->request->post('key', false)) {
			if (null === $user = Yii::$app->user->identity) return (['user' => 'Unauthorized']);//у меня были отдельные базовые ajax-контроллеры со своими типизированными ответами, тут я сделал чтобы просто работало
			$value = Yii::$app->request->post('value', []);
			UsersOptions::setStatic((int)$user->getId(), (string)$key, $value);
			return [];
		}
		return (['key' => 'Not specified']);
	}

	/**
	 * Возвращает настройку пользователя
	 * @return array
	 * @throws Throwable
	 */
	public function actionUserGetOption():array {
		if (false !== $key = Yii::$app->request->post('key', false)) {
			if (null === $user = Yii::$app->user->identity) return (['user' => 'Unauthorized']);
			return [
				'key' => $key,
				'value' => UsersOptions::getStatic((int)$user->getId(), (string)$key)
			];
		}
		return (['key' => 'Not specified']);
	}

	/**
	 * @return array
	 */
	public function actionUserDropOption():array {
		if (false !== $key = Yii::$app->request->post('key', false)) {
			if (null === $user = Yii::$app->user->identity) return (['user' => 'Unauthorized']);
			return [
				'key' => $key,
				'value' => UsersOptions::dropStatic((int)$user->getId(), (string)$key)
			];
		}
		return (['key' => 'Not specified']);
	}

	/**
	 * @return array
	 */
	public function actionUserDropAllOptions():array {
		if (null === $user = Yii::$app->user->identity) return (['user' => 'Unauthorized']);
		return [
			'value' => UsersOptions::dropAllStatic($user->getId())
		];
	}

	/**
	 * @return array
	 */
	public function actionUserListOptions():array {
		if (null === $user = Yii::$app->user->identity) return (['user' => 'Unauthorized']);
		return [
			'value' => UsersOptions::listStatic((int)$user->getId())
		];
	}
}