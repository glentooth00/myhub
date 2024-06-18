UPDATE `trades` SET `percent_return` = (`zar_profit` / `zar_sent`) * 100 
WHERE `trades`.`id` >= 23296 AND `trades`.`id` <= 23338 AND `zar_sent` != 0;