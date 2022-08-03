<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\Command\ZddMigrations;

use Akeneo\Pim\Enrichment\Component\Product\Model\Product;
use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use Doctrine\DBAL\Connection;

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class V20220729171405CleanVersioningResourceUuidColumnForNonProductVersionsIntegration extends TestCase
{
    private const PRODUCT_VERSION_ID = 1;
    private const NON_PRODUCT_VERSION_ID = 2;

    private Connection $connection;
    private V20220729171405CleanVersioningResourceUuidColumnForNonProductVersions $migrationToTest;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->get('database_connection');
        $this->migrationToTest = $this->get(V20220729171405CleanVersioningResourceUuidColumnForNonProductVersions::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->connection->executeStatement(
            sprintf(
                'DROP TRIGGER IF EXISTS %s;',
                V20220729171405CleanVersioningResourceUuidColumnForNonProductVersions::BLOCKING_TRIGGER_NAME
            )
        );
    }

    public function test_it_empties_the_resource_uuid_when_the_version_is_not_product_related()
    {
        $this->createProductRelatedVersionWithResourceUuid();
        $this->createNonProductRelatedVersionWithResourceUuid();

        $this->migrationToTest->migrate();

        $this->assertProductRelatedVersionHasResourceUuid();
        $this->assertNonProductRelatedVersionHasNoResourceUuid();
    }

    public function test_it_throws_if_the_migration_that_removes_triggers_has_not_run()
    {
        $this->givenTheMigrationThatRemovesTriggerHasNotRun();

        $this->expectException(\LogicException::class);
        $this->migrationToTest->migrate();
    }

    protected function getConfiguration(): Configuration
    {
        return $this->catalog->useMinimalCatalog();
    }

    private function createProductRelatedVersionWithResourceUuid(): void
    {
        $sql = <<<SQL
INSERT INTO `pim_versioning_version` (`id`, `author`, `resource_name`, `resource_id`, `resource_uuid`, `snapshot`, `changeset`, `context`, `version`, `logged_at`, `pending`)
VALUES
	(:version_id, 'system', :resource_name, NULL, 'A_UUID', 'a:11:{s:6:\"family\";s:16:\"multifunctionals\";s:6:\"groups\";s:0:\"\";s:10:\"categories\";s:41:\"lexmark,multifunctionals,print_scan_sales\";s:6:\"parent\";s:0:\"\";s:14:\"color_scanning\";s:1:\"0\";s:23:\"description-en_US-print\";s:1477:\"<b>Streamlined Reliability</b>\\nA smart, reliable option for bringing duplex printing, copying, scanning and high-speed faxing into one machine, with up to 40 ppm and the ability to scan documents straight to e-mail or a flash drive.\\n\\n<b>As Easy as It Gets</b>\\nRight out of the box, you’ll power through tasks at exceptionally fast speeds—up to 40 ppm. It’s a breeze to set up, install, and start enjoying the benefit of doing all those multiple tasks on one user-friendly machine.\\n\\n<b>Smarter Printer, Smarter Business</b>\\nThe large LCD color touch screen offers amazingly simple access to a rich range of features, including duplex scanning, advanced copying and easy user authorization for enhanced security. You can even customize the touch screen to meet your workgroup’s specific needs.\\n\\n<b>Small in Size, Huge on Features</b>\\nEnjoy an intelligent, efficient combination of built-in features like duplex printing, copying and scanning plus a front Direct USB port. Gives you the ability to scan to multiple destinations, letting your workgroup breeze through intense workloads.\\n\\n<b>Save the Earth and Money</b>\\nLower your cost per page while helping conserve resources with up to 9,000*-page or 15,000*-page replacement cartridges. Add that to the automatic duplex printing and the energy savings of consolidating to one smart device, and you’re taking big steps toward an eco-conscious workplace. (*Declared yield in accordance with ISO/IEC 19752.)\";s:18:\"maximum_print_size\";s:19:\"legal_216_x_356_mm_\";s:4:\"name\";s:14:\"Lexmark X464de\";s:22:\"release_date-ecommerce\";s:25:\"2012-04-20T00:00:00+00:00\";s:3:\"sku\";s:8:\"13871461\";s:7:\"enabled\";i:1;}', 'a:9:{s:6:\"family\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:16:\"multifunctionals\";}s:10:\"categories\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:41:\"lexmark,multifunctionals,print_scan_sales\";}s:14:\"color_scanning\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:1:\"0\";}s:23:\"description-en_US-print\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:1477:\"<b>Streamlined Reliability</b>\\nA smart, reliable option for bringing duplex printing, copying, scanning and high-speed faxing into one machine, with up to 40 ppm and the ability to scan documents straight to e-mail or a flash drive.\\n\\n<b>As Easy as It Gets</b>\\nRight out of the box, you’ll power through tasks at exceptionally fast speeds—up to 40 ppm. It’s a breeze to set up, install, and start enjoying the benefit of doing all those multiple tasks on one user-friendly machine.\\n\\n<b>Smarter Printer, Smarter Business</b>\\nThe large LCD color touch screen offers amazingly simple access to a rich range of features, including duplex scanning, advanced copying and easy user authorization for enhanced security. You can even customize the touch screen to meet your workgroup’s specific needs.\\n\\n<b>Small in Size, Huge on Features</b>\\nEnjoy an intelligent, efficient combination of built-in features like duplex printing, copying and scanning plus a front Direct USB port. Gives you the ability to scan to multiple destinations, letting your workgroup breeze through intense workloads.\\n\\n<b>Save the Earth and Money</b>\\nLower your cost per page while helping conserve resources with up to 9,000*-page or 15,000*-page replacement cartridges. Add that to the automatic duplex printing and the energy savings of consolidating to one smart device, and you’re taking big steps toward an eco-conscious workplace. (*Declared yield in accordance with ISO/IEC 19752.)\";}s:18:\"maximum_print_size\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:19:\"legal_216_x_356_mm_\";}s:4:\"name\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:14:\"Lexmark X464de\";}s:22:\"release_date-ecommerce\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:25:\"2012-04-20T00:00:00+00:00\";}s:3:\"sku\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:8:\"13871461\";}s:7:\"enabled\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";i:1;}}', NULL, 1, '2022-07-27 16:20:49', 0);
SQL;
        $this->connection->executeStatement($sql, ['version_id' => self::PRODUCT_VERSION_ID, 'resource_name' => Product::class]);
    }

    private function createNonProductRelatedVersionWithResourceUuid(): void
    {
        $sql = <<<SQL
INSERT INTO `pim_versioning_version` (`id`, `author`, `resource_name`, `resource_id`, `resource_uuid`, `snapshot`, `changeset`, `context`, `version`, `logged_at`, `pending`)
VALUES
	(:version_id, 'system', 'Akeneo\\Channel\\Infrastructure\\Component\\Model\\Locale', '6', 'A_UUID', 'a:3:{s:4:\"code\";s:5:\"ar_EG\";s:15:\"view_permission\";s:0:\"\";s:15:\"edit_permission\";s:0:\"\";}', 'a:1:{s:4:\"code\";a:2:{s:3:\"old\";s:0:\"\";s:3:\"new\";s:5:\"ar_EG\";}}', NULL, 1, '2022-07-27 16:20:26', 0);
SQL;
        $this->connection->executeStatement($sql, ['version_id' => self::NON_PRODUCT_VERSION_ID]);
    }

    private function assertProductRelatedVersionHasResourceUuid(): void
    {
        $result = $this->fetchResourceIdForVersion(self::PRODUCT_VERSION_ID);
        $this->assertNotNull($result);
    }

    private function assertNonProductRelatedVersionHasNoResourceUuid(): void
    {
        $result = $this->fetchResourceIdForVersion(self::NON_PRODUCT_VERSION_ID);
        $this->assertNull($result);
    }

    private function fetchResourceIdForVersion(int $versionId): ?string
    {
        $stmt = $this->connection->executeQuery(
            'SELECT resource_uuid FROM pim_versioning_version WHERE id = :version_id',
            ['version_id' => $versionId]
        );

        return $stmt->fetchOne();
    }

    private function givenTheMigrationThatRemovesTriggerHasNotRun(): void
    {
        $createDummyTriggerQuery = <<<SQL
CREATE TRIGGER {trigger_name}
BEFORE INSERT ON pim_catalog_category_product FOR EACH ROW
BEGIN
IF NEW.product_uuid IS NULL THEN SET NEW.category_id = 1;
END IF;
END
SQL;
        $query = \strtr(
            $createDummyTriggerQuery,
            ['{trigger_name}' => V20220729171405CleanVersioningResourceUuidColumnForNonProductVersions::BLOCKING_TRIGGER_NAME]
        );
        $this->connection->executeStatement($query);
    }
}
