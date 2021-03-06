<?php

function event_poll_get_page_content_vote($guid) {
	elgg_require_js('event_poll/event_poll');
	$vars = array();
	$vars['id'] = 'event-poll-vote';
	$vars['name'] = 'event_poll_vote';
	// just in case a feature adds an image upload
	$vars['enctype'] = 'multipart/form-data';

	elgg_push_breadcrumb(elgg_echo('item:object:event_calendar'), 'event_calendar/list');

	$body_vars = array();
	$event = get_entity((int)$guid);
	if (elgg_instanceof($event, 'object', 'event_calendar')) {
		$body_vars['event'] = $event;
		$body_vars['form_data'] = event_poll_prepare_vote_form_vars($event);
		$event_container = get_entity($event->container_guid);
		if (elgg_instanceof($event_container, 'group')) {
			elgg_push_breadcrumb($event_container->name, 'event_calendar/group/' . $event_container->getGUID());
		}
		elgg_push_breadcrumb($event->title, $event->getURL());

		$title = elgg_echo('event_poll:vote_title');
		elgg_push_breadcrumb(elgg_echo('event_poll:vote_title'));
		$content = elgg_view_form('event_poll/vote', $vars, $body_vars);
	} else {
		$content = elgg_echo('event_poll:error_event_poll_edit');
	}

	$params = array('title' => $title, 'content' => $content, 'filter' => '');

	$body = elgg_view_layout("content", $params);

	return elgg_view_page($title,$body);
}

function event_poll_get_page_content_schedule($guid) {
	elgg_require_js('event_poll/event_poll');
	$vars = array();
	$vars['id'] = 'event-poll-schedule';
	$vars['name'] = 'event_poll_schedule';
	// just in case a feature adds an image upload
	$vars['enctype'] = 'multipart/form-data';

	elgg_push_breadcrumb(elgg_echo('item:object:event_calendar'), 'event_calendar/list');

	$body_vars = array();
	$event = get_entity((int)$guid);
	if (elgg_instanceof($event, 'object', 'event_calendar') && $event->canEdit()) {
		$body_vars['event'] = $event;
		$event_container = get_entity($event->container_guid);
		if (elgg_instanceof($event_container, 'group')) {
			elgg_push_breadcrumb($event_container->name, 'event_calendar/group/' . $event_container->getGUID());
		}
		elgg_push_breadcrumb($event->title, $event->getURL());

		$title = elgg_echo('event_poll:schedule_title', array($event->title));
		elgg_push_breadcrumb($title);
		$content = elgg_view_form('event_poll/schedule', $vars, $body_vars);
		$content .= elgg_view_form('event_poll/schedule_message', $vars, $body_vars);
	} else {
		$content = elgg_echo('event_poll:error_event_poll_edit');
	}

	$params = array('title' => $title, 'content' => $content, 'filter' => '');

	$body = elgg_view_layout("content", $params);

	return elgg_view_page($title,$body);
}

function event_poll_get_page_content_edit($page_type,$guid) {
	elgg_require_js('event_poll/event_poll');
	elgg_load_js('lightbox');
	elgg_load_css('lightbox');

	$vars = array();
	$vars['id'] = 'event-poll-edit';
	$vars['name'] = 'event_poll_edit';
	// just in case a feature adds an image upload
	$vars['enctype'] = 'multipart/form-data';

	elgg_push_breadcrumb(elgg_echo('item:object:event_calendar'), 'event_calendar/list');

	$body_vars = array();
	$event = get_entity((int)$guid);
	if (elgg_instanceof($event, 'object', 'event_calendar') && $event->canEdit()) {
		$body_vars['event'] = $event;
		$body_vars['form_data'] =  event_poll_prepare_edit_form_vars($event);
		// start date is the start of the month for this event
		$body_vars['start_date'] = gmdate("Y-m",$event->start_date)."-1";

		$event_container = get_entity($event->container_guid);
		if (elgg_instanceof($event_container, 'group')) {
			elgg_push_breadcrumb($event_container->name, 'event_calendar/group/' . $event_container->getGUID());
		}
		elgg_push_breadcrumb($event->title, $event->getURL());

		if ($page_type == 'edit') {
			$title = elgg_echo('event_poll:edit_title');
			elgg_push_breadcrumb(elgg_echo('event_poll:edit_title'));
			$content = elgg_view_form('event_poll/edit', $vars, $body_vars);
		} else {
			$title = elgg_echo('event_poll:add_title');
			elgg_push_breadcrumb(elgg_echo('event_poll:add_title'));
			$content = elgg_view_form('event_poll/edit', $vars, $body_vars);
		}

	} else {
		$title = elgg_echo('event_poll:error_title');
		$content = elgg_echo('event_poll:error_event_poll_edit');
	}

	$params = array('title' => $title, 'content' => $content, 'filter' => '');

	$body = elgg_view_layout("content", $params);

	return elgg_view_page($title,$body);
}

