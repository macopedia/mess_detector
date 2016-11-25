<?php
namespace Macopedia\MessDetector\Command;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\DataSet;

/**
 *
 */
class FalCommandController extends CommandController
{
    /**
     * Finds all sys_file_references which have wrong sys_language_uid
     *
     * @return void
     * @cli
     */
    public function fileReferenceLanguageCommand()
    {
        $allowedTables = ['tt_content'];
        foreach ($allowedTables as $table) {
            $tca = $GLOBALS['TCA'][$table];
            if (empty($tca['ctrl']['languageField'])) {
                continue;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->getRestrictions()
                ->removeAll();
            $select = [
                'sys_file_reference.uid',
                'sys_file_reference.pid',
                'sys_file_reference.sys_language_uid',
                'sys_file_reference.l10n_parent',
                'sys_file_reference.title',
                'sys_file_reference.uid_local',
                'sys_file_reference.uid_foreign',
                'parent_table.' . $tca['ctrl']['languageField']
            ];
            $rows = $queryBuilder
                ->select(...$select)
                ->from('sys_file_reference')
                ->leftJoin(
                    'sys_file_reference',
                    $table,
                    'parent_table',
                    $queryBuilder->expr()->eq(
                        'parent_table.uid',
                        $queryBuilder->quoteIdentifier('sys_file_reference.uid_foreign')
                    )
                )
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('sys_file_reference.tablenames', $queryBuilder->quote($table)),
                        $queryBuilder->expr()->neq('sys_file_reference.sys_language_uid', 'parent_table.' . $tca['ctrl']['languageField'])
                    )
                )
                ->execute()
                ->fetchAll();
            $this->outputLine('Checking records from sys_file_reference related to table: ' . $table);
            if (!empty($rows)) {
                $this->outputLine('Following sys_file_reference\'s have different sys_language_uid set then the parent record they are related to.');
                $this->outputLine('Related issues: https://forge.typo3.org/issues/76048');
                $this->outputLine('Query run:');
                $this->outputLine($queryBuilder->getSQL());
                $this->output->outputTable($rows, array_keys($rows[0]));
            } else {
                $this->outputLine('All good.');
                $this->outputLine($queryBuilder->getSQL());
            }
        }
    }
}