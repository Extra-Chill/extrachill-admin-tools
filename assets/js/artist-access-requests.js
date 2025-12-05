/**
 * Artist Access Requests Tool
 * Handles AJAX for approving and rejecting artist access requests
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		initApproveButtons();
		initRejectButtons();
	});

	/**
	 * Approve button handler
	 */
	function initApproveButtons() {
		$('.ec-approve-request').on('click', function() {
			var $button = $(this);
			var userId = $button.data('user-id');
			var type = $button.data('type');
			var $row = $button.closest('tr');

			if (!confirm('Approve this artist access request?')) {
				return;
			}

			$button.prop('disabled', true).text('Approving...');

			$.ajax({
				url: ecAdminTools.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ec_approve_artist_access',
					user_id: userId,
					type: type,
					nonce: ecAdminTools.nonces.artistAccessRequests
				},
				success: function(response) {
					if (response.success) {
						$row.fadeOut(300, function() {
							$(this).remove();
							checkEmptyState();
						});
					} else {
						alert('Error: ' + response.data);
						$button.prop('disabled', false).text('Approve');
					}
				},
				error: function() {
					alert('AJAX error occurred');
					$button.prop('disabled', false).text('Approve');
				}
			});
		});
	}

	/**
	 * Reject button handler
	 */
	function initRejectButtons() {
		$('.ec-reject-request').on('click', function() {
			var $button = $(this);
			var userId = $button.data('user-id');
			var $row = $button.closest('tr');

			if (!confirm('Reject this request? The user will not be notified.')) {
				return;
			}

			$button.prop('disabled', true).text('Rejecting...');

			$.ajax({
				url: ecAdminTools.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ec_reject_artist_access',
					user_id: userId,
					nonce: ecAdminTools.nonces.artistAccessRequests
				},
				success: function(response) {
					if (response.success) {
						$row.fadeOut(300, function() {
							$(this).remove();
							checkEmptyState();
						});
					} else {
						alert('Error: ' + response.data);
						$button.prop('disabled', false).text('Reject');
					}
				},
				error: function() {
					alert('AJAX error occurred');
					$button.prop('disabled', false).text('Reject');
				}
			});
		});
	}

	/**
	 * Check if table is empty and show empty state
	 */
	function checkEmptyState() {
		var $table = $('.ec-artist-access-wrap table');
		if ($table.find('tbody tr').length === 0) {
			$table.replaceWith('<div class="ec-empty-state"><p>No pending artist access requests.</p></div>');
		}
	}

})(jQuery);
