<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Test\Integration\Infrastructure\Validation;

use Akeneo\Catalogs\Infrastructure\Validation\ProductMappingSchema;
use Akeneo\Catalogs\Test\Integration\IntegrationTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @covers \Akeneo\Catalogs\Infrastructure\Validation\ProductMappingSchema
 * @covers \Akeneo\Catalogs\Infrastructure\Validation\ProductMappingSchemaValidator
 */
class ProductMappingSchemaValidatorTest extends IntegrationTestCase
{
    private ?ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * @dataProvider validSchemaDataProvider
     */
    public function testItAcceptsTheSchema(string $schema): void
    {
        $violations = $this->validator->validate(
            \json_decode($schema, false, 512, JSON_THROW_ON_ERROR),
            new ProductMappingSchema()
        );

        $this->assertEmpty($violations);
    }

    /**
     * @dataProvider invalidSchemaDataProvider
     */
    public function testItRejectsTheSchema(string $schema): void
    {
        $violations = $this->validator->validate(
            \json_decode($schema, false, 512, JSON_THROW_ON_ERROR),
            new ProductMappingSchema()
        );

        $this->assertCount(1, $violations);
        $this->assertEquals('You must provide a valid schema.', $violations->get(0)->getMessage());
    }

    public function validSchemaDataProvider(): array
    {
        return $this->readFilesFromDirectory(__DIR__ . '/ProductSchema/valid');
    }

    public function invalidSchemaDataProvider(): array
    {
        return $this->readFilesFromDirectory(__DIR__ . '/ProductSchema/invalid');
    }

    /**
     * @return array<string, array{schema: string}>
     */
    private function readFilesFromDirectory(string $directory): array
    {
        $files = scandir($directory);
        $files = array_filter($files, fn ($file) => !str_starts_with($file, '.'));

        return array_combine($files, array_map(fn ($file) => [
            'schema' => file_get_contents($directory . '/' . $file),
        ], $files));
    }
}
