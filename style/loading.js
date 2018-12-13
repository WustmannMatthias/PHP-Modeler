function loading(xhr) {
	$('#loading').html(
		"<br><p class='center'>Please wait for project to be analysed...<br><br>"
		+ "<img class='center' src='images/gears.gif' alt='loading gif' /></p>"
	);
	$('#loading').show();
}


function printData(data) {
	$('#loading').hide();
	$('#result').html(data);
	$('#result').show();
}


function ajaxFailed(jqXHR, textStatus, errorThrown) {
	$('#loading').hide();

	var innerHtml = textStatus;
	$('#result').html(data);
	$('#result').show();
}



/**
	Script begin !
*/
$(function() {
	$.ajax({
		url: "crawler.php",
		beforeSend: loading
	})	.done(printData)
		.fail(ajaxFailed);
});
