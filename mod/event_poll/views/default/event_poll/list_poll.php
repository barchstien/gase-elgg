<?php

elgg_load_library('elgg:event_poll');

$event = $vars['event'];

$title = elgg_view('output/url', array(
	'href' => 'event_poll/vote/'.$event->guid,
	'text' => $event->title,
	'is_trusted' => true,
));

$owner = $event->getOwnerEntity();
$owner_icon = elgg_view_entity_icon($owner, 'tiny');
$owner_link = elgg_view('output/url', array(
	'href' => "event_calendar/owner/$owner->username",
	'text' => $owner->name,
	'is_trusted' => true,
));
$author_text = elgg_echo('byline', array($owner_link));
$date = elgg_view_friendly_time($event->time_created);

$subtitle = "$author_text $date";

$body = '<div class="mts">';
if (event_poll_get_current_schedule_slot($event)) {
	$body .= '<label>' . elgg_echo('event_poll:listing:scheduled') . '</label>' . event_calendar_get_formatted_time($event);
} else {
	$body .= '<label>' . elgg_echo('event_poll:listing:responded') . '</label>';
	$time_responded = event_poll_get_response_time($event->guid);
	if ($time_responded) {
		$body .= elgg_get_friendly_time($time_responded);
	} else {
		$body .= elgg_echo('event_poll:listing:not_responded');
	}
}
$body .= '</div>';

if (elgg_in_context('widgets') || !$event->canEdit()) {
	$metadata = '';
} else {
	$metadata = elgg_view_menu('entity', array(
		'entity' => $event,
		'handler' => 'event_poll',
		'sort_by' => 'priority',
		'class' => 'elgg-menu-hz',
	));
}

$params = array(
	'entity' => $event,
	'title' => $title,
	'metadata' => $metadata,
	'subtitle' => $subtitle,
	'content' => $body,
	'tags' => false
);
$params = $params + $vars;
$list_body = elgg_view('object/elements/summary', $params);

echo elgg_view_image_block($owner_icon, $list_body);
