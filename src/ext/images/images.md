# The images subserver

	load_ext('images');

This subserver generates previews from original full-sized image files
stored on disk. This task is typical for shops, blogs and other sites
dealing with image files of different sizes uploaded by users.

The original image is served by the web server itself. But if the same
image path is "decorated" with a size marker, the subserver serves the
resized version of that image. The decorated URLs are created by the
`image_url` function.

	$url = image_url($imgpath, $width=null, $height=null);

The `image_url` function creates the HTML-escaped URL for the copy of
the image found at `imgpath` with width limited to `width` pixels and
height limited to `height` pixels.

Both `width` and `height` parameters are optional. If none of the
limits is given, the original URL is returned. For example, to show a
preview that would fit a 200 pixels height container:

	```php
	<?php
	$orig_path = "uploads/img217.jpg";
	?>
	<div class="img-container">
		<img src="<?= image_url($orig_path, null, 200) ?>" alt="">
	</div>
	```

The created copy is never enlarged. If the original size is less than
the given limits, the original URL will be returned.

There is a hard size limit for the images that can be processed that is
set to 2 megabytes. If the original image exceeds that size, then all
requests to the resized copies will return the "Not Found" error.

The subserver supports caching using the `ETag` and related HTTP
headers.

The generated previews are cached in the directory which by default is
at `<write-dir>/image-previews`. The `imgcache` configuration parameter
may be used to override the path. Regardless of the path the script
must have write permissions for that directory. If the directory
doesn't exist, the script must be able to create it.
