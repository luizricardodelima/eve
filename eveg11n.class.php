<?php
require_once 'eve.class.php';

/**  TODO deprecated */
class EveG11n
{
	private $eve;
	private $full_date_time_formatter;
	private $compact_date_time_formatter;

	function full_date_time_format($date)
	{ 	
		if ($this->full_date_time_formatter)
		{	
			return $this->full_date_time_formatter->format($date);
		}
		else
		{
			return strftime('%x %X', $date);
		}
	}

	function compact_date_time_format($date)
	{ 	
		if ($this->compact_date_time_formatter)
		{	
			return $this->compact_date_time_formatter->format($date);
		}
		else
		{
			return strftime('%x %X', $date);
		}
	}

	function __construct(Eve $eve)
	{
		$this->eve = $eve;
		setlocale(LC_TIME, $this->eve->getSetting('system_locale'));
		if (class_exists('IntlDateFormatter'))
		{
			$this->full_date_time_formatter = new IntlDateFormatter($this->eve->getSetting('system_locale'),
					                    IntlDateFormatter::FULL,
					                    IntlDateFormatter::SHORT);
			$this->compact_date_time_formatter = new IntlDateFormatter($this->eve->getSetting('system_locale'),
					                    IntlDateFormatter::SHORT,
					                    IntlDateFormatter::SHORT);
		}
	}
}
?>
