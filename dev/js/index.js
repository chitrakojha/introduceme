im.index = (function() {
	var friends = [];
	var introducee1, introducee2;

	var loadFriends = function(network) {
		log('Loading '+network+' friends');
		$.ajax({
			type: "GET",
			url: "/en/ajax/load-friends/",
			data: "network="+network,
			dataType: "json",
			cache: false,
			success: function(data) {
				// Response handler
				if (data.result == "false") {
					if (data.message) {
						var callback = null;
						if (data.redirect == "true") {
							// Access token was invalid. Display a dialog then redirect the user to the network login page
							var link = $("#login"+network).attr("href");
							callback = function() {
								window.location = link;
							}
						}
						im.showDialog(data.title, data.message, callback);
					}
				} else {
					// Loop through friends and add to the autocomplete
					var net = 'facebook';
					if (network == 'LinkedIn') {
						net = 'linkedIn';
					} else if (network == 'Twitter') {
						net = 'twitter';
					}
					for (var friend in data.friends) {
						var friendData = data.friends[friend];
						// Add this friend to the array if they are not already there
						var existing = false;
						for(var i=0, len=friends.length; i < len; i++) {
							if (friends[i][net+"Id"] == friendData[net+"Id"]) {
								existing = true;
								break;
							}
						}
						if (!existing) {
							friends.push(friendData);
						}
						// Sort the array
						friends.sort(function(a, b) {
							if(a.label > b.label) return 1;
							return -1;
						});
					}
					startAutoComplete();
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				// Don't show dialog or it shows even on exit page
				//im.showDialog('Error loading AJAX', 'Please try again later.', null);
			}
		});
	};

	var startAutoComplete = function() {
		$("#introducee1, #introducee2").autocomplete({
			source: friends,
			select: function(event, ui) {
				$(this).val(ui.item.label);
				if ($(this).attr("id") == "introducee1") {
					introducee1 = ui.item;
				} else {
					introducee2 = ui.item;
				}
				return false;
			}
		});
		$("#introducee1").data("autocomplete")._renderItem = function(ul, item) {
			var output = $("<li></li>");
			output.data("item.autocomplete", item);
			var inner = $("<a>" + item.label + "</a> ");
			if (item.facebookId) {
				inner.append('<div class="ir iconFacebook">&nbsp;</div>');
			}
			if (item.linkedInId) {
				inner.append('<div class="ir iconLinkedIn">&nbsp;</div>');
			}
			if (item.twitterId) {
				inner.append('<div class="ir iconTwitter">&nbsp;</div>');
			}
			output.append(inner);
			output.appendTo(ul);
			return output;
		};
		$("#introducee2").data("autocomplete")._renderItem = function(ul, item) {
			var output = $("<li></li>");
			output.data("item.autocomplete", item);
			var inner = $("<a>" + item.label + "</a> ");
			if (item.facebookId) {
				inner.append('<div class="ir iconFacebook">&nbsp;</div>');
			}
			if (item.linkedInId) {
				inner.append('<div class="ir iconLinkedIn">&nbsp;</div>');
			}
			if (item.twitterId) {
				inner.append('<div class="ir iconTwitter">&nbsp;</div>');
			}
			output.append(inner);
			output.appendTo(ul);
			return output;
		};
	};

	var submitForm = function() {
		// Validate form
		var validationError = false;
		if (!introducee1) {
			$("#introducee1").effect("highlight", {}, 3000);
			validationError = true;
		}
		if (!introducee2) {
			$("#introducee2").effect("highlight", {}, 3000);
			validationError = true;
		}
		if (validationError) {
			return false;
		}
		// Disable the submit button
		$("#submitIntroduce").attr('disabled', 'disabled');
		// Analytics
		_gaq.push(['_trackPageview', '/click-send-introduction']);
		// Send the data
		var inputData = {
			introducee1Name: $("#introducee1").val(),
			introducee1FacebookId: introducee1.facebookId,
			introducee1LinkedInId: introducee1.linkedInId,
			introducee1TwitterId: introducee1.twitterId,
			introducee2Name: $("#introducee2").val(),
			introducee2FacebookId: introducee2.facebookId,
			introducee2LinkedInId: introducee2.linkedInId,
			introducee2TwitterId: introducee2.twitterId,
			message: ($("#message").val() != $("#message").attr("placeholder")) ? $("#message").val() : ""
		};
		$.ajax({
			type: "POST",
			url: "/en/ajax/introduce/",
			data: inputData,
			dataType: "json",
			cache: false,
			success: function(data) {
				// Response handler
				$("#submitIntroduce").removeAttr('disabled');
				if (data.result == "false") {
					if (data.message) {
						var callback = null;
						if (data.redirect == "true") {
							// Access token was invalid. Display a dialog then redirect the user to the network login page
							var link = $("a.login"+network).attr("href");
							callback = function() {
								window.location = link;
							}
						}
						im.showDialog(data.title, data.message, callback);
					}
				} else {
					// The introduction has been saved to the database
					_gaq.push(['_trackPageview', '/introduction-saved']);
					// Redirect the user to send-introduction where notifications will sent
					window.location = data.link;
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				im.showDialog('Error loading AJAX', 'Please try again later.', null);
				$("#submitIntroduce").removeAttr('disabled');
			}
		});
		return false;
	}

	return {
		loadFriends: loadFriends,
		submitForm: submitForm
	}
})();

$(document).ready(function() {
	$("time.timeago").timeago();

	if (im.personId) {
		if (im.facebookId) {
			im.index.loadFriends('Facebook');
		}
		if (im.linkedInId) {
			im.index.loadFriends('LinkedIn');
		}
		if (im.twitterId) {
			im.index.loadFriends('Twitter');
		}
	} else {
		$(':text, textarea').focus(function() {
			$("#loginFirst").css({'display': 'block', 'padding-left': '40px'});
			$('#loginFirst').animate({
				paddingLeft: '10px'
			});
		});
	}
	
	$("#formIntroduce").submit(im.index.submitForm);

	$("#loginFacebook").click(function() {
		_gaq.push(["_trackPageview", "/index/click-login/facebook"]);
		return true;
	});
	$("#loginLinkedIn").click(function() {
		_gaq.push(["_trackPageview", "/index/click-login/linkedin"]);
		return true;
	});
	$("#loginTwitter").click(function() {
		_gaq.push(["_trackPageview", "/index/click-login/twitter"]);
		return true;
	});

	$("a.help").click(function() {
		_gaq.push(["_trackPageview", "/index/click-learn-more"]);
		return true;
	});
});

