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
	var months_text = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
	var formatDate = function(date){
		if(+date == 0)
			return '';
		return date.getDate()+' '+months_text[date.getMonth()]+' '+(date.getFullYear()+543)+
			' '+date.toTimeString().substring(0,5);
	};
	var rtime = function (target, duration) {
		// if(duration !== 0 && !duration)
		// 	duration = +(target - new Date())/1000;
		var seconds  = Math.ceil(duration);
		if(seconds < 0)
			return '';
		if(seconds < 60)
			return 'ในอีก ' + seconds +  ' วินาที';
		var minutes = Math.round(duration/60);
		var hours = Math.floor(minutes/60);
		if(hours < 24)
			return 'ในอีก ' + (hours>0?hours + ' ชั่วโมง ':'') + (minutes%60? minutes%60 + ' นาที' :'');
		hours = Math.round(minutes/60);
		var days = Math.floor(hours/24);
		if(days < 2)
			return 'ในอีก ' + (days>0?days + ' วัน ':'') + (hours%24? hours%24 + ' ชั่วโมง' :'');

		return 'วันที่ '+formatDate(target);
	};
	var update = function(){
		var wait = server.start_time - (Date.now()/1000+server.offset);
		if (wait > 0) {
			var msg;
			if (ready)
				msg = 'จะเริ่มสอบ';
			else
				msg = 'การสอบจะเริ่ม';
			msg += rtime(new Date(server.start_time*1000), wait);
			$('#timer').text(msg);
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