function event_poll_prepare_edit_form_vars($event) {
	// TODO: add content here
	return array();
}

function event_poll_get_times_dropdown() {
	return elgg_view('input/timepicker', array('name' => 'event_poll_time', 'value' => ''));
}

function event_poll_send_invitations($guid, $subject, $body, $invitees) {
	$event = get_entity($guid);
	if (elgg_instanceof($event, 'object', 'event_calendar') && $event->canEdit()) {
		// TODO as workaround simply remove any existing relationships of former invitees
		// until the functionality for editing an event poll is implemented
		// to ensure that no former invitees remain that are no longer included after editing
		remove_entity_relationships($guid, 'event_poll_invitation', true);

		$sender_guid = elgg_get_logged_in_user_guid();
		$body .= "\n\n" . elgg_get_site_url() . 'event_poll/vote/' . $guid;
		if (is_array($invitees) && count($invitees) > 0) {
			foreach($invitees as $user_guid) {
				add_entity_relationship($user_guid, 'event_poll_invitation', $guid);
			}
			// email invitees
			notify_user($invitees, $sender_guid, $subject, $body, array(), 'email');
			foreach($invitees as $invitee) {
				messages_send($subject, $body, $invitee, $sender_guid, 0, false, false);
			}
			return true;
		}
	}
	return false;
}

function event_poll_get_options($event) {
	$options = array('none');
	if ($event->event_poll) {
		$event_poll = unserialize($event->event_poll);
		foreach($event_poll as $date) {
			$iso_date = $date['iso_date'];
			foreach($date['times_array'] as $time) {
				$minutes = $time['minutes'];
				$options[] = "{$iso_date}__{$minutes}";
			}
		}
	}
	return $options;
}

function event_poll_get_response_time($event_guid, $user_guid = 0) {
	if (!$user_guid) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	$options= array(
		'guid' => $event_guid,
		'annotation_name' => 'event_poll_vote',
		'annotation_owner_guid' => $user_guid,
		'limit' => 1,
	);
	$annotations = elgg_get_annotations($options);
	if ($annotations) {
		return $annotations[0]->time_created;
	} else {
		return 0;
	}
}

function event_poll_get_times($event_guid) {
	$times = array();
	$options= array(
		'guid' => $event_guid,
		'annotation_name' => 'event_poll_vote',
		'limit' => false,
	);
	$annotations = elgg_get_annotations($options);
	foreach($annotations as $a) {
		if(!isset($times[$a->owner_guid])) {
			$times[$a->owner_guid] = array();
		}
		$times[$a->owner_guid][] = $a->value;
	}

	return $times;
}

function event_poll_get_invitees($event_guid) {
	$invitees = array();
	$options = array(
		'type' => 'user',
		'relationship' => 'event_poll_invitation',
		'relationship_guid' => $event_guid,
		'inverse_relationship' => true,
		'limit' => false,
	);
	return elgg_get_entities_from_relationship($options);
}

