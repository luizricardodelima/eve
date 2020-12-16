<?php
session_start();
require_once 'eve.class.php';
require_once 'eveuserservice.class.php';

$eve = new Eve();

// If screenname and verificationcode attributes are passed, perform the verification
if (isset($_GET['screenname']) && isset($_GET['verificationcode']))
{
	$EveUserService = new EveUserService($eve);
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Código de verificação", null);

	if ($eve->user_exists($_GET['screenname']))
	{
		$eve->output_success_message("Este usuário já está verificado. Volte à tela principal e use este e-mail e a senha para acessar o sistema.");
		?>		
		<div class="dialog_panel">
		<button class="submit" type="button" onclick="window.location='userarea.php';">Voltar</button>
		</div>
		<?php
	}
	else if ($EveUserService->unverified_user_check_code($_GET['screenname'], $_GET['verificationcode']))
	{
    	// Username with verification code has been found
		$EveUserService->unverified_user_transform_to_user($_GET['screenname']);
		
		// The new user will be authenticated automatically this time
		$_SESSION['screenname'] = $_GET['screenname'];
		$eve->output_redirect_page("userarea.php?emailverificationsuccess=1");		
	}
	else
	{	// Username with verification code has not been found
		$eve->output_error_message(" Usuário não verificado. C&oacute;digo de verifica&ccedil;&atilde;o inválido.");
		?>
		
		<div class="dialog_panel">
		<button class="submit" type="button" onclick="window.location='<?php echo basename(__FILE__)."?screenname={$_GET['screenname']}"; ?>';">Tentar novamente</button>
		</div>

		<?php
	}
	$eve->output_html_footer();
}
// If only the screenname is passed, user will be asked to provide the verification code
else if (isset($_GET['screenname']))
{
	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", "Código de verificação", null);

	?>
	<form id="verificationcode_form" class="dialog_panel" method="get">
	<p>Insira o c&oacute;digo de verifica&ccedil;&atilde;o para <strong><?php echo $_GET['screenname'];?></strong>:</p>	
	
	<label for="verficationcode_input">C&oacute;digo de verifica&ccedil;&atilde;o:</label>
	<input type="text" name="verificationcode" id="verficationcode_input"/>
	<input type="hidden" name="screenname" value="<?php echo $_GET['screenname'];?>">
	<button class="submit" type="submit" id="submit_button">Verificar</button>	
	</form>
	<?php
	
	$eve->output_html_footer();
}
// If there are no arguments passed, there is nothing to do. Redirect to user area.
else
{
	$eve->output_redirect_page("userarea.php");
}
?>
