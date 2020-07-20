<?php

namespace Test\testObjects;

use \SypherLev\Blueprint\QueryBuilders\SourceInterface;
use \SypherLev\Blueprint\QueryBuilders\QueryInterface;

class BlueprintMock extends \SypherLev\Blueprint\Blueprint
{
    public function __construct(SourceInterface $source, QueryInterface $query)
    {
        parent::__construct($source, $query);

        $this->addPattern('whole', function () {
            return (new \SypherLev\Blueprint\Elements\Pattern())
                ->table('mockTable')
                ->join('mockTable', 'joinTable', ['id' => 'join_id'], 'LEFT')
                ->columns([
                    'mockTable' => ['*'],
                    'joinTable' => ['firstcolumn', 'secondcolumn']
                ])
                ->aggregate('sum', ['col2'])
                ->groupBy(['col1']);
        });

        $this->addPattern('insert', function () {
            return (new \SypherLev\Blueprint\Elements\Pattern())
                ->table('mockTable')
                ->columns([
                    'created', 'col1', 'col2', 'current_index'
                ]);
        });

        $this->addPattern('update', function () {
            return (new \SypherLev\Blueprint\Elements\Pattern())
                ->table('mockTable')
                ->columns([
                    'col1', 'col2', 'current_index'
                ]);
        });

        $this->addFilter('activeOnly', function () {
            return (new \SypherLev\Blueprint\Elements\Filter())
                ->where(['created > ' => time() - 86000])
                ->orderBy(['created'], 'DESC', true)
                ->limit(10, 50);
        });

        $this->addTransformation('createdTimestampToString', function ($record) {
            if (isset($record->created)) {
                $record->created = date('Y-m-d', $record->created);
            }
            return $record;
        });

        $this->addTransformation('arrayTransformation', function ($records) {
            foreach ($records as $idx => $r) {
                $records[$idx]->current_index = $idx;
            }
            return $records;
        }, true);

        $this->addTransformation('arrayInsertTransformation', function ($records) {
            foreach ($records as $idx => $r) {
                $records[$idx]['current_index'] = $idx;
            }
            return $records;
        }, true);

        $this->addTransformation('singleStringToTimestamp', function ($record) {
            $record['current_index'] = 0;
            return $record;
        });

        $this->addTransformation('createdStringToTimestamp', function (Array $records) {
            foreach ($records as $idx => $record) {
                if (isset($record['created'])) {
                    $records[$idx]['created'] = strtotime($record['created']);
                }
            }
            return $records;
        }, true);
    }

    public function getMany()
    {
        return $this->select()
            ->withPattern('whole')
            ->withTransformation('createdTimestampToString')
            ->orderBy(['col1'])
            ->limit(5)
            ->many();
    }

    public function getSingle()
    {
        return $this->select()
            ->withPattern('whole')
            ->where(['mockTable' => ['id' => 1]])
            ->withTransformation('createdTimestampToString')
            ->withTransformation('arrayTransformation')
            ->one();
    }

    public function getWithoutColumns() {
        $this->select()
            ->table('mockTable')
            ->where(['mockTable' => ['id' => 1]]);
        return $this->getCurrentSQL();
    }

    public function getInArray() {
        $this->select()
            ->table('mockTable')
            ->where(['id IN' => [1,2,3]]);
        return $this->getCurrentSQL();
    }

    public function getOnlyAggregates() {
        $this->select()
            ->table('mockTable')
            ->aggregate('sum', ['mockTable' => ['firstcolumn']])
            ->groupBy(['mockTable' => ['id']]);
        return $this->getCurrentSQL();
    }

    public function getWithFilter()
    {
        return $this->select()
            ->withPattern('whole')
            ->withFilter('activeOnly')
            ->withTransformation('arrayTransformation')
            ->many();
    }

    public function insertRecord($record)
    {
        return $this->insert()
            ->withPattern('insert')
            ->withTransformation('arrayInsertTransformation')
            ->add($record)
            ->execute();
    }

