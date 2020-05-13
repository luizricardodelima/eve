<?php
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();
$eve->output_html_header();
$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Esqueci minha senha", null);

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
		
	<form method="post" autocomplete="off" onsubmit="disable_submit_button();" class="user_dialog_panel">
	<p><?php echo $eve->_('passwordretrieval.intro');?></p>
	<label for="screenname_txt">E-mail&nbsp;</label>
	<input type="text" name="screenname" id="screenname_txt" class="login_form"/>
	<button type="submit" class="submit" id="submit_button"><?php echo $eve->_('passwordretrieval.option.retrieve');?></button>
	<p>
	<a href="userarea.php"><?php echo $eve->_('common.action.back');?></a> | 
	<a href="mailto:<?php echo $eve->getSetting('support_email_address'); ?>"><?php echo $eve->_('common.action.support');?></a> 
	</p>
	</form>
	<?php
}
else
{
	$eveUserServices = new EveUserServices($eve);
	$eveUserServices->retrievePassword($_POST['screenname']);
	?>
	<div class="user_dialog_panel">
	<p>A senha foi enviada para o e-mail informado, caso esteja cadastrado. Se você estiver com dificuldade em recuperar sua senha, entre em contato com o suporte.</p>
	<p>
	<a href="userarea.php"><?php echo $eve->_('common.action.back');?></a> | 
	<a href="mailto:<?php echo $eve->getSetting('support_email_address'); ?>"><?php echo $eve->_('common.action.support');?></a> 
	</p>
	</div>
	<?php
}

$eve->output_html_footer();
?>
