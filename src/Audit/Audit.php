<?php

namespace Utopia\Audit;

use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Exception;

class Audit
{
    const COLLECTION = "audit";

    /**
     * @var Database
     */
    private $db;

    /**
     * @param Database $adapter
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function setup(): void
    {
        if (!$this->db->exists()) {
            throw new Exception("You need to create the databse before running Audit setup");
        }

        $attributes = [
            new Document([
                '$id' => 'userId',
                'type' => Database::VAR_STRING,
                'size' => Database::LENGTH_KEY,
                'required' => true,
                'signed' => true,
                'array' => false,
                'filters' => [],
            ]),
            new Document([
                '$id' => 'event',
                'type' => Database::VAR_STRING,
                'size' => 255,
                'required' => true,
                'signed' => true,
                'array' => false,
                'filters' => [],
            ]),
            new Document([
                '$id' => 'resource',
                'type' => Database::VAR_STRING,
                'size' => 255,
                'required' => false,
                'signed' => true,
                'array' => false,
                'filters' => [],
            ]),
            new Document([
                '$id' => 'userAgent',
                'type' => Database::VAR_STRING,
                'size' => 65534,
                'required' => true,
                'signed' => true,
                'array' => false,
                'filters' => [],
            ]),
            new Document([
                '$id' => 'ip',
                'type' => Database::VAR_STRING,
                'size' => 45,
                'required' => true,
                'signed' => true,
                'array' => false,
                'filters' => [],
            ]),
            new Document([
                '$id' => 'location',
                'type' => Database::VAR_STRING,
                'size' => 45,
                'required' => false,
                'signed' => true,
                'array' => false,
                'filters' => [],
            ]),
            new Document([
                '$id' => 'time',
                'type' => Database::VAR_INTEGER,
                'size' => 0,
                'required' => false,
                'signed' => true,
                'array' => false,
                'filters' => [],
            ]),
            new Document([
                '$id' => 'data',
                'type' => Database::VAR_STRING,
                'size' => 16777216,
                'required' => false,
                'signed' => true,
                'array' => false,
                'filters' => ['json'],
            ]),
        ];

        $indexes = [
            new Document([
                '$id' => 'index1',
                'type' => Database::INDEX_KEY,
                'attributes' => ['userId'],
                'lengths' => [],
                'orders' => [],
            ]),
            new Document([
                '$id' => 'index2',
                'type' => Database::INDEX_KEY,
                'attributes' => ['event'],
                'lengths' => [],
                'orders' => [],
            ]),
            new Document([
                '$id' => 'index3',
                'type' => Database::INDEX_KEY,
                'attributes' => ['resource'],
                'lengths' => [],
                'orders' => [],
            ]),
            new Document([
                '$id' => 'index4',
                'type' => Database::INDEX_KEY,
                'attributes' => ['userId', 'event'],
                'lengths' => [],
                'orders' => [],
            ]),
            new Document([
                '$id' => 'index5',
                'type' => Database::INDEX_KEY,
                'attributes' => ['resource', 'event'],
                'lengths' => [],
                'orders' => [],
            ]),
        ];

        $this->db->createCollection(Audit::COLLECTION, $attributes, $indexes);

    }

    /**
     * Log.
     *
     * Add specific event log
     *
     * @param string $userId
     * @param string $event
     * @param string $resource
     * @param string $userAgent
     * @param string $ip
     * @param string $location
     * @param array  $data
     *
     * @return bool
     */
    public function log(string $userId, string $event, string $resource, string $userAgent, string $ip, string $location, array $data = []): bool
    {
        Authorization::skip(function () use ($userId, $event, $resource, $userAgent, $ip, $location, $data) {
            $this->db->createDocument(Audit::COLLECTION, new Document([
                '$read' => [],
                '$write' => [],
                'userId' => $userId,
                'event' => $event,
                'resource' => $resource,
                'userAgent' => $userAgent,
                'ip' => $ip,
                'location' => $location,
                'data' => $data,
                'time' => \time(),
            ]));
        });
        return true;
    }

    /**
     * Get All Logs By User ID.
     *
     * @param string $userId
     * @param int $limit
     * @param int $offset
     * @param Document|null $orderAfter
     *
     * @return array
     */
    public function getLogsByUser(string $userId, int $limit = 25, int $offset = 0, Document $orderAfter = null): array
    {
        $result = Authorization::skip(function () use ($userId, $limit, $offset, $orderAfter) {
            return $this->db->find(Audit::COLLECTION, $this->getQueryByUser($userId), $limit, $offset, [], ['DESC'], $orderAfter);
        });
        return $result;
    }

    /**
     * Get Logs Count By User ID.
     *
     * @param string $userId
     *
     * @return int
     */
    public function getLogsByUserCount(string $userId): int
    {
        $result = Authorization::skip(function () use ($userId) {
            return $this->db->count(Audit::COLLECTION, $this->getQueryByUser($userId));
        });
        return $result;
    }

