<?php
namespace Macopedia\MessDetector\Command;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 *
 */
class L10nParentCommandController extends CommandController
{
    /**
     * Finds all records which has l10n_parent value set to uid of the record in non default language
     *
     * @param string $tableName pass table name if you want to run the check on a single table
     * @return void
     * @cli
     */
    public function checkDefaultLanguageCommand($tableName='')
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

    /**
     * Finds all records which has l10n_parent value set to uid of the record on a different page
     *
     * @param string $tableName pass table name if you want to run the check on a single table
     * @return void
     * @cli
     */
    public function checkPidCommand($tableName='')
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
                    $queryBuilder->expr()->neq($table . '_parent.pid', $table . '.pid')
                )
                ->execute()
                ->fetchAll();
            $this->outputLine('Checking table: ' . $table);
            if (!empty($rows)) {
                $this->outputLine('Following records live on a different page then their translation source transOrigPointerField (l10n_parent)');
                $this->outputLine('Original record and its translation should live on the same page.');
                $this->outputLine('Query run:');
                $this->outputLine($queryBuilder->getSQL());
                $this->output->outputTable($rows, array_keys($rows[0]));
            } else {
                $this->outputLine('All good.');
            }
        }
    }

    /**
     * Finds all records which l10n_parent field value equals uid
     *
     * @param string $tableName pass table name if you want to run the check on a single table
     * @return void
     * @cli
     */
    public function checkUidCommand($tableName='')
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
            $select = ['uid', 'pid', $tca['ctrl']['languageField'], $tca['ctrl']['transOrigPointerField']];
            if (!empty($tca['ctrl']['delete'])) {
                $select[] = $tca['ctrl']['delete'];
            }
            if (!empty($tca['ctrl']['label'])) {
                $select[] = $tca['ctrl']['label'];
            }
            $rows = $queryBuilder
                ->select(...$select)
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq('uid', $tca['ctrl']['transOrigPointerField'])
                )
                ->execute()
                ->fetchAll();
            $this->outputLine('Checking table: ' . $table);
            if (!empty($rows)) {
                $this->outputLine('Following records have transOrigPointerField (l10n_parent) field value equal to uid');
                $this->outputLine('Record should not be itself parent');
                $this->outputLine('Query run:');
                $this->outputLine($queryBuilder->getSQL());
                $this->output->outputTable($rows, array_keys($rows[0]));
            } else {
                $this->outputLine('All good.');
            }
        }
    }
}