function event_poll_get_voted_guids($event_guid) {
	$voted = array();
	$options = array(
		'type' => 'user',
		'relationship' => 'event_poll_voted',
		'relationship_guid' => $event_guid,
		'inverse_relationship' => true,
		'limit' => false,
	);
	$users = elgg_get_entities_from_relationship($options);
	foreach($users as $u) {
		$voted[] = $u->guid;
	}
	return $voted;
}

// displays a vote table header for an event poll
function event_poll_display_vote_table_header($event_poll) {
	$table_rows = '<tr><td class="event-poll-extra-td">&nbsp;</td>';
	$table_header = '<tr><td class="event-poll-extra-td">&nbsp;</td>';
	$i = 0;
	foreach ($event_poll as $date) {
		$num_times = count($date['times_array']);
		$table_header .= '<td class="event-poll-vote-date-td-header event-poll-vote-date-td" colspan="'.$num_times.'">'.$date['human_date'].'</td>';
		$j = 0;
		foreach($date['times_array'] as $time) {
			if ($j == 0) {
				$table_rows .= '<td class="event-poll-left-td">'.$time['human_time'].'</td>';
			} else if ($j == $num_times - 1) {
				$table_rows .= '<td class="event-poll-right-td">'.$time['human_time'].'</td>';
			} else {
				$table_rows .= '<td>'.$time['human_time'].'</td>';
			}
			$j += 1;
		}

		$i += 1;
	}
	$table_header .= '<td class="event-poll-vote-date-td-header event-poll-vote-none-td1">'.elgg_echo('event_poll:none_of_these1').'</td>'; 
	$table_header .= '</tr>';
	$table_rows .= '<td class="event-poll-vote-date-td-header event-poll-vote-none-td2">&nbsp;</td>';
	$table_rows .= '</tr>';

	return $table_header . $table_rows;
}

// displays a table fragment for invitees who have voted
function event_poll_display_invitees($event_poll, $times_choices, $invitees, $voted_guids, $current_user_guid) {
	$table_rows = '';
	$others = array();
	foreach($invitees as $user) {
		if (in_array($user->guid, $voted_guids) && $user->guid != $current_user_guid) {
			$table_rows .= '<tr><td class="event-poll-name-td">' .$user->name.'</td>';
			foreach ($event_poll as $date) {
				$iso_date = $date['iso_date'];
				foreach($date['times_array'] as $time) {
					if ($time == '-') {
						$table_rows .= '<td class="event-poll-vote-internal-td">&nbsp;</td>';
					} else {
						$minutes = $time['minutes'];
						$name = "{$iso_date}__{$minutes}";
						if (isset($times_choices[$user->guid]) && in_array($name,$times_choices[$user->guid])) {
							$table_rows .= '<td class="event-poll-vote-internal-td event-poll-check-image">';
							$table_rows .= elgg_view('input/checkbox', array('value' => 1, 'checked' => 'checked', 'disabled' => 'disabled'));
							$table_rows .= '</td>';
						} else {
							$table_rows .= '<td class="event-poll-vote-internal-td">&nbsp;</td>';
						}
					}
				}
			}
			// add the none bit
			$name = "none";
			if (isset($times_choices[$user->guid]) && in_array($name, $times_choices[$user->guid])) {
				$table_rows .= '<td class="event-poll-vote-internal-td event-poll-check-image">';
				$table_rows .= elgg_view('input/checkbox', array('value' => 1, 'checked' => 'checked', 'disabled' => 'disabled'));
				$table_rows .= '</td>';
			} else {
				$table_rows .= '<td class="event-poll-vote-internal-td">&nbsp;</td>';
			}
		} else if ($user->guid != $current_user_guid) {
			$others[] = $user;
		}
		$table_rows .= '</tr>';
	}

	return array($table_rows,$others);
}

