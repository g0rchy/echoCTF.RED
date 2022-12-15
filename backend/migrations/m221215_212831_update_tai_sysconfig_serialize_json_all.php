<?php

use yii\db\Migration;

/**
 * Class m221215_212831_update_tai_sysconfig_serialize_json_all
 */
class m221215_212831_update_tai_sysconfig_serialize_json_all extends Migration
{
  public $DROP_SQL = "DROP TRIGGER IF EXISTS {{%tai_sysconfig}}";
  public $CREATE_SQL = "CREATE TRIGGER {{%tai_sysconfig}} AFTER INSERT ON {{%sysconfig}} FOR EACH ROW
    thisBegin:BEGIN
      IF (@TRIGGER_CHECKS = FALSE) THEN
          LEAVE thisBegin;
      END IF;

      IF (select memc_server_count()<1) THEN
        select memc_servers_set('127.0.0.1') INTO @memc_server_set_status;
      END IF;
      DO memc_set('sysconfig_json',(SELECT CONCAT('[',GROUP_CONCAT(JSON_OBJECT('id', id,'val',val) ORDER BY id),']') FROM sysconfig WHERE id NOT LIKE 'CA%' and id NOT IN ('disabled_routes','frontpage_scenario','routes','writeup_rules','vpn-ta.key') ORDER BY id));
      DO memc_set(CONCAT('sysconfig:',NEW.id),NEW.val);
    END";

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
