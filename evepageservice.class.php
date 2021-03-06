<?php

require_once 'eve.class.php';

class EvePageService
{
	private $eve;

	function page_change_position($id, $newposition)
	{
		// TODO: Return values
		// TODO: Prepared statements
		$page_res = $this->eve->mysqli->query("SELECT * FROM `{$this->eve->DBPref}page` WHERE `id` = $id;");			
		$page = $page_res->fetch_assoc();
		$oldposition = $page['position'];
		$this->eve->mysqli->query("UPDATE `{$this->eve->DBPref}page` SET `position` = $oldposition WHERE `position` = $newposition;");
		$this->eve->mysqli->query("UPDATE `{$this->eve->DBPref}page` SET `position` = $newposition WHERE `id` = $id;");
	}

	function page_create()
	{
		// TODO: Return values
		// TODO: Prepared statements
		// TODO: Search for the highest position and insert that + 1
		$this->eve->mysqli->query("insert into `{$this->eve->DBPref}page` () values ();");
	}

	function page_delete($id)
	{
		// TODO: Return values
		$stmt = $this->eve->mysqli->prepare("delete from `{$this->eve->DBPref}page` where `{$this->eve->DBPref}page`.`id` = ?;");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
	}

	/**Returns the content of the page represented by its $id. If no page is found, returns
	 * null. */
	function page_get_content($id)
	{
		$stmt = $this->eve->mysqli->prepare
		("
			select `content` from `{$this->eve->DBPref}page` where `id`= ? ;
		");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->store_result();
		if($stmt->num_rows > 0)
		{	
			$this->page_increase_view_count($id);
			$stmt->bind_result($content);
			$stmt->fetch();
			return $content;
		}
		else
		{
			$stmt->close();
			return null;
		}
	}

	/**Returns the page set as homepage. If there are multiple pages set as homepage
	 * it will only return one result, in no particular order. If no page is set as
	 * homepage, it returns null. */
	function page_get_homepage()
	{
		$page_res = $this->eve->mysqli->query
		("
			select `id` from `{$this->eve->DBPref}page` where `is_homepage` = 1;
		");
		$page = $page_res->fetch_assoc();
		if ($page) return $page['id'];
		else return null;
	}

	function page_increase_view_count($id)
	{
		$stmt2 = $this->eve->mysqli->prepare("update `{$this->eve->DBPref}page` set `views` = `views`+ 1 where `id` = ?;");
		$stmt2->bind_param('i', $id);
		$stmt2->execute();
		$stmt2->close();
	}

	function page_list($only_visible = true)
	{
		$page_query = ($only_visible) ?
		"
			select *
			from `{$this->eve->DBPref}page`
			where `{$this->eve->DBPref}page`.`is_visible` = 1
			order by `{$this->eve->DBPref}page`.`position`;
		"
		:
		"
			select *
			from `{$this->eve->DBPref}page`
			order by `{$this->eve->DBPref}page`.`position`;
		";
		$page_res = $this->eve->mysqli->query($page_query);
		$result = array();
		while ($page = $page_res->fetch_assoc()) $result[] = $page;
		$page_res->close();
		return $result;
	}

	function __construct(Eve $eve)
	{
		$this->eve = $eve;
	}
}
?>
