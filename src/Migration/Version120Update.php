<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle
 *
 * @copyright  Glen Langer 2020 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @license    LGPL-3.0-or-later
 * @see        https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\BannerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * This migration add a column and set the default value.
 *
 * This became necessary with the changes for https://github.com/BugBuster1701/contao-banner-bundle/issues/49.
 */
class Version120Update extends AbstractMigration
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Create a new instance.
     *
     * @param Connection $connection the database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the name.
     */
    public function getName(): string
    {
        return 'Enable OverwriteMeta';
    }

    /**
     * Must only run if:
     * - the Banner tables are present AND
     * - the column banner_overwriteMeta is not present.
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_banner'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_banner');

        return !isset($columns['banner_overwriteMeta']);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): MigrationResult
    {
        $this->connection->query("
            ALTER TABLE
                tl_banner
            ADD
                banner_overwriteMeta CHAR(1) DEFAULT '' NOT NULL
        ");

        $stmt = $this->connection->prepare("
            UPDATE
                tl_banner
            SET
                overwriteMeta = '1'
            WHERE
                banner_name = '' AND banner_comment = ''
        ");

        $stmt->execute();

        return new MigrationResult(
            true,
            'Overwrite Metadata '.$stmt->rowCount().' x activated.'
        );
    }
}
