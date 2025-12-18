/**
 * Artist Access Requests Tool
 * Handles approval and rejection of artist access requests via REST API
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initApproveButtons();
		initRejectButtons();
	});

	/**
	 * Approve button handler
	 */
	function initApproveButtons() {
		document.querySelectorAll('.ec-approve-request').forEach(function(button) {
			button.addEventListener('click', function() {
				var userId = this.dataset.userId;
				var type = this.dataset.type;
				var row = this.closest('tr');
				var btn = this;

				if (!confirm('Approve this artist access request?')) {
					return;
				}

				btn.disabled = true;
				btn.textContent = 'Approving...';

				fetch(ecAdminTools.restUrl + 'admin/artist-access/' + userId + '/approve', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': ecAdminTools.nonce
					},
					body: JSON.stringify({ type: type })
				})
				.then(function(response) {
					return response.json().then(function(data) {
						return { ok: response.ok, data: data };
					});
				})
				.then(function(result) {
					if (result.ok && result.data.success) {
						row.style.transition = 'opacity 0.3s';
						row.style.opacity = '0';
						setTimeout(function() {
							row.remove();
							checkEmptyState();
						}, 300);
					} else {
						alert('Error: ' + (result.data.message || 'Unknown error'));
						btn.disabled = false;
						btn.textContent = 'Approve';
					}
				})
				.catch(function() {
					alert('Request failed');
					btn.disabled = false;
					btn.textContent = 'Approve';
				});
			});
		});
	}

	/**
	 * Reject button handler
	 */
	function initRejectButtons() {
		document.querySelectorAll('.ec-reject-request').forEach(function(button) {
			button.addEventListener('click', function() {
				var userId = this.dataset.userId;
				var row = this.closest('tr');
				var btn = this;

				if (!confirm('Reject this request? The user will not be notified.')) {
					return;
				}

				btn.disabled = true;
				btn.textContent = 'Rejecting...';

				fetch(ecAdminTools.restUrl + 'admin/artist-access/' + userId + '/reject', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': ecAdminTools.nonce
					}
				})
				.then(function(response) {
					return response.json().then(function(data) {
						return { ok: response.ok, data: data };
					});
				})
				.then(function(result) {
					if (result.ok && result.data.success) {
						row.style.transition = 'opacity 0.3s';
						row.style.opacity = '0';
						setTimeout(function() {
							row.remove();
							checkEmptyState();
						}, 300);
					} else {
						alert('Error: ' + (result.data.message || 'Unknown error'));
						btn.disabled = false;
						btn.textContent = 'Reject';
					}
				})
				.catch(function() {
					alert('Request failed');
					btn.disabled = false;
					btn.textContent = 'Reject';
				});
			});
		});
	}

	/**
	 * Check if table is empty and show empty state
	 */
	function checkEmptyState() {
		var table = document.querySelector('.ec-artist-access-wrap table');
		if (table && table.querySelectorAll('tbody tr').length === 0) {
			var emptyState = document.createElement('div');
			emptyState.className = 'ec-empty-state';
			emptyState.innerHTML = '<p>No pending artist access requests.</p>';
			table.replaceWith(emptyState);
		}
	}

})();