function event_poll_get_page_content_list($filter) {
	elgg_load_library('elgg:event_calendar');
	elgg_require_js('event_poll/event_poll');
	//event_calendar_handle_event_poll_add_items();
	$filter_override = elgg_view('event_poll/filter_menu', array('filter' => $filter));
	$options = array(
		'type' => 'object',
		'subtype' => 'event_calendar',
		'metadata_name_value_pairs' => array(array('name' => 'schedule_type', 'value' => 'poll')),
		'offset' => get_input('offset', 0),
		'limit' => 10,
		'full_view' => false,
	);
	if ($filter == 'all') {
		$title = elgg_echo('event_poll:list:title:show_all');
	} else if ($filter == 'mine') {
		$title = elgg_echo('event_poll:list:title:show_mine');
		$options['owner_guid'] = elgg_get_logged_in_user_guid();
	} else {
		$title = elgg_echo('event_poll:list:title:show_friends');
		$friendguids = array();
		$logged_in_user = elgg_get_logged_in_user_entity();
		if ($friends = $logged_in_user->getFriends(array('limit' => false))) {
			foreach ($friends as $friend) {
				$friendguids[] = $friend->getGUID();
			}
		}
		$options['owner_guids'] = $friendguids;
	}

	$content = elgg_list_entities($options, 'elgg_get_entities_from_metadata', 'event_poll_list_polls');

	elgg_push_breadcrumb(elgg_echo('item:object:event_calendar'), 'event_calendar/list');
	elgg_push_breadcrumb($title);

	$params = array('title' => $title, 'content' => $content, 'filter_override' => $filter_override);

	$body = elgg_view_layout("content", $params);

	return elgg_view_page($title, $body);
}

function event_poll_list_polls($es, $vars) {
	if ($vars['count'] < 1) {
		return '<p>' . elgg_echo('event_poll:listing:no_polls') . '</p>';
	}
	$r = '';
	foreach($es as $e) {
		$r .= '<li class="elgg-item">' . elgg_view('event_poll/list_poll', array('event' => $e)) . '</li>';
	}

	$nav = elgg_view('navigation/pagination', array(
		'offset' => get_input('offset', 0),
		'count' => $vars['count'],
		'limit' => 10,
		'offset_key' => 'offset',
	));

	$body = '<ul class="elgg-list elgg-list-entity">';
	$body .= $r;
	$body .= '</ul>';
	$body .= '<div class="event-poll-pagination">'.$nav.'</div>';
	
	return $body;
}

function event_poll_vote($event, $message = '', $schedule_slot = '') {
	if (elgg_instanceof($event, 'object', 'event_calendar')) {
		if ($event->canEdit()) {
			if ($schedule_slot) {
				@list($iso, $time) = explode('__', $schedule_slot);

				$event->start_time = $time;
				$event->end_time = $time + $event->event_length;
				$event->end_date = strtotime($iso);
				$event->start_date = strtotime("+ $time minutes", strtotime($iso));
				$event->real_end_time = strtotime("+ " . $event->event_length . " minutes", $event->start_date);
				$event->is_event_poll = 0;
			}
			$event_calendar_personal_manage = elgg_get_plugin_setting('personal_manage', 'event_calendar');

			if ($event_calendar_personal_manage == 'by_event') {
				$event->personal_manage = get_input('personal_manage');
			}

			$event->access_id = get_input('access_id');
			$event->send_reminder = get_input('send_reminder');
			$event->reminder_number = get_input('reminder_number');
			$event->reminder_interval = get_input('reminder_interval');

			$event->save();
		}

		$current_user = elgg_get_logged_in_user_entity();

		if (((check_entity_relationship($current_user->guid, 'event_poll_invitation', $event->guid)) || ($current_user->guid == $event->owner_guid)) && $event->event_poll) {
			elgg_delete_annotations(array('guid' => $event->guid, 'annotation_name' => 'event_poll_vote', 'annotation_owner_guid' => $current_user->guid, 'limit' => false));
			$poll_options = event_poll_get_options($event);
			foreach($poll_options as $option) {
				$tick = get_input($option);
				if ($tick) {
					create_annotation($event->guid, 'event_poll_vote', $option, null, $current_user->guid, ACCESS_PUBLIC);
				}
			}
			add_entity_relationship($current_user->guid, 'event_poll_voted', $event->guid);
			if ($message && $message != elgg_echo('event_poll:vote_message:explanation')) {
				$sender_guid = elgg_get_logged_in_user_guid();
				$subject = elgg_echo('event_poll:vote_message:subject', array($event->title));
				$message = elgg_echo('event_poll:vote_message:top', array($current_user->name))."\n\n".$message;
				notify_user($event->owner_guid, $sender_guid, $subject, $message, array(), 'email');
				messages_send($subject, $message, $event->owner_guid, $sender_guid, 0, false, false);
			}
		}
		return true;
	} else {
		return false;
	}
}

