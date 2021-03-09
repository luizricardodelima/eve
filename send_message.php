<?php
session_start();
require_once 'eve.class.php';

$eve = new Eve();

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
else
{
	$eve->output_html_header(['wysiwyg-editor']);
	$eve->output_navigation([
		$eve->getSetting('userarea_label') => "userarea.php",
		$eve->_('userarea.option.admin.send.message') => null
	]);

	?>
	<div class="section"><?php echo $eve->_('userarea.option.admin.send.message');?></div>
	<div class="dialog_panel">
	<div class="dialog_section">Mensagem<!-- TODO G11N --></div>

	<label for="subject">
	Assunto<!-- TODO G11N -->
	</label>
	<input type="text" name="subject" id="subject"/>

	<label for="message">
	Mensagem<!-- TODO G11N -->
	</label>
	<textarea class="htmleditor" rows="6" cols="50" name="message" id="message"></textarea>
	<button type="button" class="submit" onclick="send_messages()">Enviar<!-- TODO G11N --></button>
	<div style="height:10em; overflow-y: scroll;" id="result_panel">
		
	</div>
	<script>
	var addresses = <?php echo json_encode(array_values($_POST['screenname']));?>;

	function send_messages()
	{
		for (const address of addresses) {
			let subject_ = document.getElementById("subject").value;
			let message_ = tinymce.get("message").getContent();

			$.ajax({
				url: 'service/send_message.php',
				method: 'POST',
				data: {
					address : address,
					subject : subject_ ,
					message : message_
					},
				success: function(result){ 
					var para = document. createElement("p");
					var node = document. createTextNode(address + " - " + result);
					para. appendChild(node);
					document.getElementById("result_panel").appendChild(para);}			
			});
			
		}
	}
	</script>
	</div>
	<?php
	
	$eve->output_html_footer();
}
?>