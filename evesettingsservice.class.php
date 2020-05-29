<?php

require_once 'eve.class.php';

class EveSettingsService
{
    private $eve;
    
    function settings_get()
	{	
        $list = array();

		$stmt = $this->eve->mysqli->prepare
		("
            select 	`{$this->eve->DBPref}settings`.`key`,
                    `{$this->eve->DBPref}settings`.`value`
			from 	`{$this->eve->DBPref}settings`
			where	`{$this->eve->DBPref}settings`.`key` = ?
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return null;
        }
        foreach (func_get_args() as $argument)
        {
            $stmt->bind_param('s', $argument);
            $stmt->execute();
            $stmt->bind_result($key, $value);
            while ($stmt->fetch())
            {
                $list[$key] = $value;
            }    
        }
        $stmt->close();
		return $list;
    }
    
    function settings_update($settings)
    {
        $stmt = $this->eve->mysqli->prepare
        ("
            update  `{$this->eve->DBPref}settings`
            set     `{$this->eve->DBPref}settings`.`value` = ?
            where   `{$this->eve->DBPref}settings`.`key` = ?
		");
		if ($stmt === false)
		{
			trigger_error($this->eve->mysqli->error, E_USER_ERROR);
			return;
        }
        foreach ($settings as $key => $value)
        {
            $stmt->bind_param('ss', $value, $key);
            $stmt->execute();
        }
        $stmt->close();
    }
    
    function __construct(Eve $eve)
	{
		$this->eve = $eve;
	}
}