function event_poll_prepare_vote_form_vars($event) {
	// input names => defaults
	$values = array(
		'send_reminder' => null,
		'reminder_number' => 1,
		'reminder_interval' => 60,
		'personal_manage' => 'open',
		'access_id' => ACCESS_DEFAULT,
	);

	foreach (array_keys($values) as $field) {
		if (isset($event->$field)) {
			$values[$field] = $event->$field;
		}
	}

	if (elgg_is_sticky_form('event_poll')) {
		$sticky_values = elgg_get_sticky_values('event_poll');
		foreach ($sticky_values as $key => $value) {
			$values[$key] = $value;
		}
	}

	elgg_clear_sticky_form('event_poll');

	return $values;
}

function event_poll_get_current_schedule_slot($event) {
	if ($event->start_date) {
		$iso = date('Y-m-d', $event->start_date);
		$time = $event->start_time;
		return "{$iso}__{$time}";
	} else {
		return '';
	}
}

function event_poll_set_time_limits($event, $poll, $event_length) {
	$start_time = 2000000000;
	$end_time = 0;
	foreach($poll as $date) {
		$iso_date = $date['iso_date'];
		$ds = strtotime($iso_date);
		foreach($date['times_array'] as $t) {
			$m = $t['minutes'];
			$ts = strtotime("+ $m minutes", $ds);
			if ($start_time > $ts) {
				$start_time = $ts;
			}

			if ($end_time < $ts) {
				$end_time = $ts;
			}
		}
	}
	$event->event_poll_start_time = $start_time;
	$event->event_poll_end_time = $end_time + 60 * $event_length;
	$event->event_length = $event_length;
}

function elgg_poll_set_poll($guid, $poll, $event_length) {
	$event = get_entity($guid);
	if (elgg_instanceof($event, 'object', 'event_calendar') && $event->canEdit()) {
		// TODO as workaround simply remove database entries related to any former event poll votings in case it is an existing poll that gets edited
		// long time solution for editing polls might be to keep voting data if appropriate and former poll details are preserved
		elgg_delete_annotations(array('guid' => $event->guid, 'annotation_name' => 'event_poll_vote', 'limit' => false));
		remove_entity_relationships($event->guid, 'event_poll_voted', true);

		// sort the poll by time within date
		event_poll_set_time_limits($event, $poll, $event_length);

		$event->event_poll = serialize($poll);

		$event->is_event_poll = 1;
	}

	return '';
}

function event_poll_merge_poll_events($events, $start_time, $end_time) {
	$options = array(
		'type'=>'object',
		'subtype' => 'event_calendar',
		'metadata_name_value_pairs' => array(
			array('name' => 'is_event_poll', 'value' => 1),
			array('name' => 'event_poll_start_time', 'value' => $start_time, 'operand' => '>='),
			array('name' => 'event_poll_start_time', 'value' => $end_time, 'operand' => '<=')
		),
		'limit' => false,
	);
	$eps = elgg_get_entities_from_metadata($options);
	foreach($eps as $e) {
		$event_length = $e->event_length;
		$p = unserialize($e->event_poll);
		$data = array();
		foreach($p as $times_data) {
			$iso_date = $times_data['iso_date'];
			$dts = strtotime($iso_date);
			if (isset($times_data['times_array'])) {
				foreach($times_data['times_array'] as $item) {
					$m = $item['minutes'];
					$ts = strtotime("+ $m minutes", $dts);
					$data[] = array('start_time' => $ts, 'end_time' => $ts+60*$event_length, 'iso_date' => $iso_date, 'minutes' => $m);
				}
			}
		}
		$events[] = array('event' => $e,'is_event_poll' => true, 'data' => $data);
	}

	return $events;
}

