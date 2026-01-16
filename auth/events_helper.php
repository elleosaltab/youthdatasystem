<?php
function getEventStatus(array $event): array {
    $now = new DateTime();

    $start = new DateTime($event['start_date'] . ' ' . $event['start_time']);
    $end   = new DateTime($event['end_date']   . ' ' . $event['end_time']);

    $status = 'Ended';
    $can_register = false;

    if ($now < $start) {
        $status = 'Upcoming';
        $can_register = true; 
    } elseif ($now >= $start && $now <= $end) {
        $status = 'Ongoing';
        $can_register = ($event['cutoff_policy'] === 'allow_ongoing');
    } else {
        $status = 'Ended';
        $can_register = false;
    }

    return [
        'status' => $status,
        'can_register' => $can_register
    ];
}
