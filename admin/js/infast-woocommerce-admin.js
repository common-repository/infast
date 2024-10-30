jQuery(document).ready(function() {

	jQuery('.infast-test-btn').on('click', function() {

		var clientId = jQuery('#infast-client-id').val();
		var clientSecret = jQuery('#infast-client-secret').val();
		
		if(!clientId || !clientSecret) {
			alert('Veuillez renseigner les champs « Client ID » et « Client Secret ».');
			return;
		}

		jQuery.ajax ({
			url: ajaxurl,  
			type:'GET',
			data:'action=infast_test_authentication',

			beforeSend:function() {
				jQuery('.infast-test-btn').append('<span> [...]</span>');
				jQuery(this).prop('disabled', true);
			},
			success:function(results) {
				jQuery('.infast-test-btn span').remove();
				jQuery(this).prop('disabled', false);
				if(!results.success || !results.data || results.data == 'undefined') {
					alert('Le test à échoué, veuillez vérifier les champs « Client ID » et « Client Secret ».');
					return;
				}
				alert('La connexion à INFast fonctionne correctement. Vous êtes connecté avec le compte ' + results.data);
			},
			error:function(request, status, error) {
				console.log(error);
				jQuery('.infast-test-btn span').remove();
				jQuery(this).prop('disabled', false);
				alert('Le test à échoué, veuillez vérifier les champs « Client ID » et « Client Secret ».');	
			}
		});

	});

	jQuery('.infast-syncall-btn').on('click', function() {

		jQuery.ajax ({
			url: ajaxurl,  
			type:'POST',
			data:'action=infast_synchronise_all',

			beforeSend:function() {
				jQuery('.infast-syncall-btn').append('<span> [...]</span>');
				jQuery(this).prop('disabled', true);
			},
			success:function(results) {
				jQuery('.infast-syncall-btn span').remove();
				jQuery(this).prop('disabled', false);
			},
			error:function(request, status, error) {
				console.log(error);
				jQuery('.infast-syncall-btn span').remove();
				jQuery(this).prop('disabled', false);
			}
		});

	});

	jQuery('.infast-unlink-items-btn').on('click', function() {
		if( !confirm('Etes vous sur de vouloir délier les articles WooCommerce des articles INFast ?\n Lors de la prochaines synchronisation avec INFast tous les articles seront recréés dans INFast.') ) {
			return;
		}

		jQuery.ajax ({
			url: ajaxurl,  
			type:'POST',
			data:'action=infast_unlink_items',

			beforeSend:function() {
				jQuery('.infast-unlink-items-btn').append('<span> [...]</span>');
				jQuery(this).prop('disabled', true);
			},
			success:function(results) {
				jQuery('.infast-unlink-items-btn span').remove();
				jQuery(this).prop('disabled', false);
			},
			error:function(request, status, error) {
				console.log(error);
				jQuery('.infast-unlink-items-btn span').remove();
				jQuery(this).prop('disabled', false);
			}
		});

	});
	

});