function event_poll_handle_event_poll_add_items($group_guid = 0) {
	if ($group_guid) {
		$url_schedule_event =  "event_calendar/schedule/$group_guid";
	} else {
		$url_schedule_event =  "event_calendar/schedule";
	}

	$item = new ElggMenuItem('event-calendar-1schedule', elgg_echo('event_calendar:schedule_event'), $url_schedule_event);
	$item->setSection('event_poll');
	elgg_register_menu_item('page', $item);
}

// TODO: make human date configurable
function event_poll_change($event_guid, $day_delta, $minute_delta, $new_time, $resend, $minutes, $iso_date) {
	$event = get_entity($event_guid);
	if (elgg_instanceof($event, 'object', 'event_calendar') && $event->canEdit() && $event->is_event_poll) {
		$poll = unserialize($event->event_poll);
		$new_poll = array();
		// remove previous value
		foreach($poll as $option) {
			if ($option['iso_date'] == $iso_date) {
				$t = $option['times_array'];
				$new_t = array();
				foreach($t as $opt) {
					if ($opt['minutes'] != $minutes) {
						$new_t[] = $opt;
					}
				}
				if ($new_t) {
					$option['times_array'] = $new_t;
					$new_poll[] = $option;
				}
			} else {
				$new_poll[] = $option;
			}
		}

		// add new value
		$new_ts = strtotime("$day_delta days", strtotime($iso_date));
		$new_iso_date = date('Y-m-d', $new_ts);
		$new_minutes = $minutes + $minute_delta;
		$done = false;
		foreach($new_poll as $option) {
			$iso_date = $option['iso_date'];
			if ($iso_date == $new_iso_date) {
				$option['times_array'][] = array('minutes' => $new_minutes, 'human_time' => date('g:i a', $new_ts+($new_minutes*60)));
				usort($option['times_array'], 'event_poll_sort_times_array');
				$done = true;
			}
		}
		if (!$done) {
			$new_option = array(
				'iso_date' => $new_iso_date,
				'human_date' => date("F j, Y", $new_ts),
				'times_array' => array(array('minutes' => $new_minutes, 'human_time' => date('g:i a',$new_ts))),
			);
			$new_poll[] = $new_option;
			usort($new_poll, 'event_poll_sort');
		}
		$poll = $new_poll;
		$event->event_poll = serialize($poll);
		event_poll_set_time_limits($event, $poll, $event->event_length);
		if ($resend) {
			event_poll_resend_invitations($event);
		}
		return array('minutes' => $new_minutes, 'iso_date' => $new_iso_date);
	}
	return false;
}

function event_poll_sort_times_array($a, $b) {
	return $a['minutes'] - $b['minutes'];
}

function event_poll_sort($a, $b) {
	return strtotime($a['iso_date']) - strtotime($b['iso_date']);
}

function event_poll_resend_invitations($event) {
	$subject = elgg_echo('event_poll:reschedule_subject', array($event->title));
	$body = elgg_echo('event_poll:reschedule_body');
	$invitees = event_poll_get_invitees($event->guid);
	$guids = array();
	foreach($invitees as $invitee) {
		$guids[] = $invitee->guid;
	}
	$sender_guid = elgg_get_logged_in_user_guid();
	$body .= "\n\n" . elgg_get_site_url() . 'event_poll/vote/' . $event->guid;
	if (is_array($invitees) && count($invitees) > 0) {
		// email invitees
		notify_user($guids, $sender_guid, $subject, $body, array(), 'email');
		foreach($guids as $guid) {
			messages_send($subject, $body, $guid, $sender_guid, 0, false, false);
		}
	}
	return true;
}
