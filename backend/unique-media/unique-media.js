(function ($) {
	function showHeaderNotice(ms = 5000) {
		var $notice = $(
			'<div class="notice notice-error is-dismissible contiki-dup-notice">' +
			'<p><strong>Notice:</strong> This media already exists in the WordPress Media Library.</p>' +
			'<button type="button" class="notice-dismiss">' +
			'<span class="screen-reader-text">Dismiss this notice.</span>' +
			'</button>' +
			'</div>'
		);

		var $anchor = $('.wp-header-end');
		if ($anchor.length) {
			$anchor.after($notice);
		} else {
			$('#wpbody-content').prepend($notice);
		}

		$notice.on('click', '.notice-dismiss', function () {
			$notice.remove();
		});

		// auto hide
		if (ms) {
			setTimeout(function () {
				$notice.fadeOut(150, function () { $(this).remove(); });
			}, ms);
		}
	}

	$(document).ready(function () {
		var origSuccess = wp.Uploader.prototype.success;

		$.extend(wp.Uploader.prototype, {
			success: function (mediaModel) {
				if (mediaModel.get('duplicate_upload')) {
					showHeaderNotice(5000);

					var prevSync = wp.media.model.Attachment.prototype.sync;
					setTimeout(function () {
						var id = mediaModel.get('id');
						wp.media.model.Attachment.prototype.sync = function () { };
						mediaModel.destroy();
						wp.media.model.Attachment.prototype.sync = prevSync;

						if (wp.media.frame) {
							wp.media.frame.state().get('selection').add(wp.media.attachment(id));
						}
					}, 50);
				}

				return origSuccess.apply(this, arguments);
			}
		});
	});
})(jQuery);
