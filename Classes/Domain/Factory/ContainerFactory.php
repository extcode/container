<?php

namespace B13\Container\Domain\Factory;

use B13\Container\Domain\Model\Container;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContainerFactory implements SingletonInterface
{

    /**
     * @var Database
     */
    protected $database;

    /**
     * ContainerFactory constructor.
     * @param Database|null $database
     */
    public function __construct(Database $database = null)
    {
        $this->database = $database ?? GeneralUtility::makeInstance(Database::class);
    }

    /**
     * @param int $uid
     * @return Container
     */
    public function buildContainer(int $uid): Container
    {
        $record = $this->database->fetchOneRecord($uid);
        if ($record === null) {
            throw new Exception('cannot fetch record whith uid ' . $uid, 1576572850);
        }
        $defaultRecord = null;
        if ($record['sys_language_uid'] > 0) {
            $defaultRecord = $this->database->fetchOneDefaultRecord($record);
            if ($defaultRecord === null) {
                // free mode
                $childRecords = $this->database->fetchRecordsByParentAndLanguage($record['uid'], $record['sys_language_uid']);
            } else {
                // connected mode
                $childRecords = $this->database->fetchRecordsByParentAndLanguage($defaultRecord['uid'], 0);
                $childRecords = $this->database->fetchOverlayRecords($childRecords, $record['sys_language_uid']);
            }
        } else {
            $childRecords = $this->database->fetchRecordsByParentAndLanguage($record['uid'], $record['sys_language_uid']);
        }
        $childRecordByColPosKey = $this->recordsByColPosKey($childRecords);
        if ($defaultRecord === null) {
            $container = new Container($record, $childRecordByColPosKey);
        } else {
            $container = new Container($defaultRecord, $childRecordByColPosKey);
        }
        return $container;
    }

    /**
     * @param array $records
     * @return array
     */
    protected function recordsByColPosKey(array $records): array
    {
        $recordsByColPosKey = [];
        foreach ($records as $record) {
            if (empty($recordsByColPosKey[$record['colPos']])) {
                $recordsByColPosKey[$record['colPos']] = [];
            }
            $recordsByColPosKey[$record['colPos']][] = $record;
        }
        return $recordsByColPosKey;
    }

}
