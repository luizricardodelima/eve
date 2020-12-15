<?php
session_start();
require_once 'eve.class.php';
require_once 'evesubmissionservice.class.php';
require_once 'lib/dynamicform/dynamicform.class.php';

$eve = new Eve();
$eveSubmissionService = new EveSubmissionService($eve);

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

	$eve->output_html_header(['wysiwyg-editor']);
	$eve->output_navigation_bar($eve->getSetting('userarea_label'), "userarea.php", $eve->_('submission_definitions'), "submission_definitions.php", $submission_definition['description'], null);
	?>
	<div class="section">
	<button type="button" onclick="document.forms['submission_definition_form'].submit();"><?php echo $eve->_('common.action.save');?></button>
	<button type="button" onclick="window.location.href='submission_definition_reviewers.php?id=<?php echo $_GET['id'];?>';"><?php echo $eve->_('submission_definition.button.reviewers');?></button>
	<button type="button" onclick="window.location.href='submission_definition_access.php?id=<?php echo $_GET['id'];?>';"><?php echo $eve->_('submission_definition.button.restrict_access');?></button>
	</div>
	<?php

	if (isset ($_GET['msg'])) $eve->output_service_message($_GET['msg']);

	?>
	<form action="<?php echo basename(__FILE__)."?id={$_GET['id']}";?>" method="post" id="submission_definition_form">
	<input type="hidden" name="action" value="save"/>
	<input type="hidden" name="id" value="<?php echo $submission_definition['id'];?>"/>

	<div class="dialog_panel_wide">

	<span>
	<input type="hidden" name="access_restricted" value="0"/>
	<input type="checkbox" id="access_restricted_cbx" name="access_restricted" value="1" <?php if ($submission_definition['access_restricted']) echo "checked=\"checked\"";?> />
	<label for="access_restricted_cbx"><?php echo $eve->_('submission_definition.access_restricted');?></label>
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

	<span>
	<input type="hidden" name="send_email_on_create" value="0"/>
	<input type="checkbox" id="send_email_on_create" name="send_email_on_create" value="1" <?php if ($submission_definition['send_email_on_create']) echo "checked=\"checked\"";?> />
	<label for="send_email_on_create"><?php echo $eve->_('submission_definition.send_email_on_create');?></label>
	</span>

	<span>
	<input type="hidden" name="send_email_on_delete" value="0"/>
	<input type="checkbox" id="send_email_on_delete" name="send_email_on_delete" value="1" <?php if ($submission_definition['send_email_on_delete']) echo "checked=\"checked\"";?> />
	<label for="send_email_on_delete"><?php echo $eve->_('submission_definition.send_email_on_delete');?></label>
	</span>

	<span>
	<input type="hidden" name="send_email_on_update" value="0"/>
	<input type="checkbox" id="send_email_on_update" name="send_email_on_update" value="1" <?php if ($submission_definition['send_email_on_update']) echo "checked=\"checked\"";?> />
	<label for="send_email_on_update"><?php echo $eve->_('submission_definition.send_email_on_update');?></label>
	</span>


	<label><?php echo $eve->_('submission_definition.submission.structure');?><br/>
	<small><?php echo $eve->_('submission_definition.submission.structure.help');?></small></label>
	<?php
	
	DynamicFormHelper::$locale = $eve->getSetting('system_locale');
	$submissionStructure = new DynamicForm($submission_definition['submission_structure']);
	echo $submissionStructure->outputStructureTable('submission_structure', 'data_table');
	
	?>
	<label><?php echo $eve->_('submission_definition.revision.structure');?></label>
	<?php
	
	$revisionStructure = new DynamicForm($submission_definition['revision_structure']);
	echo $revisionStructure->outputStructureTable('revision_structure', 'data_table');
	
	?>
	</div>
	</form>
	<?php
	$eve->output_html_footer();
}
?>