<?php

use yii\db\Migration;

/**
 * Class m201110_012506_update_init_mysql_procedure
 */
class m201110_012506_update_init_mysql_procedure extends Migration
{
  public $DROP_SQL="DROP PROCEDURE IF EXISTS {{%init_mysql}}";
  public $CREATE_SQL="CREATE PROCEDURE {{%init_mysql}} ()
  BEGIN
    call populate_memcache();
    call calculate_ranks();
    call calculate_country_rank();
    call calculate_team_ranks();
  END";
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
      $this->db->createCommand($this->DROP_SQL)->execute();
      $this->db->createCommand($this->CREATE_SQL)->execute();
    }

    public function down()
    {
      $this->db->createCommand($this->DROP_SQL)->execute();
    }
}
