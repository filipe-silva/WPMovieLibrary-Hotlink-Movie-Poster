=== WPMovieLibrary Hotlink Movie Poster ===

== Description ==

Add the ability to Hotlink TMBD Movie Poster in a movie

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Go to the 'Plugins' menu in WordPress
3. Make sure WPMovieLibrary is activated
4. Activate this plugin
5. Use it!

== Notes ==
I've developed this extension based on other existing extensions like "trailers".
This extension only works while editing a single movie, on import movies it will still download the images.
However while editing the movie if this extension is active, when automatically importing images it will always hotlink. If you want to import the image for some reason it is still possible to manually do it.

For the "import movies" part there is a possiblity to override it using runkit_function_redefine, but since it isn't something that comes it php by default I decided not to implement it.

Concerned about tmdb position on hotlinking? Don't be, they allow it.
https://www.themoviedb.org/talk/556fbb0a92514140ca0005af
https://www.themoviedb.org/talk/57389ef592514111a800003d
