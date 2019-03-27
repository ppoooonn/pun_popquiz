$(function(){
	var ready = false;
	$('#agree').change(function(){
		ready = true;
		$('#agree').parent().addClass('hide');
		update();
	});
	$('#start_btn').click(function(){
		window.location.replace('/exam/problem');
	});
	var update = function(){
		var wait = server.start_time - (Date.now()/1000+server.offset);
		if (wait > 0) {
			var msg;
			if (ready)
				msg = 'จะเริ่มสอบใน ';
			else
				msg = 'การสอบจะเริ่มใน ';
			$('#timer').text(msg+(wait|0)+' seconds'); // TODO: add moment.js
			setTimeout(update,200);
		} else {
			if(ready)
				window.location.replace('/exam/problem');
			else{
				$('#start_btn').removeClass('hide');
				$('#agree').parent().addClass('hide');
				$('#timer').addClass('hide');
			}
		}
	};
	$('.ui.checkbox').checkbox();
	update();
});