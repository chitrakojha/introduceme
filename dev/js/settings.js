$(document).ready(function() {
	$("#formEmail").submit(function() {
		$("#submitEmail").attr('disabled', 'disabled');
		$.ajax({
			type: "POST",
			url: "/en/ajax/save-email/",
			data: $("#formEmail").serialize(),
			dataType: "json",
			cache: false,
			success: function(data) {
				// Response handler
				$("#submitEmail").removeAttr('disabled');
				if (data.result == "false") {
					$("#email").addClass("invalid");
				} else {
					$("#email").removeClass("invalid");
					im.showDialog(im.success, im.saved, null);
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				im.showDialog('Error loading AJAX', 'Please try again later.', null);
				$("#submitEmail").removeAttr('disabled');
			}
		});
		return false;
	});
});
