jQuery(document).ready(function($) {

	// Handle Single Image Generation in Media Library
	$('.aialtg-generate-btn').on('click', function(e) {
		e.preventDefault();

		var btn = $(this);
		var id = btn.data('id');
		var nonce = btn.data('nonce');
		var spinner = btn.siblings('.aialtg-spinner');
		var msgBox = btn.siblings('.aialtg-message');

		// UI Updates: Disable button and show spinner
		btn.prop('disabled', true);
		spinner.addClass('is-active');
		msgBox.removeClass('success error').addClass('processing').text(aialtg_vars.processing);

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'aialtg_generate_meta',
				post_id: id,
				nonce: nonce
			},
			success: function(response) {
				spinner.removeClass('is-active');
				btn.prop('disabled', false);

				if (response.success) {
					msgBox.removeClass('processing error').addClass('success').text(response.data.message);
					btn.text(aialtg_vars.regenerate);
				} else {
					msgBox.removeClass('processing success').addClass('error').text(response.data.message);
				}
			},
			error: function() {
				spinner.removeClass('is-active');
				btn.prop('disabled', false);
				msgBox.removeClass('processing success').addClass('error').text(aialtg_vars.network_error);
			}
		});
	});

	// Handle Admin Actions (Reset, Retry, Fix JSON) in Settings Page
	$('.aialtg-admin-action-btn').on('click', function(e) {
		e.preventDefault();

		var btn = $(this);
		var action = btn.data('action');
		var nonce = btn.data('nonce');
		var confirmReq = btn.data('confirm');

		if (confirmReq && !confirm(aialtg_vars.reset_confirm)) {
			return;
		}

		var wrapper = btn.closest('.aialtg-controls-wrapper');
		var statusArea = wrapper.find('.aialtg-status-area');
		var spinner = statusArea.find('.aialtg-status-spinner');
		var msgBox = statusArea.find('.aialtg-status-message');

		wrapper.find('.aialtg-buttons-row button').prop('disabled', true);
		statusArea.removeClass('success error').addClass('processing').show();
		spinner.addClass('is-active');
		msgBox.text(aialtg_vars.processing);

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: action,
				nonce: nonce
			},
			success: function(response) {
				spinner.removeClass('is-active');
				wrapper.find('.aialtg-buttons-row button').prop('disabled', false);

				if (response.success) {
					statusArea.removeClass('processing error').addClass('success');
					msgBox.text(response.data.message);
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					statusArea.removeClass('processing success').addClass('error');
					msgBox.text(response.data.message);
				}
			},
			error: function() {
				spinner.removeClass('is-active');
				wrapper.find('.aialtg-buttons-row button').prop('disabled', false);
				statusArea.removeClass('processing success').addClass('error');
				msgBox.text(aialtg_vars.network_error);
			}
		});
	});

	// Handle Dynamic Model Fetching on Settings Page
	var modelContainer = $('.aialtg-model-field-container');
	if (modelContainer.length > 0) {
		var currentValue = modelContainer.data('current-value');
		var modelNonce = modelContainer.data('nonce');
		var selectWrap = modelContainer.find('.aialtg-model-select-wrap');
		var skeleton = modelContainer.find('.aialtg-skeleton-loader');
		var modelSelect = $('#aialtg-model-select');
		var customInputWrap = $('.aialtg-custom-model-input-wrap');
		var customInput = $('#aialtg-custom-model-input');
		var realInput = $('#aialtg-model-real-input');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'aialtg_get_models',
				nonce: modelNonce
			},
			success: function(response) {
				if (response.success && response.data.models) {
					var models = response.data.models;
					modelSelect.empty();

					// Group models by provider
					var grouped = {};
					var foundCurrent = false;

					models.forEach(function(model) {
						var id = model.id;
						var name = model.name;
						
						// Parse provider from ID
						var provider = 'Other';
						if (id.indexOf('/') !== -1) {
							provider = id.split('/')[0];
							provider = provider.charAt(0).toUpperCase() + provider.slice(1);
						}

						if (!grouped[provider]) {
							grouped[provider] = [];
						}

						grouped[provider].push({
							id: id,
							name: name
						});

						if (id === currentValue) {
							foundCurrent = true;
						}
					});

					// Sort and group by provider
					var providers = Object.keys(grouped).sort(function(a, b) {
						var priority = { 'Google': 1, 'Openai': 2, 'Anthropic': 3 };
						var pA = priority[a] || 99;
						var pB = priority[b] || 99;
						if (pA !== pB) return pA - pB;
						return a.localeCompare(b);
					});

					providers.forEach(function(provider) {
						var optgroup = $('<optgroup>').attr('label', provider);
						grouped[provider].sort(function(a, b) {
							return a.name.localeCompare(b.name);
						});
						grouped[provider].forEach(function(item) {
							optgroup.append($('<option>').val(item.id).text(item.name));
						});
						modelSelect.append(optgroup);
					});

					// Add custom model option
					modelSelect.append($('<option>').val('custom').text('-- Enter Custom Model ID --'));

					// Set initial state
					if (foundCurrent) {
						modelSelect.val(currentValue);
						customInputWrap.hide();
					} else {
						modelSelect.val('custom');
						customInput.val(currentValue);
						customInputWrap.show();
					}

					skeleton.hide();
					selectWrap.show();
				} else {
					showFallbackInput();
				}
			},
			error: function() {
				showFallbackInput();
			}
		});

		function showFallbackInput() {
			skeleton.hide();
			selectWrap.show();
			modelSelect.hide();
			customInput.val(currentValue);
			customInputWrap.show();
			customInput.on('input', function() {
				realInput.val($(this).val());
			});
		}

		// Handle select changes
		modelSelect.on('change', function() {
			var selectedVal = $(this).val();
			if (selectedVal === 'custom') {
				customInputWrap.show();
				realInput.val(customInput.val());
			} else {
				customInputWrap.hide();
				realInput.val(selectedVal);
			}
		});

		// Handle custom input changes
		customInput.on('input', function() {
			if (modelSelect.val() === 'custom' || modelSelect.is(':hidden')) {
				realInput.val($(this).val());
			}
		});
	}

	// API Key Password Visibility Toggle
	$('.aialtg-toggle-password').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		var input = btn.siblings('input');
		var icon = btn.find('.dashicons');
		
		if (input.attr('type') === 'password') {
			input.attr('type', 'text');
			icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
		} else {
			input.attr('type', 'password');
			icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
		}
	});

	// Auto-grow textareas
	$('textarea.large-text').each(function() {
		this.style.height = 'auto';
		this.style.height = (this.scrollHeight + 4) + 'px';
	}).on('input', function() {
		this.style.height = 'auto';
		this.style.height = (this.scrollHeight + 4) + 'px';
	});



	// Tab switcher
	$('.aialtg-tab-btn').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		var tabId = btn.data('tab');

		$('.aialtg-tab-btn').removeClass('active');
		btn.addClass('active');

		$('.aialtg-tab-panel').removeClass('active');
		$('#aialtg-tab-' + tabId).addClass('active');

		if (tabId === 'help') {
			$('.aialtg-submit-wrap').hide();
		} else {
			$('.aialtg-submit-wrap').show();
		}
		
		localStorage.setItem('aialtg_active_tab', tabId);
	});

	// Restore active tab on load
	var activeTab = localStorage.getItem('aialtg_active_tab');
	if (activeTab && $('.aialtg-tab-btn[data-tab="' + activeTab + '"]').length > 0) {
		$('.aialtg-tab-btn[data-tab="' + activeTab + '"]').trigger('click');
	}

});