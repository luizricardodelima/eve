<?php
session_start();
require_once 'eve.class.php';
require_once 'evecustominputservice.class.php';
require_once 'evesubmissionservice.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);
$eveCustomInputService = new EveCustomInputService($eve);

// Session verification.
if (!isset($_SESSION['screenname']))
{	
	$eve->output_redirect_page("userarea.php?sessionexpired=1");
}
// Administrative privileges verification.
else if (!$eve->is_admin($_SESSION['screenname']))
{
	$eve->output_error_page('common.message.no.permission');
}
// Checking whether an id was passed and whether it is valid
else if (!isset($_GET['id']) || !$eveSubmissionService->submission_definition_get($_GET['id']))
{
	$eve->output_error_page('common.message.invalid.parameter');
}
else if (isset($_POST['action']))switch ($_POST['action'])
{
	case "save":
		// Saving information. All POST variables, except for 'action', are 'submission_definition' fields
		unset($_POST['action']);
		$msg = $eveSubmissionService->submission_definition_save($_POST);
		$eve->output_redirect_page(basename(__FILE__)."?id={$_GET['id']}&msg=$msg");
		break;
}
// If all verifications are successful and there's no post action, display the regular page.
else
{
	$submission_definition = $eveSubmissionService->submission_definition_get($_GET['id']);

	$eve->output_html_header();
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('submission_definitions'), "submission_definitions.php", $submission_definition['description'], null);
	?>
	<div class="section">
	<button type="button" onclick="document.forms['submission_definition_form'].submit();">Salvar</button>
	<button type="button" onclick="window.location.href='submission_definition_reviewers.php?id=<?php echo $_GET['id'];?>';"><?php echo $eve->_('submission_definition.button.reviewers');?></button>
	<button type="button" onclick="window.location.href='submission_definition_access.php?id=<?php echo $_GET['id'];?>';">Gerenciar acesso restrito</button>
	</div>
	<?php
	$eve->output_wysiwig_editor_code();
	if (isset($_GET['msg'])) switch ($_GET['msg'])
	{
		case EveSubmissionService::SUBMISSION_DEFINITION_SAVE_ERROR_SQL:
			$eve->output_error_message('submission_definition.message.save.error.sql');
			break;
		case EveSubmissionService::SUBMISSION_DEFINITION_SAVE_SUCCESS:
			$eve->output_success_message('submission_definition.message.save.success');
			break;
	}
	?>

	<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" id="submission_definition_form">
	<input type="hidden" name="action" value="save"/>
	<input type="hidden" name="id" value="<?php echo $submission_definition['id'];?>"/>

	<div class="user_dialog_panel_large">
	<p></p>

	<span>
	<input type="hidden" name="access_restricted" value="0"/>
	<input type="checkbox" id="access_restricted_cbx" name="access_restricted" value="1" <?php if ($submission_definition['access_restricted']) echo "checked=\"checked\"";?> />
	<label for="access_restricted_cbx">Acesso restrito</label>
	</span>
	
	<span>
	<input type="hidden" name="allow_multiple_submissions" value="0"/>
	<input type="checkbox" id="allow_multiple_submissions_cbx" name="allow_multiple_submissions" value="1" <?php if ($submission_definition['allow_multiple_submissions']) echo "checked=\"checked\"";?> />
	<label for="allow_multiple_submissions_cbx"><?php echo $eve->_('submission_definition.allow_multiple_submissions');?></label>
	</span>

	<label><?php echo $eve->_('submission_definition.deadline');?></label>
	<input type="datetime-local" name="deadline" value="<?php if($submission_definition['deadline']) echo date('Y-m-d\TH:i:s', strtotime($submission_definition['deadline']));?>"/>	

	<label><?php echo $eve->_('submission_definition.description');?></label>
	<textarea rows="1" name="description"><?php echo $submission_definition['description'];?></textarea>

	<label><?php echo $eve->_('submission_definition.information');?></label>
	<textarea class="htmleditor" rows="6" cols="50" name="information"><?php echo $submission_definition['information'];?></textarea>
	
	<label><?php echo $eve->_('submission_definition.requirement');?></label>
	<select name="requirement">
	<?php
	foreach ($eveSubmissionService->submission_definition_requirements() as $submission_definition_requirement)
	{
		$s = ($submission_definition['requirement'] == $submission_definition_requirement) ? " selected = selected" : "";
		echo "<option value=\"$submission_definition_requirement\" $s>".$eve->_("submission_definition.requirement.$submission_definition_requirement")."</option>";
	}
	?>
	</select>
	<p></p>
	</div>
	<?php
	
	$eveCustomInputService->custom_input_output_structure_table('submission_structure', $submission_definition['submission_structure'], $eve->_('submission_definition.submission.structure'));
	$eveCustomInputService->custom_input_output_structure_table('revision_structure', $submission_definition['revision_structure'], $eve->_('submission_definition.revision.structure'));
	
	?>
	</form>
	<?php
	$eve->output_html_footer();
}
?>