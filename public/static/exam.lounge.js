$(function(){
	var ready = false;
	$('#start_btn').click(function(){
		ready = !ready;
		$('#start_btn').toggleClass('active',ready);
		update();
	});
	var update = function(){
		var wait = server.start_time - (Date.now()/1000+server.offset);
		if (wait > 0) {
			if (ready){
				$('#start_btn').text('Starting in '+(wait|0)+' seconds');
				$('#timer').text('');
			}else{
				$('#start_btn').text('Ready');
				$('#timer').text('Quiz will start in '+(wait|0)+' seconds'); // TODO: add moment.js
			}
			setTimeout(update,200);
		} else {
			if(ready)
				window.location.replace('/exam/problem');
			else{
				$('#start_btn').text('Start Quiz').addClass('active');
				$('#timer').text('');
			}
		}
	};
	update();
});