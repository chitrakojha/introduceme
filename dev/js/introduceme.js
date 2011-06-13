/*globals log, _gaq */
var introduceme = (function (module) {

	var $body, showDialog, setupHomePage, setupSettingsPage, setupViewIntroductionPage;

	$(document).ready(function () {
		$body = $("body");
		$("time.timeago").timeago();
		$("input, textarea").placeholder();

		setupHomePage();
		setupSettingsPage();
		setupViewIntroductionPage();
	});

	setupHomePage = function () {
		var friends = [], $friends1, $friends2, friendsAddedToDom = false, introducee1, introducee2, loadFriends, filterFriends, buildFriendSelectors, $filteredFriends;
		if (!$body.hasClass("homePage")) {
			return false;
		}

		$filteredFriends = $(".filteredFriends");

		loadFriends = function(network) {
			log('Loading ' + network + ' friends');
			$.ajax({
				type: "GET",
				url: "/en/ajax/load-friends/",
				data: "network=" + network,
				dataType: "json",
				cache: false,
				success: function(data) {
					var callback, link, net, i, len, existing, j, friendsLen;
					// Response handler
					if (data.result === "false") {
						if (data.message) {
							if (data.redirect === "true") {
								// Access token was invalid. Display a dialog then redirect the user to the network login page
								link = $("#login" + network).attr("href");
								callback = function () {
									window.location = link;
								};
							}
							module.showDialog(data.title, data.message, callback);
						}
					} else {
						// Loop through friends and add to the autocomplete
						net = 'facebook';
						if (network === 'LinkedIn') {
							net = 'linkedIn';
						} else if (network === 'Twitter') {
							net = 'twitter';
						}
						for (i = 0, len = data.friends.length; i < len; i += 1) {
							// Add this friend to the array if they are not already there
							existing = false;
							for (j = 0, friendsLen = friends.length; j < friendsLen; j += 1) {
								if (friends[j][net + "Id"] === data.friends[i][net + "Id"]) {
									existing = true;
									break;
								}
							}
							if (!existing) {
								data.friends[i].nameUpperCase = data.friends[i].name.toUpperCase();
								friends.push(data.friends[i]);
							}
						}
						friendsAddedToDom = false;
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					// Don't show dialog or it shows even on exit page
					//module.showDialog('Error loading AJAX', 'Please try again later.', null);
				}
			});
		};

		$("#introducee1").bind("keyup", function(e) {
			filterFriends($(this).val(), $friends1);
		});

		$("#introducee2").bind("keyup", function(e) {
			filterFriends($(this).val(), $friends2);
		});

		filterFriends = function (input, $output) {
			var i, len;
			if (!friendsAddedToDom) {
				buildFriendSelectors();
			}
			if (input.length < 3) {
				if ($output) {
					$output.removeClass("filtered");
					$output.parent().hide();
				}
				return;
			}
			$output.parent().show();
			input = input.toUpperCase();
			for (i = 0, len = friends.length; i < len; i += 1) {
				if (friends[i].nameUpperCase.indexOf(input) === -1) {
					$($output[i]).addClass("filtered");
				} else {
					$($output[i]).removeClass("filtered");
				}
			}
		};

		buildFriendSelectors = function () {
			var friendsSort, html = '', i, len;

			// Sort the array
			friendsSort = function(a, b) {
				if (a.name === b.name) {
					return 0;
				} else if (a.name > b.name) {
					return 1;
				} else {
					return -1;
				}
			};
			friends.sort(friendsSort);

			if ($friends1) {
				$friends1.unbind("click");
				$friends2.unbind("click");
			}

			for (i = 0, len = friends.length; i < len; i += 1) {
				html += '<li>' + friends[i].name;
				if (friends[i].facebookId) {
					html += '<div class="ir iconFacebook">&nbsp;</div>';
				}
				if (friends[i].linkedInId) {
					html += '<div class="ir iconLinkedIn">&nbsp;</div>';
				}
				if (friends[i].twitterId) {
					html += '<div class="ir iconTwitter">&nbsp;</div>';
				}
				html += '</li>';
			}
			$filteredFriends.html(html);
			$friends1 = $($filteredFriends[0]).children();
			$friends2 = $($filteredFriends[1]).children();
			$friends1.click(function (e) {
				e.preventDefault();
				introducee1 = friends[$(this).index()];
				$("#introducee1").val(introducee1.name);
				$friends1.parent().hide();
			});
			$friends2.click(function (e) {
				e.preventDefault();
				introducee2 = friends[$(this).index()];
				$("#introducee2").val(introducee2.name);
				$friends2.parent().hide();
			});
			friendsAddedToDom = true;
		};

		$("#formIntroduce").submit(function(e) {
			var validationError = false, inputData;
			e.preventDefault();
			// Validate form
			if (!introducee1) {
				$("#introducee1").css({background: "#FFFFB1"});
				validationError = true;
			}
			if (!introducee2) {
				$("#introducee2").css({background: "#FFFFB1"});
				validationError = true;
			}
			if (validationError) {
				return false;
			}
			// Disable the submit button
			$("#submitIntroduce").attr("disabled", "disabled");
			// Analytics
			_gaq.push(["_trackPageview", "/click-send-introduction"]);
			// Send the data
			inputData = {
				introducee1Name: $("#introducee1").val(),
				introducee1FacebookId: introducee1.facebookId,
				introducee1LinkedInId: introducee1.linkedInId,
				introducee1TwitterId: introducee1.twitterId,
				introducee2Name: $("#introducee2").val(),
				introducee2FacebookId: introducee2.facebookId,
				introducee2LinkedInId: introducee2.linkedInId,
				introducee2TwitterId: introducee2.twitterId,
				message: ($("#message").val() !== $("#message").attr("placeholder")) ? $("#message").val() : ""
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
					if (data.result === "false") {
						if (data.message) {
							showDialog(data.title, data.message);
						}
					} else {
						// The introduction has been saved to the database
						_gaq.push(['_trackPageview', '/introduction-saved']);
						// Redirect the user to send-introduction where notifications will sent
						window.location = data.link;
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					showDialog('Error loading AJAX', 'Please try again later.', null);
					$("#submitIntroduce").removeAttr('disabled');
				}
			});
		});

		if (module.personId) {
			if (module.facebookId) {
				loadFriends('Facebook');
			}
			if (module.linkedInId) {
				loadFriends('LinkedIn');
			}
			if (module.twitterId) {
				loadFriends('Twitter');
			}
		} else {
			$(":text, textarea").focus(function() {
				$("#loginFirst").css({"display": "block", "padding-left": "40px"});
				$('#loginFirst').animate({
					paddingLeft: "10px"
				});
			});
		}

		$("#loginFacebook").click(function() {
			_gaq.push(["_trackPageview", "/index/click-login/facebook"]);
		});
		$("#loginLinkedIn").click(function() {
			_gaq.push(["_trackPageview", "/index/click-login/linkedin"]);
		});
		$("#loginTwitter").click(function() {
			_gaq.push(["_trackPageview", "/index/click-login/twitter"]);
		});
		$("a.help").click(function() {
			_gaq.push(["_trackPageview", "/index/click-learn-more"]);
		});
	};

	// Show a dialog window
	showDialog = function (title, body, callback) {
		var result = confirm(title + "\n" + body);
		if (result === true) {
			if (typeof callback === "function") {
				callback();
			}
		}
	};

	setupSettingsPage = function () {
		if (!$body.hasClass("settingsPage")) {
			return false;
		}
		$("#formEmail").submit(function(e) {
			e.preventDefault();
			$("#submitEmail").attr("disabled", "disabled");
			$.ajax({
				type: "POST",
				url: "/en/ajax/save-email/",
				data: $("#formEmail").serialize(),
				dataType: "json",
				cache: false,
				success: function(data) {
					// Response handler
					$("#submitEmail").removeAttr('disabled');
					if (data.result === "false") {
						$("#email").addClass("invalid");
					} else {
						$("#email").removeClass("invalid");
						module.showDialog(introduceme.content.success, introduceme.content.saved);
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					module.showDialog("Error loading AJAX", "Please try again later.");
					$("#submitEmail").removeAttr("disabled");
				}
			});
		});
	};

	setupViewIntroductionPage = function () {
		if (!$body.hasClass("viewIntroductionPage")) {
			return false;
		}
		$("#formEmail").submit(function(e) {
			e.preventDefault();
			$("#submitEmail").attr("disabled", "disabled");
			$.ajax({
				type: "POST",
				url: "/en/ajax/save-email/",
				data: $("#formEmail").serialize(),
				dataType: "json",
				cache: false,
				success: function(data) {
					// Response handler
					_gaq.push(["_trackPageview", "/view-introduction/save-email/" + module.userType]);
					$("#submitEmail").removeAttr('disabled');
					if (data.result !== "true") {
						$("#email").addClass("invalid");
					} else {
						$("#formEmail").hide();
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					module.showDialog("Error loading AJAX", "Please try again later.");
					$("#submitEmail").removeAttr("disabled");
				}
			});
		});

		$("#formMessage").submit(function(e) {
			e.preventDefault();
			$("#submitMessage").attr('disabled', 'disabled');
			$.ajax({
				type: "POST",
				url: "/en/ajax/send-message/",
				data: $("#formMessage").serialize(),
				dataType: "json",
				cache: false,
				success: function(data) {
					// Response handler
					_gaq.push(["_trackPageview", "/view-introduction/send-message/" + module.userType]);
					$("#submitMessage").removeAttr('disabled');
					if (data.result !== "true") {
						$("#message").addClass("invalid");
					} else {
						$(".displayingMessages").show();
						$("#message").removeClass("invalid").val("");
						$("#messages").prepend('<div class="message"><h2>' + module.content.youWrote + '</h2>' + 
							data.time + '<p>' + data.message + '</p></div>');
						$("#messages div:first-child time").timeago();
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					module.showDialog("Error sending message", "Please try again later.");
					$("#submitMessage").removeAttr('disabled');
				}
			});
		});

		$("#btnFaqs a").click(function(e) {
			e.preventDefault();
			_gaq.push(["_trackPageview", "/view-introduction/learn-more"]);
			$("#btnFaqs").hide();
			$("#faqs").slideDown();
		});
		$("#loginFacebook").click(function() {
			_gaq.push(["_trackPageview", "/view-introduction/click-login/facebook"]);
			return true;
		});
		$("#loginLinkedIn").click(function() {
			_gaq.push(["_trackPageview", "/view-introduction/click-login/linkedin"]);
			return true;
		});
		$("#loginTwitter").click(function() {
			_gaq.push(["_trackPageview", "/view-introduction/click-login/twitter"]);
			return true;
		});
	};

	module.showDialog = showDialog;
	return module;

}(introduceme || {}));

