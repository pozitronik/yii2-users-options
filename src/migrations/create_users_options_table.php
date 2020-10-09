<?php
declare(strict_types = 1);
use yii\db\Migration;

/**
 * Class create_users_options_table
 */
class create_users_options_table extends Migration {
	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable('users_options', [
			'id' => $this->primaryKey(),
			'user_id' => $this->integer()->comment('System user id'),
			'option' => $this->string(32)->notNull()->comment('Option name'),
			'value' => $this->text()->null()->comment('Serialized option value')
		]);

		$this->createIndex('user_id', 'users_options', 'user_id');
		$this->createIndex('user_id_option', 'users_options', ['user_id', 'option'], true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable('users_options');
	}

}
