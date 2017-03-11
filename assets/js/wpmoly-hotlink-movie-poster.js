
document.addEventListener("DOMContentLoaded", function(event) { 

	var wpmoly_posters_hotlink = {

		init: function() {},
		frame: function() {},
		select: function() {},
		set_featured: function() {},
		set_featured_original: function() {},
		close: function() {}
	}

	/**
	 * Init WPMOLY Media Posters
	 */
	wpmoly_posters_hotlink.init = function() {
		wpmoly_posters_hotlink.frame().open();
	};

	/**
	 * Media Posters Modal. Extends WP Media Modal to show
	 * movie posters from external API instead of regular WP
	 * Attachments.
	 */
	wpmoly_posters_hotlink.frame = function() {

		if ( this._frame )
			return this._frame;

		this._frame = wp.media({
			title: wpmoly_lang.import_poster_title.replace( '%s', wpmoly.editor._movie_title ),
			frame: 'select',
			searchable: false,
			library: {
				// Dummy: avoid any image to be loaded
				type : 'image',
				post__in:[ wpmoly.editor._movie_id ],
				post__not_in:[0],
				s: 'TMDb_ID=' + wpmoly.editor._movie_tmdb_id + ',type=poster'
			},
			multiple: false,
			button: {
				text: wpmoly_lang.import_poster
			}
		});

		this._frame.options.button.event = 'import_poster';
		this._frame.options.button.reset = false;
		this._frame.options.button.close = false;

		this._frame.state('library').unbind('select').on('import_poster', this.select);
		this._frame.on( 'open', this.ready );
		this._frame.state('library').get('selection').on( 'selection:single', function() {
			$( wpmoly_posters_hotlink._frame.content.selector ).find( '.attachments-browser' ).removeClass( 'hide-sidebar' );
		} );

		return this._frame;
	};

	/**
	 * Set the modal to browse mode
	 */
	wpmoly_posters_hotlink.ready = function() {

		wpmoly_posters_hotlink._frame.content.mode( 'browse' );
		$( wpmoly_posters_hotlink._frame.content.selector ).find( '.attachments-browser' ).addClass( 'hide-sidebar' );
	};

	/**
	 * Select override for Modal
	 * 
	 * Handle selected poster and custom progress bar
	 */
	wpmoly_posters_hotlink.select = function() {
		
		var $content = $(wpmoly_posters_hotlink._frame.content.selector);

		if ( ! $('#progressbar_bg').length )
			$content.append('<div id="progressbar_bg"><div id="progressbar"><div id="progress"></div></div><div id="progress_status">' + wpmoly_lang.import_poster_wait + '</div>');

		$('#progressbar_bg, #progressbar').show();
		$('#progressbar #progress').width('40%');

		var settings = wp.media.view.settings,
			selection = this.get('selection'),
			total = selection.length;

		$('.added').remove();

		wpmoly_posters_hotlink.total = total;
		//We are going to override the normal "set_featured" so to diferenciate from the normal, we need to send another arg
		selection.map(   function(x) { return wpmoly_posters_hotlink.set_featured(x, true); }  );
		wpmoly_posters_hotlink._frame.state('library').get('selection').reset();

		return;
	};

	/**
	 * Set Poster as featured image.
	 * 
	 * Upload the selected image and set it as the post's
	 * featured image.
	 */
	wpmoly_posters_hotlink.set_featured = function( image, hotlink) {
		
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
		$('#progressbar_bg, #progressbar').remove();
		if ( undefined != wpmoly_posters_hotlink._frame )
			wpmoly_posters_hotlink._frame.close();
	};
	
	/* Adds event listener for click on action link to open poster modal */
	$('#postimagediv').on( 'click', '#tmdb_load_posters_hotlink', function( e ) {
		e.preventDefault();

		if ( undefined == wpmoly.editor._movie_tmdb_id || '' == wpmoly.editor._movie_tmdb_id ) {
			wpmoly.media.no_movie();
			return false;
		}
		
		wpmoly_posters_hotlink.init();
		wpmoly_posters_hotlink._frame.$el.addClass('movie-posters');
		if ( undefined != wpmoly_posters_hotlink._frame.content.get('library').collection )
			wpmoly_posters_hotlink._frame.content.get('library').collection.props.set({ignore: (+ new Date())});
	});
	
	//Override set_featured poster with this plugin 
	wpmoly_posters_hotlink.set_featured_original = wpmoly_posters.set_featured;
	wpmoly_posters.set_featured = wpmoly_posters_hotlink.set_featured;	
});