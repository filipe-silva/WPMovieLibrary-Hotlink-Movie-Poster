
var hotlink = false;

document.addEventListener("DOMContentLoaded", function(event) { 

	var wpmoly_posters_hotlink = {
		set_featured: function() {},
		set_featured_original: function() {},
		close: function() {},
		close_original: function() {},
		edit_meta_get: function () {}
	}
//wpmoly_edit_meta.get

	/**
	 * Set Poster as featured image.
	 * 
	 * Upload the selected image and set it as the post's
	 * featured image.
	 */
	wpmoly_posters_hotlink.set_featured = function( image ) {
		
		// console.log("starting hotlink set featured, with bool as: " + hotlink);
		// console.log("arg image is: ");
		// console.log(image);
		
		if ( undefined != image.attributes && undefined != image.attributes.metadata ) {
			if(hotlink){
				var _image = {file_path: image.attributes.metadata.file_path};
			} else{
				//So we know it's an image from the selection modal and not a string path
				//We also know it's not from hotlink plugin, it's from the core
				//Therefore we assume it's not from an automatic placement (I believe they all rely on a path string, not an image object)
				//We can resume the original set_featured, since we assume it was done manually from a movie edit
				wpmoly_posters_hotlink.set_featured_original(image);
				return;
			}
		}
		else{			
			//So we know we've received a string path, and is not from hotlink plugin, that means it's from the core, so we assume it's some automatic stuff
			//Since it's automatic we hotlink
			if ( 0 <= parseInt( wp.media.featuredImage.get() ) ) {
				$('#progressbar #progress').width('100%');
				$('#progress_status').text( wpmoly_lang.done );
				window.setTimeout( wpmoly_posters_hotlink.close(), 2000 );
				return false;
			}

			var _image = {file_path: image};
		}
		
		// console.log("_image var with value: ");
		// console.log(_image);

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'text',
			data: {
				action: 'wpmoly_set_featured_hotlink',
				nonce: wpmoly.get_nonce( 'set-movie-poster-hotlink' ),
				image: _image,
				title: wpmoly.editor._movie_title,
				post_id: wpmoly.editor._movie_id,
				tmdb_id: wpmoly.editor._movie_tmdb_id
			},
			error: function( response ) {
				// console.log("error:");
				// console.log(response);
				wpmoly_state.clear();
				$.each( response.responseJSON.errors, function() {
					wpmoly_state.set( this, 'error' );
				});
			},
			success: function( response ) {
				// console.log("post successful, response is: ");
				// console.log(response);
				if ( response ) {
					var start_pos = response.indexOf('"data":') + 7;
					var end_pos = response.indexOf(',"message":',start_pos);
					var text_to_get = response.substring(start_pos,end_pos)
					// console.log(text_to_get);
					wp.media.featuredImage.set( text_to_get );
					if(image.attributes){
						$( '#wpmoly-movie-preview-poster > img' ).prop( 'src', image.attributes.url );
					}
				}
			},
			complete: function() {
				$('#progress_status').text( wpmoly_lang.done );
				window.setTimeout( wpmoly_posters_hotlink.close(), 2000 );
			}
		});
	};

	/**
	 * Close the Modal
	 */
	wpmoly_posters_hotlink.close = function() {
		hotlink = false;
		wpmoly_posters_hotlink.close_original();
	};
	
	/* Adds event listener for click on action link to open poster modal */
	$('#postimagediv').on( 'click', '#tmdb_load_posters_hotlink', function( e ) {
		hotlink = true;
		$('#tmdb_load_posters').trigger("click");
	});
	
	/**
	* This is where we override javascript functions so that when automatic stuff like getting metadata that gets a poster becames hotlink.
	**/
	wpmoly_posters_hotlink.set_featured_original = wpmoly_posters.set_featured;
	wpmoly_posters.set_featured = wpmoly_posters_hotlink.set_featured;
	
	wpmoly_posters_hotlink.close_original = wpmoly_posters.close;
	wpmoly_posters.close = wpmoly_posters_hotlink.close;
	
});