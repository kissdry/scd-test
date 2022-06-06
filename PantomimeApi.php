<?php

declare(strict_types=1);

namespace testTask;

use Exception;

class PantomimeApi
{
    /** @var string constant part of API endpoints URLs */
    const BASE_URL = 'https://supercooldesign.co.uk/api/technical-test';

    /**
     * Returns events.
     *
     * @param bool $onSaleOnly
     * @return array
     * @throws Exception
     */
    public function getEvents(bool $onSaleOnly = true): array
    {
        $result = [];
        $events = $this->getResponse('/events');
        foreach ($events as $event) {
            $event['isOnSale'] =
                (strtotime($event['startSelling']) <= time()) &&
                (strtotime($event['stopSelling']) > time());

            $event['numericId'] =
                preg_match('#^\d+#', $event['id'], $match)
                ? $match[0]
                : 'n/a';

            if ($onSaleOnly === true && $event['isOnSale'] === false) {
                continue;
            }

            $result[$event['id']] = $event;
        }
        return $result;
    }

    /**
     * Returns venues.
     *
     * @return array
     * @throws Exception
     */
    public function getVenues(): array
    {
        $result = [];
        $venues = $this->getResponse('/venues');
        foreach ($venues as $venue) {
            $result[$venue['id']] = $venue;
        }
        return $result;
    }

    /**
     * Returns instances.
     *
     * @return array
     * @throws Exception
     */
    public function getInstances(): array
    {
        $result = [];
        $instances = $this->getResponse('/instances');
        foreach ($instances as $instance) {
            $result[$instance['id']] = $instance;
        }
        return $result;
    }

    /**
     * Returns API response in form of array.
     *
     * @param string $endpoint variable part of API endpoint URL
     * @return array
     * @throws Exception if failed to get response or invalid json was received
     */
    protected function getResponse(string $endpoint): array
    {
        $url = rtrim(self::BASE_URL, '/') . '/' . ltrim($endpoint, '/');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        if (0 !== $errno) {
            throw new Exception("Error getting API response from $endpoint: #$errno $error");
        }

        $data = json_decode($response,true);
        if (is_null($data)) {
            $preview = substr($response, 0, 200);
            throw new Exception("Error parsing API response from $endpoint: $preview");
        }

        return $data;
    }
}
