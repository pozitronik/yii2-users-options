<?php
declare(strict_types = 1);

namespace pozitronik\users_options\controllers;

use pozitronik\users_options\traits\UsersOptionsTrait;
use Throwable;
use Yii;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class AjaxController
 * @package app\controllers
 */
class AjaxController extends Controller {

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
			/** @var UsersOptionsTrait $user */
			$user->options->set((string)$key, (array)$value);
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
			/** @var UsersOptionsTrait $user */
			return $user->options->get((string)$key);
		}
		return (['key' => 'Not specified']);
	}
}