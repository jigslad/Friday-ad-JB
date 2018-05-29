;(function () {
  var $body = $('body');

	$body.on('dragenter dragleave', function (e) {
		e.preventDefault();

		var $hierarchy = $(e.target).parents().add(e.target),
				$droppables = $hierarchy.filter('.droppable'),
				countOffset = e.type === 'dragenter' ? 1 : -1;
		var showDroppableOverLay = false;

		$droppables.add($body).each(function () {
			var dragCount = ($(this).data('dragCount') || 0) + countOffset;

			if (dragCount && e.originalEvent.dataTransfer.types) {
			    for (var i=0; i<e.originalEvent.dataTransfer.types.length; i++) {
			        //console.log(e.originalEvent.dataTransfer.types[i]);
			        if (e.originalEvent.dataTransfer.types[i] == "Files") {
			            showDroppableOverLay = true;
			        }
			    }
			}
			if (showDroppableOverLay && dragCount > 0) {
				$('.img-upload-layer').show();
				$("#drag_drop_hover").addClass("drag-drop-box-hover");
			} else {
				$('.img-upload-layer').hide();
				$("#drag_drop_hover").removeClass("drag-drop-box-hover");
			}
			$(this)
				.data('dragCount', dragCount)
				.toggleClass('dragging', dragCount > 0);
		});
	});

	$body.on('dragover', function (e) {
		e.preventDefault();

		var isDroppable = false;
		$('.droppable').each(function () {
			if($(this).data('dragCount') > 0)
				isDroppable = true;
		});

		e.originalEvent.dataTransfer.dropEffect = isDroppable ? 'copy' : 'none';
	});

	$body.on('drop', function (e) {
		e.preventDefault();
		$('.droppable').add($body).removeClass('dragging').removeData('dragCount');
		$('.img-upload-layer').hide();
		$("#drag_drop_hover").removeClass("drag-drop-box-hover");
		scrollToElement('#upload_image_div', '1000');
	});
})();
