
var reload = true; // do not change this

$(document).ready( function( ) {
	// disable the selected players in the other select boxes
	$('select[id^="opponent"]').change( function( ) {
		var $selects = $('select[id^="opponent"]');

		// make all options available
		// then we'll disable them below
		$selects
			.find('option')
				.attr('disabled', false);

		// disable the selected options
		// in each of the other boxes
		$selects.each( function( ) {
			var $this = $(this);
			var val = $this.val( );

			// only if the option wasn't the '-- OPEN --' or '-- CLOSED --' option
			if (('' !== val) && ('0' !== val)) {
				// don't disable the option in the box we selected the option in
				// because that might get weird
				$selects.not($this)
					.find('option[value="'+val+'"]')
						.attr('selected', false)
						.attr('disabled', true);
			}
		});
	});

	// this runs all the ...vites
	$('div#invites input').click( function( ) {
		var id = $(this).attr('id').split('-');

		if ('accept' == id[0]) { // invites and openvites
			// accept the invite
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+'invite=accept&game_id='+id[1];
				return;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: 'invite=accept&game_id='+id[1],
				success: function(msg) {
					window.location = 'game.php?id='+msg+debug_query_;
					return;
				}
			});
		}
		else if ('resend' == id[0]) { // resends outvites
			// resend the invite
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+'invite=resend&game_id='+id[1];
				return;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: 'invite=resend&game_id='+id[1],
				success: function(msg) {
					alert(msg);
					if (reload) { window.location.reload( ); }
					return;
				}
			});
		}
		else { // invites decline and outvites withdraw
			// delete the invite
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+'invite=delete&game_id='+id[1];
				return;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: 'invite=delete&game_id='+id[1],
				success: function(msg) {
					alert(msg);
					if (reload) { window.location.reload( ); }
					return;
				}
			});
		}
	});
});

function show_link( ) {
	if (0 != $('select#setup').val( )) {
		$('a#show_setup').show( );
	}
	else {
		$('a#show_setup').hide( );
	}
}

