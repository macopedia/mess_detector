<?php
namespace Macopedia\MessDetector\Command;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 *
 */
class DatabaseIntegrityCommandController extends CommandController
{
    /**
     * Finds all records which has wrong t3_origuid value set
     *
     * @return void
     * @cli
     */
    public function checkT3OrigUidCommand()
    {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        // Remove all default restrictions (delete, hidden, starttime, stoptime), but add DeletedRestriction again
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'pid', 'l18n_parent', 't3_origuid', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->quoteIdentifier('t3_origuid'))
                )
            )
            ->execute()
            ->fetchAll();
        if (!empty($rows)) {
            $this->outputLine('Following records have wrong t3_origuid value. t3_origuid should never be equal to uid');
            $this->outputLine('Query run:');
            $this->outputLine($queryBuilder->getSQL());

            $this->output->outputTable($rows, array_keys($rows[0]));
        } else {
            $this->outputLine('All good.');
        }
    }

    /**
     * Finds all records which has wrong t3_origuid value set
     *
     * @return void
     * @cli
     */
    public function checkL10nParentCommand()
    {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        // Remove all default restrictions (delete, hidden, starttime, stoptime), but add DeletedRestriction again
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rows = $queryBuilder
            ->select('tt_content.uid', 'tt_content.pid', 'tt_content.l18n_parent', 'tt_content.t3_origuid', 'tt_content.header')
            ->from('tt_content')
            ->leftJoin(
                'tt_content',
                'tt_content',
                'tt_content_parent',
                $queryBuilder->expr()->eq(
                    'tt_content.l18n_parent',
                     $queryBuilder->quoteIdentifier('tt_content_parent.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->neq('tt_content_parent.sys_language_uid', 0)
            )
            ->execute()
            ->fetchAll();
        if (!empty($rows)) {
            $this->outputLine('Following records have wrong l18n_parent value.');
            $this->outputLine('Query run:');
            $this->outputLine($queryBuilder->getSQL());
            $this->output->outputTable($rows, array_keys($rows[0]));
        } else {
            $this->outputLine('All good.');
        }
    }
}