    /**
     * Get Query to get Logs by User ID.
     *
     * @param string $userId
     *
     * @return array
     */
    private function getQueryByUser(string $userId): array
    {
        return [
            new Query('userId', Query::TYPE_EQUAL, [$userId]),
        ];
    }

    /**
     * Get All Logs By Resource.
     *
     * @param string $resource
     * @param int $limit
     * @param int $offset
     * @param Document|null $orderAfter
     *
     * @return array
     */
    public function getLogsByResource(string $resource, int $limit = 25, int $offset = 0, Document $orderAfter = null): array
    {
        $results = Authorization::skip(function () use ($resource, $limit, $offset, $orderAfter) {
            return $this->db->find(Audit::COLLECTION, $this->getQueryByResource($resource), $limit, $offset, [], ['DESC'], $orderAfter);
        });
        return $results;
    }

    /**
     * Get Logs Count By Resource.
     *
     * @param string $resource
     *
     * @return int
     */
    public function getLogsByResourceCount(string $resource): int
    {
        $results = Authorization::skip(function () use ($resource) {
            return $this->db->count(Audit::COLLECTION, $this->getQueryByResource($resource));
        });
        return $results;
    }

    /**
     * Get Query to get Logs by Resource.
     *
     * @param string $resource
     *
     * @return array
     */
    private function getQueryByResource(string $resource): array
    {
        return [
            new Query('resource', Query::TYPE_EQUAL, [$resource]),
        ];
    }

    /**
     * Get All Logs By User and Events.
     *
     * @param string $userId
     * @param array $events
     * @param int $limit
     * @param int $offset
     * @param Document|null $orderAfter
     *
     * @return array
     */
    public function getLogsByUserAndEvents(string $userId, array $events, int $limit = 25, int $offset = 0, Document $orderAfter = null): array
    {
        $results = Authorization::skip(function () use ($userId, $events, $limit, $offset, $orderAfter) {
            return $this->db->find(Audit::COLLECTION, $this->getQueryByUserAndEvents($userId, $events), $limit, $offset, [], ['DESC'], $orderAfter);
        });
        return $results;
    }

    /**
     * Get Logs Count By User and Events.
     *
     * @param string $userId
     * @param array $events
     *
     * @return int
     */
    public function getLogsByUserAndEventsCount(string $userId, array $events): int
    {
        $results = Authorization::skip(function () use ($userId, $events) {
            return $this->db->count(Audit::COLLECTION, $this->getQueryByUserAndEvents($userId, $events));
        });
        return $results;
    }

    /**
     * Get Query to get Logs by User and Events.
     *
     * @param string $userId
     * @param array $events
     *
     * @return array
     */
    private function getQueryByUserAndEvents(string $userId, array $events): array
    {
        return [
            new Query('userId', Query::TYPE_EQUAL, [$userId]),
            new Query('event', Query::TYPE_EQUAL, $events),
        ];
    }

    /**
     * Get All Logs By Resource and Events.
     *
     * @param string $resource
     * @param array $events
     * @param int $limit
     * @param int $offset
     * @param Document|null $orderAfter
     *
     * @return array
     */
    public function getLogsByResourceAndEvents(string $resource, array $events, int $limit = 25, int $offset = 0, Document $orderAfter = null): array
    {
        $results = Authorization::skip(function () use ($resource, $events, $limit, $offset, $orderAfter) {
            return $this->db->find(Audit::COLLECTION, $this->getQueryByResourceAndEvents($resource, $events), $limit, $offset, [], ['DESC'], $orderAfter);
        });
        return $results;
    }

    /**
     * Get All Logs By Resource and Events.
     *
     * @param string $resource
     * @param array $events
     *
     * @return int
     */
    public function getLogsByResourceAndEventsCount(string $resource, array $events): int
    {
        $results = Authorization::skip(function () use ($resource, $events) {
            return $this->db->count(Audit::COLLECTION, $this->getQueryByResourceAndEvents($resource, $events));
        });
        return $results;
    }

    /**
     * Get Query to get Logs by Resource and Events.
     *
     * @param string $resource
     * @param array $events
     *
     * @return array
     */
    private function getQueryByResourceAndEvents(string $resource, array $events): array
    {
        return [
            new Query('resource', Query::TYPE_EQUAL, [$resource]),
            new Query('event', Query::TYPE_EQUAL, $events),
        ];
    }

    /**
     * Delete all logs older than $timestamp seconds
     *
     * @param int $timestamp
     *
     * @return bool
     */
    public function cleanup(int $timestamp): bool
    {
        Authorization::skip(function () use ($timestamp) {
            do {
                $documents = $this->db->find(Audit::COLLECTION, [
                    new Query('time', Query::TYPE_LESSER, [$timestamp]),
                ]);

                foreach ($documents as $document) {
                    $this->db->deleteDocument(Audit::COLLECTION, $document['$id']);
                }
            } while (!empty($documents));
        });
        return true;
    }
}
