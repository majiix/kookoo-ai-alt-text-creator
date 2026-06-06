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

	// Handle Reset Cron Progress in Settings Page
	$('.aialtg-reset-btn').on('click', function(e) {
		e.preventDefault();

		if ( ! confirm( aialtg_vars.reset_confirm ) ) {
			return;
		}

		var btn = $(this);
		var nonce = btn.data('nonce');
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
				action: 'aialtg_reset_progress',
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

	// Handle Scan & Fix JSON Errors in Settings Page
	$('.aialtg-fix-json-btn').on('click', function(e) {
		e.preventDefault();

		var btn = $(this);
		var nonce = btn.data('nonce');
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
				action: 'aialtg_fix_json_errors',
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
					}, 2000);
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

	// Handle Retry Failed Images
	$('.aialtg-retry-failed-btn').on('click', function(e) {
		e.preventDefault();

		var btn = $(this);
		var nonce = btn.data('nonce');
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
				action: 'aialtg_retry_failed',
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
					}, 2000);
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

						// Check for vision/image support
						var supportsImage = false;
						if (model.architecture && model.architecture.input_modalities) {
							if (model.architecture.input_modalities.indexOf('image') !== -1) {
								supportsImage = true;
							}
						}

						if (supportsImage) {
							name += ' 📷';
						}

						if (!grouped[provider]) {
							grouped[provider] = [];
						}

						grouped[provider].push({
							id: id,
							name: name,
							supportsImage: supportsImage
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

});