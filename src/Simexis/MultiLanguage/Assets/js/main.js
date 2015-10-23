jQuery(document).ready(function($){

	$.ajaxSetup({
		beforeSend: function(xhr, settings) {
			settings.data += $('meta[name="csrf-token"]').attr('content');
		}
	});

	$('.editable').editable({
		success: function(response, newValue) {
			if(response.status == 'save') {
				if(response.locked) {
					$(this).removeClass('status-0').addClass('status-1');
				} else {
					$(this).removeClass('status-1').addClass('status-0');
				}
				var locale = $(this).data('locale');
				var $next = $(this).closest('tr').next().find('.editable.locale-'+locale);
				setTimeout(function() {
					$next.editable('show');
				}, 300);
			} else if(response.status == 'error') {
				alert(response.message);
			} else if(response.status == 'delete') {
				$(this).removeClass('status-1').addClass('status-0');
				var locale = $(this).data('locale');
				var $next = $(this).closest('tr').next().find('.editable.locale-'+locale);
				setTimeout(function() {
					$next.editable('show');
				}, 300);
			}
		}
	});

	$('.group-select').on('change', function(){
		window.location.href = $(this).val();
	});

	$('.form-global-action').on('ajax:success', function (e, data) {
		$('div.success-' + data.action + ' strong.counter').text(data.counter);
		$('div.success-' + data.action + '').slideDown();
	});
	
	$('[data-toggle="tooltip"]').tooltip();
	
});