    public function testSelectQuery()
    {
        $this->select()
            ->join('mockTable', 'joinTable', ['id' => 'join_id'], 'LEFT')
            ->columns([
                'mockTable' => ['*'],
                'joinTable' => ['alias1' => 'firstcolumn', 'alias2' => 'secondcolumn']
            ])
            ->aggregate('sum', ['alias' => 'col2'])
            ->table('mockTable')
            ->where(['mockTable' => ['id >' => 0]])
            ->groupBy(['mockTable' => 'col1'])
            ->orderBy(['mockTable' => ['id']], 'DESC')
            ->limit(5);
        return $this->getCurrentSQL();
    }

    public function testSelectBindings()
    {
        $this->select()
            ->withPattern('whole')
            ->aggregate('sum', ['alias' => 'col2'])
            ->where(['mockTable' => ['id >' => 0]])
            ->orderBy(['mockTable' => 'id'])
            ->groupBy(['col1'])
            ->limit(5);
        return $this->getCurrentBindings();
    }

    public function testInsertBindings($record)
    {
        $this->insert()
            ->withPattern('insert')
            ->add($record);
        return $this->getCurrentBindings();
    }

    public function testUpdateBindings($id, $record)
    {
        $this->update()
            ->withPattern('update')
            ->set($record)
            ->where(['id' => $id]);
        return $this->getCurrentBindings();
    }

    public function testInsertQuery($record)
    {
        $this->record();
        $this->insert()
            ->withPattern('insert')
            ->add($record)
            ->withTransformation('createdStringToTimestamp')
            ->withTransformation('singleStringToTimestamp')
            ->execute();
        $this->stop();
        return $this->output();
    }

    public function testUpdateQuery($id, $record)
    {
        $this->source->startRecording();
        $this->update()
            ->table('mockTable')
            ->columns([
                'created', 'col1', 'col2', 'current_index'
            ])
            ->set($record)
            ->where(['id' => $id])
            ->withTransformation('createdStringToTimestamp')
            ->withTransformation('singleStringToTimestamp')
            ->execute();
        $this->source->stopRecording();
        return $this->source->getRecordedOutput();
    }

    public function testDeleteQuery($id)
    {
        $this->source->startRecording();
        $this->delete()
            ->table('mockTable')
            ->where(['id' => $id])
            ->execute();
        $this->source->stopRecording();
        return $this->source->getRecordedOutput();
    }

    public function testCountQuery()
    {
        $this->source->startRecording();
        $this->select()
            ->table('mockTable')
            ->where(['id >' => 1])
            ->count();
        $this->source->stopRecording();
        return $this->source->getRecordedOutput();
    }

    public function testPatternException()
    {
        try {
            $this->select()
                ->withPattern('fake')
                ->many();
            return null;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function testFilterException()
    {
        try {
            $this->select()
                ->withFilter('fake')
                ->many();
            return null;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function testTransformationException()
    {
        try {
            $this->select()
                ->withTransformation('fake')
                ->many();
            return null;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function testPatternAddingException() {
        try {
            $this->addPattern('fake', function(){
                return new \stdClass();
            });
            return null;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function testFilterAddingException() {
        try {
            $this->addFilter('fake', function(){
                return new \stdClass();
            });
            return null;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function testCountBadResult() {
        try {
            $this->select()->count();
            return null;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function testReverseOrderTableSetting() {
        $this->select()
            ->columns(['one', 'two', 'three'])
            ->table('mockTable');
        return $this->getCurrentSQL();
    }

    public function testGetBindings() {
        $this->whitelistColumn(['id']);
        $this->select()
            ->table('mockTable')
            ->where(['id >' => 1]);
        return $this->getCurrentBindings();
    }

    public function testGetColumns() {
        return $this->getTableColumns('mockTable');
    }

    public function testGetPrimaryKey() {
        return $this->getPrimaryKey('mockTable');
    }

    public function testWhitelists() {
        $this->whitelistColumn(['mockColumn']);
        $this->whitelistTable(['mockTable']);
    }
}