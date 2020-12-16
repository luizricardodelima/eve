<?php
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$eve->output_html_header();
$eve->output_navigation([
	$eve->getSetting('userarea_label') => "userarea.php",
	$eve->_('passwordretrieval') => null
]);

if (!isset($_POST['screenname']))
{
	?>
	<script>
	function disable_submit_button()
	{
		document.getElementById('submit_button').disabled = 1;
		document.getElementById('submit_button').innerHTML = '<?php echo $eve->_('common.action.pleasewait');?>';
	}
	</script>
		
	<form method="post" autocomplete="off" onsubmit="disable_submit_button();" class="dialog_panel">
	<p><?php echo $eve->_('passwordretrieval.intro');?></p>
	<label for="screenname_txt"><?php echo $eve->_('passwordretrieval.field.email');?></label>
	<input type="text" name="screenname" id="screenname_txt" class="login_form"/>
	<button type="submit" class="submit" id="submit_button"><?php echo $eve->_('passwordretrieval.option.retrieve');?></button>
	
	<span><a href="userarea.php"><?php echo $eve->_('common.action.back');?></a> | 
	<a href="mailto:<?php echo $eve->getSetting('support_email_address'); ?>"><?php echo $eve->_('common.action.support');?></a> 
	</span>
	</form>
	<?php
}
else
{
	$EveUserService = new EveUserService($eve);
	$EveUserService->user_retrieve_password($_POST['screenname']);
	?>
	<div class="dialog_panel">
	<p><?php echo $eve->_('passwordretrieval.result');?></p>
	
	<span><a href="userarea.php"><?php echo $eve->_('common.action.back');?></a> | 
	<a href="mailto:<?php echo $eve->getSetting('support_email_address'); ?>"><?php echo $eve->_('common.action.support');?></a>
	</span>
	</div>
	<?php
}

$eve->output_html_footer();
?>
