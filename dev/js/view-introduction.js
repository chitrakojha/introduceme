$(document).ready(function() {
	$("time.timeago").timeago();

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
				_gaq.push(["_trackPageview", "/view-introduction/save-email/"+im.userType]);
				$("#submitEmail").removeAttr('disabled');
				if (data.result != "true") {
					$("#email").addClass("invalid");
				} else {
					$("#formEmail").hide();
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				im.showDialog('Error loading AJAX', 'Please try again later.', null);
				$("#submitEmail").removeAttr('disabled');
			}
		});
		return false;
	});

	$("#formMessage").submit(function() {
		$("#submitMessage").attr('disabled', 'disabled');
		$.ajax({
			type: "POST",
			url: "/en/ajax/send-message/",
			data: $("#formMessage").serialize(),
			dataType: "json",
			cache: false,
			success: function(data) {
				// Response handler
				_gaq.push(["_trackPageview", "/view-introduction/send-message/"+im.userType]);
				$("#submitMessage").removeAttr('disabled');
				if (data.result != "true") {
					$("#message").addClass("invalid");
				} else {
					$(".displayingMessages").show();
					$("#message").removeClass("invalid").val("");
					$("#messages").prepend('<div class="message"><h2>'+im.youWrote+'</h2>'+data.time+'<p>'+data.message+'</p></div>');
					$("#messages div:first-child time").timeago();
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				im.showDialog('Error sending message', 'Please try again later.', null);
				$("#submitMessage").removeAttr('disabled');
			}
		});
		return false;
	});

});
