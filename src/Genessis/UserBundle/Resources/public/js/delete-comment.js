$(document).ready(function(){
	$('.btn-delete-comment').click(function(e){
		e.preventDefault();
		$('.alert-danger').addClass('hidden');
		$('.alert-success').addClass('hidden');

		var row = $(this).parents('.data');
		var id = row.data('id');
		// alert(id);

		var form = $('#form-delete-comment');

		var url = form.attr('action').replace(':COMMENT_ID', id);

		var data = form.serialize();
		// alert(data);

		bootbox.confirm(message, function(res){
			if(res == true ){
				$.post(url, data, function(result){

					if(result.removed==1){
						row.fadeOut();
						$('#message').removeClass('hidden');
						$('#comment-message').text(result.message);

						var totalComments = $('#total').text();
						if($.isNumeric(totalComments)){
							$('#total').text(totalComments - 1);
						}else{
							$('#total').text(result.countComments);
						}
					}else{
						$('#message-danger').removeClass('hidden');
						$('#comment-message-danger').text(result.message);
					}
				}).fail(function(){
					alert('ERROR');
					row.show();
				});
			}
		});
	});
});