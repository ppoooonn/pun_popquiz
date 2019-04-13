$(function(){
	server.problem_end_time = (server.problem_end_time - server.offset) * 1000;
	if(server.quiz_end_time)
		server.quiz_end_time = (server.quiz_end_time - server.offset) * 1000;
	else{
		server.quiz_end_time = 0;
		$('#quiz-end-timer').remove();
	}
	var load_finish = function(){
		if(--loading > 0)
			return true;
		$('#loading').addClass('hide');
		$('#content').removeClass('hide');
		start_timer();
		$.post('/exam/problem_loaded',{
			'problem': server.problem_order
		});
	};
	var start_timer = function(){
		var end = Date.now()+1000*server.problem_timer;
		if(server.problem_end_time < end)
			end = server.problem_end_time;
		if(server.quiz_end_time && server.quiz_end_time < end)
			end = server.quiz_end_time;
		var update = function(){
			var t = Math.ceil((end - Date.now())/1000);
			if(t<=0){
				clearInterval(inv);
				$('#timer').text('Timeout');
				setTimeout(function(){
					$('#answer-form').submit();
				},100);
				return;
			}
			var m = t/60|0;
			var s = t%60;
			if(s<10)s='0'+s;
			$('#timer').text(m+':'+s);

			if(server.quiz_end_time){
				var t = Math.ceil((server.quiz_end_time - Date.now())/1000);
				if(t>=0){
					var h = t/3600|0;
					t-=3600*h;
					var m = t/60|0;
					var s = t%60;
					if(m<10)s='0'+m;
					if(s<10)s='0'+s;
					$('#quiz-end-timer').text(h+':'+m+':'+s);
				}
			}
		};
		var inv = setInterval(update, 500);
		update();
	};
	$('img.zoom').click(function(){
		$('#large-img').modal('show').modal('unbind scrollLock');
	});
	$('#large-img').click(function(){
		$('#large-img').modal('hide');
	});
	$('.answer').on('change','input[name=choice]',function(){
		if(!$(this).prop('checked'))
			return true;
		$('.answer button[type=submit]').prop('disabled', false);
	});
	var imgs = $('.problem img, #large-img img');
	var loading = imgs.length;
	imgs.each(function(){
		if (this.complete && this.naturalHeight !== 0){
			load_finish();
		} else
			$(this).on('load', load_finish);
	});
	$('.ui.checkbox').checkbox();
});