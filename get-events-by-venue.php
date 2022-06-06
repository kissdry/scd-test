<?php

declare(strict_types=1);

namespace testTask;

require_once __DIR__ . '/PantomimeApi.php';

$api = new PantomimeApi();

try {
    $venues = $api->getVenues();
    $events = $api->getEvents();
    $instances = $api->getInstances();
} catch (\Exception $exception) {
    exit($exception->getMessage());
}

$venues = addEventsToVenues($venues, $events, $instances);
uasort($venues, 'testTask\sortVenues');

function addEventsToVenues(array $venues, array $events, array $instances): array
{
    $events = addInstancesToEvents($events, $instances);

    foreach ($venues as $venueId => $venue) {
        $venues[$venueId]['events'] = [];
    }

    foreach ($events as $eventId => $event) {
        if ($event['instanceCount'] === 0) {
            continue;
        }
        $instance = reset($event['instances']);
        $venueId = $instance['venue']['id'];
        $venues[$venueId]['events'][$eventId] = $event;
    }

    foreach ($venues as $venueId => $venue) {
        if (empty($venues[$venueId]['events'])) {
            unset($venues[$venueId]);
            continue;
        }
        uasort($venues[$venueId]['events'], 'testTask\sortEvents');
    }

    return $venues;
}

function addInstancesToEvents(array $events, array $instances): array
{
    foreach ($events as $eventId => $event) {
        $events[$eventId]['instanceCount'] = 0;
        $events[$eventId]['audioDescribedInstanceCount'] = 0;
        $events[$eventId]['instances'] = [];
    }

    foreach ($instances as $instance) {
        $instanceId = $instance['id'];
        $eventId = $instance['event']['id'];

        if (!isset($events[$eventId])) {
            // event is off sale
            continue;
        }

        $events[$eventId]['instances'][$instanceId] = $instance;

        $events[$eventId]['instanceCount']++;

        if ($instance['attribute_audioDescribed'] === true) {
            $events[$eventId]['audioDescribedInstanceCount']++;
        }

        if (
            !isset($events[$eventId]['firstInstance']) ||
            $instance['start'] < $events[$eventId]['firstInstance']
        ) {
            $events[$eventId]['firstInstance'] = $instance['start'];
        }

        if (
            strtotime($instance['start']) > time() &&
            (
                !isset($events[$eventId]['nextInstance']) ||
                $instance['start'] < $events[$eventId]['nextInstance']
            )
        ) {
            $events[$eventId]['nextInstance'] = $instance['start'];
        }

        if (
            !isset($events[$eventId]['lastInstance']) ||
            $instance['start'] > $events[$eventId]['lastInstance']
        ) {
            $events[$eventId]['lastInstance'] = $instance['start'];
        }
    }

    return $events;
}

function sortEvents(array $event1, array $event2): int
{
    if ($event1['firstInstance'] === $event2['firstInstance']) {
        return 0;
    }
    return ($event1['firstInstance'] < $event2['firstInstance']) ? -1 : 1;
}

function sortVenues(array $venue1, array $venue2): int
{
    if ($venue1['title'] === $venue2['title']) {
        return 0;
    }
    return ($venue1['title'] < $venue2['title']) ? -1 : 1;
}

?>
<ul>
<?php foreach ($venues as $venue): ?>
    <li>
        <?= $venue['title'] ?>:
        <ul>
<?php foreach ($venue['events'] as $event): ?>
            <li>
                <?= $event['title'] ?>: <br>
                id: <?= $event['numericId'] ?> <br>
                First instance: <?= $event['firstInstance'] ?> <br>
                Next instance: <?= $event['nextInstance'] ?> <br>
                Last instance: <?= $event['lastInstance'] ?> <br>
                Instance count: <?= $event['instanceCount'] ?> <br>
                Audio Described instance count: <?= $event['audioDescribedInstanceCount'] . "\n" ?>
            </li>
<?php endforeach; ?>
        </ul>
    </li>
<?php endforeach; ?>
</ul>
