<?php
declare(strict_types = 1);
use yii\db\Migration;

/**
 * Class m000000_000000_create_users_options_table
 */
class m000000_000000_create_users_options_table extends Migration {
	private const TABLE_NAME = 'users_options';
	
	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable(self::TABLE_NAME, [
			'id' => $this->primaryKey(),
			'user_id' => $this->integer()->comment('System user id'),
			'option' => $this->string(256)->notNull()->comment('Option name'),
			'value' => $this->binary()->null()->comment('Serialized option value')
		]);

		$this->createIndex(self::TABLE_NAME.'_user_id', self::TABLE_NAME, 'user_id');
		$this->createIndex(self::TABLE_NAME.'_user_id_option', self::TABLE_NAME, ['user_id', 'option'], true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::TABLE_NAME);
	}

}
