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
     * @param string $tableName pass table name if you want to run the check on a single table
     * @return void
     * @cli
     */
    public function checkT3OrigUidEqualsUidCommand($tableName='')
    {
        foreach ($GLOBALS['TCA'] as $table => $tca) {
            if (!empty($tableName) && $table != $tableName) {
                continue;
            }
            if (empty($tca['ctrl']['origUid'])) {
                continue;
            }
            $origUidFieldName = $tca['ctrl']['origUid'];

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            // Remove all default restrictions (delete, hidden, starttime, stoptime)
            $queryBuilder->getRestrictions()
                ->removeAll();
            $select = ['uid', 'pid', $origUidFieldName];
            if (!empty($tca['ctrl']['transOrigPointerField'])) {
                $select[] = $tca['ctrl']['transOrigPointerField'];
            }
            if (!empty($tca['ctrl']['delete'])) {
                $select[] = $tca['ctrl']['delete'];
            }
            if (!empty($tca['ctrl']['languageField'])) {
                $select[] = $tca['ctrl']['languageField'];
            }
            if (!empty($tca['ctrl']['label'])) {
                $select[] = $tca['ctrl']['label'];
            }
            $rows = $queryBuilder
                ->select(...$select)
                ->from($table)
                ->where(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->quoteIdentifier($origUidFieldName))
                    )
                )
                ->execute()
                ->fetchAll();

            $this->outputLine('Checking table: ' . $table);
            if (!empty($rows)) {
                $this->outputLine('Following records have wrong t3_origuid value. t3_origuid should never be equal to uid.');
                $this->outputLine('It should be safe to set t3_origuid for these records to 0.');
                $this->outputLine('This issue might be caused by the workspaces bug, see https://forge.typo3.org/issues/78643');
                $this->outputLine('Query run:');
                $this->outputLine($queryBuilder->getSQL());

                $this->output->outputTable($rows, array_keys($rows[0]));
            } else {
                $this->outputLine('All good.');
            }
        }
    }

    /**
     * Finds all records which has l10n_parent value set to uid of the record in non default language
     *
     * @param string $tableName pass table name if you want to run the check on a single table
     * @return void
     * @cli
     */
    public function checkL10nParentCommand($tableName='')
    {
        foreach ($GLOBALS['TCA'] as $table => $tca) {
            if (!empty($tableName) && $table != $tableName) {
                continue;
            }
            if (empty($tca['ctrl']['transOrigPointerField'])
                || !empty($tca['ctrl']['transOrigPointerTable'])) {
                continue;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            // Remove all default restrictions (delete, hidden, starttime, stoptime)
            $queryBuilder->getRestrictions()
                ->removeAll();
            $select = [$table . '.uid', $table . '.pid', $table . '.' . $tca['ctrl']['languageField'], $table . '.' . $tca['ctrl']['transOrigPointerField']];
            if (!empty($tca['ctrl']['delete'])) {
                $select[] = $table . '.' . $tca['ctrl']['delete'];
            }
            if (!empty($tca['ctrl']['label'])) {
                $select[] = $table . '.' . $tca['ctrl']['label'];
            }
            $rows = $queryBuilder
                ->select(...$select)
                ->from($table)
                ->leftJoin(
                    $table,
                    $table,
                    $table . '_parent',
                    $queryBuilder->expr()->eq(
                        $table . '.' . $tca['ctrl']['transOrigPointerField'],
                        $queryBuilder->quoteIdentifier($table . '_parent.uid')
                    )
                )
                ->where(
                    $queryBuilder->expr()->neq($table . '_parent.' . $tca['ctrl']['languageField'], 0)
                )
                ->execute()
                ->fetchAll();
            $this->outputLine('Checking table: ' . $table);
            if (!empty($rows)) {
                $this->outputLine('Following records have wrong transOrigPointerField (l10n_parent) value.');
                $this->outputLine('transOrigPointerField field should always point to a record in the default language (0)');
                $this->outputLine('Query run:');
                $this->outputLine($queryBuilder->getSQL());
                $this->output->outputTable($rows, array_keys($rows[0]));
            } else {
                $this->outputLine('All good.');
            }
        }
    }
}