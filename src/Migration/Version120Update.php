<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2025 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
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
        return 'Banner Bundle Version120Update Enable OverwriteMeta';
    }

    /**
     * Must only run if:
     * - the Banner tables are present AND
     * - the column banner_overwritemeta is not present.
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_banner'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_banner');

        return !isset($columns['banner_overwritemeta']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeQuery("
            ALTER TABLE
                tl_banner
            ADD
                banner_overwritemeta CHAR(1) DEFAULT '' NOT NULL
        ");

        $stmt = $this->connection->prepare("
            UPDATE
                tl_banner
            SET
                banner_overwritemeta = '1'
            WHERE
                banner_name = '' AND ( banner_comment = '' OR banner_comment is NULL )
        ");

        $result = $stmt->executeQuery();

        return new MigrationResult(
            true,
            'Overwrite Metadata '.$result->rowCount().' x activated. (Banner Bundle)',
        );
    }
}
