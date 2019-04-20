$(function(){
	var ready = false;
	$('#agree input').change(function(){
		ready = $(this).prop('checked');
		update();
	});
	$('#start_btn').click(function(){
		location.href = ('/exam/problem');
	});
	var months_text = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
	var formatDate = function(date){
		if(+date == 0)
			return '';
		return date.getDate()+' '+months_text[date.getMonth()]+' '+(date.getFullYear()+543)+
			' '+date.toTimeString().substring(0,5);
	};
	var rtime = function (target, duration) {
		var prefix = duration>0?'ในอีก ':'ใน ';
		duration = Math.abs(duration);
		var seconds  = Math.ceil(duration);
		if(seconds < 0)
			return '';
		if(seconds < 60)
			return prefix + seconds +  ' วินาที';
		var minutes = Math.round(duration/60);
		var hours = Math.floor(minutes/60);
		if(hours < 24)
			return prefix + (hours>0?hours + ' ชั่วโมง ':'') + (minutes%60? minutes%60 + ' นาที' :'');
		hours = Math.round(minutes/60);
		var days = Math.floor(hours/24);
		if(days < 2)
			return prefix + (days>0?days + ' วัน ':'') + (hours%24? hours%24 + ' ชั่วโมง' :'');

		return 'วันที่ '+formatDate(target);
	};
	var update = function(){
		var wait = server.start_time - (Date.now()/1000+server.offset);
		if (wait > 0) {
			var msg = 'การสอบจะเริ่ม';
			msg += rtime(new Date(server.start_time*1000), wait);
			$('#timer').text(msg);
			setTimeout(update,300);
		} else {
			if(server.end_time){
				wait = (Date.now()/1000+server.offset) - server.end_time;
				if(wait > 0){
					location.href = ('/exam/logout');
					return;
				}
				var msg = 'การสอบจะจบลง';
				msg += rtime(new Date(server.start_time*1000), wait);
				$('#timer').text(msg);
				setTimeout(update,300);
			}else{
				$('#timer').addClass('hide');
			}

			$('#start_btn').toggleClass('hide', !ready);
		}
	};
	$('.ui.checkbox').checkbox();
	update();
});