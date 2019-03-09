$(function(){
	var load_finish = function(){
		if(--loading > 0)
			return true;
		$('#loading').addClass('hide');
		$('#content').addClass('show');
		start_timer();
		$.post('/exam/problem_loaded',{
			'problem': server.problem_order
		});
	};
	var start_timer = function(){
		var end = Date.now()+1000*server.problem_timer;
		if(server.end_time < end)
			end = server.end_time;
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