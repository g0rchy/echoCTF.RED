DELIMITER ;;
IF (SELECT count(*) FROM information_schema.tables WHERE table_schema = 'echoCTF' AND table_name = 'devnull' LIMIT 1)>0 THEN
  CALL echoCTF.init_mysql();
  SELECT memc_servers_behavior_set('MEMCACHED_BEHAVIOR_TCP_NODELAY','1') INTO @devnull;
  SELECT memc_servers_behavior_set('MEMCACHED_BEHAVIOR_NO_BLOCK','1') INTO @devnull;
END IF;;
DELIMITER ;
