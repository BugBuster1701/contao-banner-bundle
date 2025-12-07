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
use Contao\Image\ResizeConfiguration;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * This migration change the value of a column from proportinal to box.
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

        return (bool) $this->connection->fetchOne("SELECT TRUE FROM tl_banner WHERE banner_imgSize LIKE '%proportional%' LIMIT 1");
    }

    public function run(): MigrationResult
    {
        $rows = $this->connection->fetchAllAssociative('SELECT
                    id,
                    banner_imgSize
                FROM
                    tl_banner
                WHERE
                    banner_imgSize LIKE \'%proportional%\'');

        foreach ($rows as $row) {
            $oldSize = StringUtil::deserialize($row['banner_imgSize'], true);
            // do not change if not proportinal
            if ('proportional' !== ($oldSize[2] ?? null)) {
                continue;
            }
            $oldSize[2] = ResizeConfiguration::MODE_BOX;

            $result = $this->connection->update('tl_banner', ['banner_imgSize' => serialize($oldSize)], ['id' => $row['id']]);
        }

        return new MigrationResult(
            true,
            'Change Image Size to box '.$result.' x activated. (Banner Bundle)',
        );
    }
}
