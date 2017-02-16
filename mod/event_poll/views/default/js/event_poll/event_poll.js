define(function(require) {
	var elgg = require("elgg");
	var $ = require("jquery");

	var event_polls = [];
	var max_time_count = 0;
	var click_id = 0;

	function init() {
		$('#event-poll-next-button').on('click', handleStage3);
		//$('#event-poll-next2-button').on('click', handleStage3);
		$('#event-poll-back2-button').on('click', handleStage1);
		$('#event-poll-edit-link').on('click', handleStage1);
		$('#event-poll-back-button').on('click', handleStage1);
		$('#event-poll-send-button').on('click', sendPoll);
		$(document).on('click', '.event-poll-date-option1-remove', removeOption1);
		$(document).on('click', '.event-poll-date-option2-remove', removeOption2);
		$('#event-poll-vote-message').on('click', handleVoteMessage);
		$('#event-poll-length-hour').on('change', handleChangeLength);
		$('#event-poll-length-minute').on('change', handleChangeLength);
		$('.event-poll-vote-checkbox').on('click', handleVoteChoice);
		$('.event-poll-vote-none-checkbox').on('click', handleVoteNoneChoice);
		$('[name="schedule_slot"][type=radio]').on('change', handleTimeSelection);
		handleTimeSelection();
		setupCalendar();
		//prevent odd user picker behaviour on enter
		$(document).on("autocompleteopen", ".ui-autocomplete-input", function(event) {
			var autocomplete = $(this).data("autocomplete");
			menu = autocomplete.menu;
			menu.activate( $.Event({ type: "mouseenter" }), menu.element.children().first() );
		});
	}

	handleVoteChoice = function(e) {
		$('.event-poll-vote-none-checkbox').attr('checked', false);
	}

	handleVoteNoneChoice = function(e) {
		$('.event-poll-vote-checkbox').attr('checked', false);
	}

	handleTimeSelection = function() {
		if ($('[name="schedule_slot"][type=radio]:checked').val()) {
			$('#event-poll-vote-event-data-wrapper').show();
			$(".event-calendar-edit-reminder-wrapper").show();
			$(".event-calendar-edit-form-membership-block").show();
			$(".event-calendar-edit-form-share-block").show();
		}
	}

	setupCalendar = function() {
		var loadFullCalendar = function() {
			var locale = $.datepicker.regional[elgg.get_language()];

			if (!locale) {
				locale = $.datepicker.regional[''];
			}

			$('#calendar').fullCalendar({
				header: {
					left: 'prev,next today',
					center: 'title',
					right: '',
				},
				defaultView: 'agendaWeek',
				allDaySlot: false,
				month: $('#event-poll-month-number').val(),
				firstHour: $('#event-poll-hour-number').val(),
				ignoreTimezone: true,
				editable: false,
				slotMinutes: 15,
				dayClick: handleDayClick,
				eventClick : handleEventClick,
				eventAfterRender : handleEventRender, 
				events: handleGetEvents,
				isRTL:  locale.isRTL,
				firstDay: locale.firstDay,
				monthNames: locale.monthNames,
				monthNamesShort: locale.monthNamesShort,
				dayNames: locale.dayNames,
				dayNamesShort: locale.dayNamesShort,
				buttonText: {
					today: locale.currentText,
					month: elgg.echo('event_calendar:month_label'),
					week: elgg.echo('event_calendar:week_label'),
					day: elgg.echo('event_calendar:day_label')
				},
				timeFormat: $('#event-poll-time-format').val()
			});
		}

		if ($('#calendar').length > 0) {
			var deps = ['jquery-ui', 'jquery-ui/datepicker'];
			if (elgg.get_language() != 'en') {
				deps.push('jquery-ui/i18n/datepicker-'+ elgg.get_language() + '.min');
			}
			require(deps, loadFullCalendar);
		}
	}

	handleEventRender = function(event,element,view) {
		var guid = $('#event-poll-event-guid').val();
		if (event.guid == guid) {
			var click_id = event.click_id;
			element.find('.fc-event-title').prepend('<span rel="'+click_id+'" class="event-poll-delete-cell">[x]</span> ');
			//element.after('<span rel="'+click_id+'" class="event-poll-delete-cell">[x]</span>');
		}
	}

	deleteCell = function(e) {
		var click_id = $(this).attr('rel');
		alert(click_id);
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
		return false;
	}

	handleDayClick = function(date) {
		var stage = $('#event-poll-stage').val();
		if (stage == 1) {
			var h = '<div class="event-poll-date-options">';
			h += '<a class="event-poll-date-option1-remove" href="#"><span class="elgg-icon fa fa-times "></span></a>';
			h += '<span class="event-poll-human-date">'+formatDate(date)+'</span>';
			h += '<span class="event-poll-iso-date">'+getISODate(date)+'</span>';
			h += '<span class="event-poll-click-id">'+click_id+'</span>';
			h += '</div>';
			$('#event-poll-date-container').addClass('event-poll-date-alert');
			setTimeout(function() {$('#event-poll-date-container').removeClass('event-poll-date-alert');},250);
			$('#event-poll-date-wrapper').append(h);

			addEventPollOptionToCalendar(date);
		}
	};

	addEventPollOptionToCalendar = function(date) {
		var guid = $('#event-poll-event-guid').val();
		var lgth_m = parseInt($('#event-poll-length-minute').val());
		var lgth_h = parseInt($('#event-poll-length-hour').val());
		var end_date = new Date(date.getTime()+1000*(lgth_h*60*60+lgth_m*60));
		var event_item = {
			id: guid,
			guid: guid,
			click_id: click_id,
			title: $('#event-poll-event-title').val(),
			url: $('#event-poll-event-url').val(),
			start:  date.getTime()/1000,
			end : end_date.getTime()/1000,
			className: 'event-poll-new-class',
			allDay: false
		};
		$('#calendar').fullCalendar('renderEvent',event_item,true);
		click_id += 1;
	}

	handleEventClick = function(event,e) {
		if (e.target.className == 'event-poll-delete-cell') {
			click_id = e.target.getAttribute('rel');
			$('.event-poll-date-options').each(
				function(v) {
					var this_click_id = $(this).find('.event-poll-click-id').html();
					if (click_id == this_click_id) {
						$(this).remove();
					}
				}
			);
			$('#calendar').fullCalendar('removeEvents', function(event) { return event.click_id == click_id; });
			return false;
		} else {
			if (event.url) {
				$.colorbox({'href':event.url});
				return false;
			}
		}
	};

	formatDate = function(date) {
		var d = $.datepicker.formatDate("MM d, yy", date);
		var h = date.getHours();
		var m = date.getMinutes();
		mf = m < 10 ? '0' + m : m;
		var lgth_m = $('#event-poll-length-minute').val();
		var lgth_h = $('#event-poll-length-hour').val();
		if($('#event-poll-time-format').val() == '12') {
			if (h == 0) {
				t = "12:"+mf+" am";
			} else if (h == 12) {
				t = "12:"+mf+" pm";
			} else if (h < 12) {
				t = h+":"+mf+" am";
			} else {
				t = (h-12)+":"+mf+" pm";
			}
		} else {
			t = h+":"+mf;
		}
		var r = '';
		date2 = new Date(date.getTime());
		if(lgth_m) {
			date2.setHours(h + parseInt(lgth_h));
			date2.setMinutes(m + parseInt(lgth_m));
			var d2 = $.datepicker.formatDate("MM d, yy", date2);
			var h2 = date2.getHours();
			var m2 = date2.getMinutes();
			mf2 = m2 < 10 ? '0' + m2 : m2;
			if($('#event-poll-time-format').val() == '12') {
				if (h2 == 0) {
					t2 = "12:"+mf2+" am";
				} else if (h2 == 12) {
					t2 = "12:"+mf2+" pm";
				} else if (h2 < 12) {
					t2 = h2+":"+mf2+" am";
				} else {
					t2 = (h2-12)+":"+mf2+" pm";
				}
			} else {
				t2 = h2+":"+mf2;
			}
			if (d == d2) {
				r = d+" ("+t+" - "+t2+')';
			} else {
				r = d+" ("+t+") - "+d2+" ("+t2+")";
			}
		} else {
			r = d+" ("+t+")";
		}
		r += '<span class="event-poll-human-date-bit">'+d+'</span><span class="event-poll-human-time-bit">'+t+'</span>';
		return r;
	}

	doNothing = function(e,jsEvent) {
		handleDayClick(e.start);
		jsEvent.preventDefault();
		jsEvent.stopPropagation();
		jsEvent.stopImmediatePropagation();
	}

	getISODate = function(d) {
		var year = d.getFullYear();
		var month = d.getMonth()+1;
		month =	month < 10 ? '0' + month : month;
		var day = d.getDate();
		day = day < 10 ? '0' + day : day;
		var h = d.getHours();
		var m = d.getMinutes();
		m =	m < 10 ? '0' + m : m;
		return h+":"+m+" "+year +"-"+month+"-"+day;
	}

	formatTime = function(d) {
		var hours = d.getHours();
		var minutes = d.getMinutes();
		minutes = minutes < 10 ? '0' + minutes : minutes;
		return hours+":"+minutes;
	}

	handleGetEvents = function(start, end, callback) {
		var start_date = getISODate(start);
		var end_date = getISODate(end);
		var groupguid = $('#event-poll-event-groupguid').val();
		var url = "event_calendar/get_fullcalendar_events/"+start_date+"/"+end_date+"/all/"+groupguid;
		elgg.getJSON(url, {success: function(events) {
				//var guid = $('#event-poll-event-guid').val();
				//$.each(events,function(k,e) {if (e.guid == guid) { e.className = 'event-poll-new-class'; }});
				//callback(events);
				// TODO for now do not display former event slot options when editing an event poll as they are not preserved anyway
				// if the editing of event polls is revised this would have to be revised too
				var guid = $('#event-poll-event-guid').val();
				var other_events = new Array();
				$.each(events,function(k,e) {if (e.guid != guid) { other_events[k] = e; }});
				callback(other_events);
			}
		});
	}

	// TODO: simplify this next function
	createEventObject = function() {
		var event_poll_object = {};
		max_time_count = 0;
		$('.event-poll-date-options').each(
			function() {
				var iso = $(this).find('.event-poll-iso-date').html();
				var human_time = $(this).find('.event-poll-human-time-bit').html();
				var human_date = $(this).find('.event-poll-human-date-bit').html();
				var td_bits = iso.split(" ");
				var t_bits = td_bits[0].split(":");
				var h = parseInt(t_bits[0]);
				var m = parseInt(t_bits[1]);
				var t = h*60+m;
				var d = td_bits[1];
				if (!(d in event_poll_object)) {
					event_poll_object[d] = {};
					event_poll_object[d]['iso_date'] = d;
					event_poll_object[d]['human_date'] = human_date;
					event_poll_object[d]['times'] = [];
					event_poll_object[d]['human_times'] = [];
					event_poll_object[d]['time_object'] = {};
				}
				event_poll_object[d]['time_object'][t] = {minutes: t, human_time:human_time};
				event_poll_object[d]['times'].push(t);
				event_poll_object[d]['human_times'].push(human_time);
				if (max_time_count < event_poll_object[d]['times'].length) {
					max_time_count = event_poll_object[d]['times'].length;
				}
			}
		);
		// now sort the structure into the event_polls array
		var iso_dates = getKeys(event_poll_object);
		iso_dates.sort();
		event_polls = [];
		for (var i = 0; i < iso_dates.length; i++) {
			var pobj = event_poll_object[iso_dates[i]];
			var nobj = {iso_date: pobj['iso_date'], human_date: pobj['human_date']};
			var tobj = pobj['time_object'];
			var times = getKeys(tobj);
			times.sort(function(a,b) { return a-b; });
			var ta = [];
			for (var j = 0; j < times.length; j++) {
				ta.push(tobj[times[j]]);
			}
			nobj['times_array'] = ta;
			event_polls.push(nobj);
		}
	}

	getKeys = function(obj)  {
		var keys = [];

		for(var key in obj) {
			if(obj.hasOwnProperty(key)) {
				keys.push(key);
			}
		}

		return keys;
	}

	handleStage2 = function(e) {
		// remove the existing table, if any
		$('#event-poll-date-times-table').remove();
		// set up the new table
		var tb = '<table id="event-poll-date-times-table"><tr>';
		tb += '<th class="event-poll-date-times-table-date">&nbsp;</th>';
		// TODO - make the number of time slots configurable
		tb += '<th class="event-poll-date-times-table-time">'+elgg.echo("event_poll:time1")+'</th>';
		tb += '<th class="event-poll-date-times-table-time">'+elgg.echo("event_poll:time2")+'</th>';
		tb += '<th class="event-poll-date-times-table-time">'+elgg.echo("event_poll:time3")+'</th>';
		tb += '</tr></table>';
		// insert the new table
		$('#event-poll-date-times-table-wrapper').prepend(tb);
		// add the data rows
		$('.event-poll-date-options').each(insertTableRow);
		// get the times dropdown and populate the table with it
		elgg.get('event_poll/get_times_dropdown', {success: populateTimesDropdowns});
		$('#event-poll-next-button').hide();
		$('#event-poll-next2-button').show();
		$('#event-poll-back-button').show();
		$('#event-poll-back2-button').hide();
		$('#event-poll-send-button').hide();
		$('#event-poll-title1').hide();
		$('#event-poll-title2').show();
		$('#event-poll-title3').hide();
		$('#event-poll-stage').val(2);
		$('#event-poll-date-container').hide();
		$('#event-poll-date-times-table-wrapper').show();
		$('#event-poll-date-times-table-read-only-wrapper').hide();
		$('#event-poll-stage3-wrapper').hide();
		// show calendar
		$('#calendar').show();
		e.preventDefault();
	}

	handleStage3 = function(e) {
		createEventObject();
		var event_length = 60*parseInt($('#event-poll-length-hour').val()) + parseInt($('#event-poll-length-minute').val());
		elgg.action('event_poll/set_poll',{data : {poll: event_polls, event_length:event_length, guid: $('#event-poll-event-guid').val()}});
		populateReadOnlyTable(max_time_count);

		$('#event-poll-date-container').hide();
		$('#event-poll-stage1-wrapper').hide();
		$('#event-poll-send-button').hide();

		// show read-only table
		$('#event-poll-date-times-table-read-only-wrapper').show();
		$('#event-poll-date-times-table-wrapper').hide();

		// hide calendar
		$('#calendar').hide();

		// show title
		$('#event-poll-title1').hide();
		$('#event-poll-title2').hide();
		$('#event-poll-title3').show();

		// show invitation form
		$('#event-poll-stage3-wrapper').show();

		// show buttons
		$('#event-poll-next-button').hide();
		$('#event-poll-back-button').hide();
		$('#event-poll-back2-button').show();
		$('#event-poll-send-button').show();

		// set stage
		$('#event-poll-stage').val(3);

		e.preventDefault();
	}

	populateReadOnlyTable = function() {
		$('#event-poll-readonly-table').remove();
		// set up the new table
		var tb = '<table id="event-poll-readonly-table"><tr>';
		tb += '<th class="event-poll-date-times-table-date">&nbsp;</th>';
		// TODO - make the number of time slots configurable
		for (var i=1; i <= max_time_count; i++) {
			tb += '<th class="event-poll-date-times-table-time">'+elgg.echo("event_poll:time"+i)+'</th>';
		}

		tb += '</tr></table>';
		// insert the new table
		$('#event-poll-date-times-table-read-only-wrapper').prepend(tb);
		// add the data rows
		$.each(event_polls, insertReadOnlyTableRow);
	}

	insertReadOnlyTableRow = function(index,item) {
		var human = item['human_date'];
		var times = item['times_array'];
		var t = '<tr class="event-poll-readonly-table-row">';
		t += '<td>';
		t += '<span class="event-poll-human-date">'+human+'</span>';
		t += '</td>';
		$.each(times,
			function (index,time) {
				t += '<td class="event-poll-time-readonly">'+time['human_time']+'</td>';
			}
		);
		t += '</tr>';
		$('#event-poll-readonly-table').append(t);
	}

	handleStage1 = function(e) {
		$('#event-poll-back2-button').hide();
		$('#event-poll-send-button').hide();
		$('#event-poll-next-button').show();
		$('#event-poll-next2-button').hide();
		$('#event-poll-back-button').hide();
		$('#event-poll-title1').show();
		$('#event-poll-title2').hide();
		$('#event-poll-title3').hide();
		$('#event-poll-stage').val(1);
		$('#event-poll-date-container').show();
		$('#calendar').show();
		$('#event-poll-date-times-table-wrapper').hide();
		$('#event-poll-stage3-wrapper').hide();
		$('#event-poll-stage1-wrapper').show();
		e.preventDefault();
	}

	handleChangeLength = function(e) {
		click_id = 0;
		createEventObject();
		$('#event-poll-date-wrapper').remove();
		$('#event-poll-date-container').append('<div id="event-poll-date-wrapper"></div>');
		$.each(event_polls, insertDateDiv);
		var save_click_id = click_id;
		click_id = 0;
		// remove event poll options from calendar
		var guid = $('#event-poll-event-guid').val();
		$('#calendar').fullCalendar('removeEvents', function(e) { return e.guid == guid; });
		// add event polls back to calendar with corrected times
		$.each(event_polls, insertEventPollOption);
		click_id = save_click_id;
	}

	insertEventPollOption = function(key,value) {
		var times = value['times_array'];
		var iso_date = value['iso_date'];
		var date_bits = iso_date.split('-');
		for (var i = 0; i < times.length; i++) {
			var minutes = times[i]['minutes'];
			var date = new Date(parseInt(date_bits[0]),parseInt(date_bits[1])-1,parseInt(date_bits[2]),0,minutes);
			addEventPollOptionToCalendar(date);
		}
	}

	insertTableRow = function(index) {
		var human = $(this).find('.event-poll-human-date').html();
		var iso = $(this).find('.event-poll-iso-date').html();
		var t = '<tr class="event-poll-date-times-table-row">';
		t += '<td>';
		t += '<a class="event-poll-date-option2-remove" href="#"><span class="elgg-icon fa fa-times "></span></a>';
		t += '<span class="event-poll-human-date">'+human+'</span><span class="event-poll-iso-date">'+iso+'</span></td>';
		t += '<td class="event-poll-times-dropdown"></td><td class="event-poll-times-dropdown"></td><td class="event-poll-times-dropdown"></td>';
		t += '</tr>';
		$('#event-poll-date-times-table').append(t);
	}

	insertDateDiv = function(key,value) {
		var date_bits = value['iso_date'].split('-');
		var date = new Date(parseInt(date_bits[0]),parseInt(date_bits[1])-1,parseInt(date_bits[2]));
		var t = date.getTime();
		$.each(value['times_array'], function(k,v) {
			var nd = new Date(t);
			nd.setMinutes(v['minutes']);
			handleDayClick(nd);
		});
	}

	setSelectValuesForRow = function() {
		var iso_date = $(this).find('.event-poll-iso-date').html();
		for(var i = 0; i < event_polls.length; i++) {
			if (event_polls[i]['iso_date'] == iso_date) {
				$($(this).find('[name="event_poll_time"]')).each(
					function (index) {
						var time = event_polls[i]['times_array'][index]['minutes'];
						$(this).val(time);
					}
				);
			}
		}
	}

	populateTimesDropdowns = function(data) {
		$('.event-poll-times-dropdown').append(data);
		$('.event-poll-date-times-table-row').each(setSelectValuesForRow);
	}

	removeOption1 = function(e) {
		var p = $(this).parent();
		var iso_date = p.find('.event-poll-iso-date').html();
		var click_id = p.find('.event-poll-click-id').html();
		$('#calendar').fullCalendar('removeEvents', function(e) { return e.click_id == click_id; });
		$(this).parent().remove();
		removeDate(iso_date);
	}

	removeDate = function(iso_date) {
		var new_event_polls = [];
		for(var i = 0; i < event_polls.length; i++) {
			if (event_polls[i]['iso_date'] != iso_date) {
				new_event_polls.push(event_polls[i]);
			}
		}
		event_polls = new_event_polls;
	}

	removeOption2 = function(e) {
		var iso_date = $(this).find('.event-poll-iso-date').html();
		$(this).parent().parent().remove();
		removeDate(iso_date);
	}

	sendPoll = function(e) {
		d = {
			guid : $('#event-poll-event-guid').val(),
			subject : $('[name="invitation_subject"]').val(),
			body : $('[name="invitation_body"]').val(),
			invitees : $('input[name="members[]"]').map(function(){return $(this).val();}).get()
		};
		elgg.action('event_poll/invite', {data: d, success: sendPollResponse});
		//$('input[name="members[]"]').parent().remove();
		e.preventDefault();
	}

	sendPollResponse = function(response) {
		var r = response['output'];
		if (r['success']) {
			elgg.system_message(r['msg']);
		} else {
			elgg.register_error(r['msg']);
		}
		elgg.forward('event_poll/list/all');
	}

	handleVoteMessage = function(e) {
		var m = elgg.echo('event_poll:vote_message:explanation');
		if ($(this).html() == m) {
			$(this).html('');
		}
	}

	return init();
});