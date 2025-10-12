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
use Contao\StringUtil;
use Contao\Image\ResizeConfiguration;
use Doctrine\DBAL\Connection;

/**
 * This migration change the value of a column from proportinal to box
 *
 * This became necessary for https://github.com/BugBuster1701/contao-banner-bundle/issues/102
 */
class Version183Update extends AbstractMigration
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
        return 'Banner Bundle Version183Update Change Image Size from proportional to box';
    }

    /**
     * Must only run if:
     * - the Banner tables are present AND
     * - the column banner_overwritemeta is banner_imgSize present AND
     * - the column banner_imgSize has a part with the value 'proportional'
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_banner'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_banner');
        
        if (!isset($columns['banner_imgsize'])) {
            return false;
        }
        $stmt = $this->connection->prepare("
            SELECT
                banner_imgSize
            FROM
                tl_banner
            WHERE
                banner_imgSize LIKE '%proportional%'
            LIMIT 1
        ");
        $result = $stmt->executeQuery();
        if ($result->rowCount() < 1) {
            return false;
        }

        return true;
    }

    public function run(): MigrationResult
    {
        $stmt = $this->connection->prepare("
            SELECT
                id,
                banner_imgSize
            FROM
                tl_banner
            WHERE
                banner_imgSize LIKE '%proportional%'
        ");
        $result = $stmt->executeQuery();
        
        while ($row = $result->fetchAssociative()) {
            $oldSize = StringUtil::deserialize($row['banner_imgSize']);
            // do not change if not proportinal
            if ('proportional' !== $oldSize[2]) {
                continue;
            }
            $oldSize[2] = ResizeConfiguration::MODE_BOX;
            $newSize = serialize($oldSize);
            $updateStmt = $this->connection->prepare("
                UPDATE
                    tl_banner
                SET
                    banner_imgSize = ?
                WHERE
                    id = ?
            ");
            $result = $updateStmt->executeStatement([$newSize, $row['id']]);
        }
     
        return new MigrationResult(
            true,
            'Change Image Size to box '.$result->rowCount().' x activated. (Banner Bundle)',
        );
    }
}
