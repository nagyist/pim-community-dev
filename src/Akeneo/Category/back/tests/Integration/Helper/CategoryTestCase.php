<?php

declare(strict_types=1);

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Akeneo\Category\back\tests\Integration\Helper;

use Akeneo\Category\Application\Storage\Save\Query\UpsertCategoryBase;
use Akeneo\Category\Application\Storage\Save\Query\UpsertCategoryTranslations;
use Akeneo\Category\Domain\Model\Enrichment\Category;
use Akeneo\Category\Domain\Model\Attribute\AttributeImage;
use Akeneo\Category\Domain\Model\Attribute\AttributeRichText;
use Akeneo\Category\Domain\Model\Attribute\AttributeText;
use Akeneo\Category\Domain\Model\Attribute\AttributeTextArea;
use Akeneo\Category\Domain\Model\Enrichment\Template;
use Akeneo\Category\Domain\Query\GetCategoryInterface;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeAdditionalProperties;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeCode;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeCollection;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeIsLocalizable;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeIsRequired;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeIsScopable;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeOrder;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeType;
use Akeneo\Category\Domain\ValueObject\Attribute\AttributeUuid;
use Akeneo\Category\Domain\ValueObject\CategoryId;
use Akeneo\Category\Domain\ValueObject\Code;
use Akeneo\Category\Domain\ValueObject\LabelCollection;
use Akeneo\Category\Domain\ValueObject\Template\TemplateCode;
use Akeneo\Category\Domain\ValueObject\Template\TemplateUuid;
use Akeneo\Category\Infrastructure\Storage\InMemory\GetTemplateInMemory;
use Akeneo\Test\Integration\TestCase;

class CategoryTestCase extends TestCase
{
    /**
     * @param array<string, string>|null $labels
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    protected function createOrUpdateCategory(
        string $code,
        ?int $id = null,
        ?array $labels = [],
        ?int $parentId = null,
    ): Category {
        $categoryId = (null === $id ? null : new CategoryId($id));
        $parentId = (null === $parentId ? null : new CategoryId($parentId));

        $categoryModelToCreate = new Category(
            id: $categoryId,
            code: new Code($code),
            labels: LabelCollection::fromArray($labels),
            parentId: $parentId,
        );

        // Insert the category in pim_catalog_category
        $this->get(UpsertCategoryBase::class)->execute($categoryModelToCreate);

        // Get the data of the newly inserted category from pim_catalog_category
        $categoryBase = $this->get(GetCategoryInterface::class)->byCode((string) $categoryModelToCreate->getCode());
        $parentId =
            $categoryBase->getParentId() === null
                ? null
                : new CategoryId($categoryBase->getParentId()->getValue());

        $categoryModelWithId = new Category(
            new CategoryId($categoryBase->getId()->getValue()),
            new Code((string) $categoryBase->getCode()),
            $categoryModelToCreate->getLabels(),
            $parentId,
        );
        $this->get(UpsertCategoryTranslations::class)->execute($categoryModelWithId);

        $categoryTranslations = $this->get(GetCategoryInterface::class)->byCode((string) $categoryModelToCreate->getCode())->getLabels()->getTranslations();

        $createdParentId =
            $categoryBase->getParentId()?->getValue() > 0
            ? new CategoryId($categoryBase->getParentId()->getValue())
            : null;

        // Instantiates a new Category model based on data fetched in database
        return new Category(
            new CategoryId($categoryBase->getId()->getValue()),
            new Code((string) $categoryBase->getCode()),
            LabelCollection::fromArray($categoryTranslations),
            $createdParentId,
        );
    }

    /**
     * Insert dummy category.
     */
    protected function insertBaseCategory(Code $code): Category
    {
        $category = new Category(
            id: null,
            code: $code,
        );
        $this->get(UpsertCategoryBase::class)->execute($category);

        /** @var Category $createdCategory */
        $createdCategory = $this
            ->get(GetCategoryInterface::class)
            ->byCode((string) $category->getCode());

        return $createdCategory;
    }

    /**
     * @param array<string, string>|null $templateLabels
     * @param array<array<string, mixed>>|null $templateAttributes
     *
     * @throws \Exception
     */
    public function generateMockedCategoryTemplateModel(
        ?string $templateUuid = null,
        ?string $templateCode = null,
        ?array $templateLabels = null,
        ?int $categoryTreeId = null,
        ?array $templateAttributes = null,
    ): Template {
        $getTemplate = new GetTemplateInMemory();
        /** @var Template $defaultTemplate */
        $defaultTemplate = $getTemplate->byUuid('');

        if ($templateUuid === null) {
            $templateUuid = $defaultTemplate->getUuid();
        } else {
            $templateUuid = TemplateUuid::fromString($templateUuid);
        }

        if ($templateCode === null) {
            $templateCode = $defaultTemplate->getCode();
        } else {
            $templateCode = new TemplateCode($templateCode);
        }

        if ($templateLabels === null) {
            $templateLabels = $defaultTemplate->getLabelCollection();
        } else {
            $templateLabels = LabelCollection::fromArray($templateLabels);
        }

        if ($categoryTreeId === null) {
            $categoryTreeId = $defaultTemplate->getCategoryTreeId();
        } else {
            $categoryTreeId = new CategoryId($categoryTreeId);
        }

        if ($templateAttributes === null) {
            $templateAttributes = $defaultTemplate->getAttributeCollection();
        } else {
            $attributes = [];
            foreach ($templateAttributes as $attribute) {
                if (!array_key_exists('type', $attribute)) {
                    throw new \Exception('Can\'t generate mocked template: missing attribute type');
                }

                switch ($attribute['type']) {
                    case AttributeType::TEXTAREA:
                        $attributeClass = AttributeTextArea::class;
                        break;
                    case AttributeType::TEXT:
                        $attributeClass = AttributeText::class;
                        break;
                    case AttributeType::IMAGE:
                        $attributeClass = AttributeImage::class;
                        break;
                    case AttributeType::RICH_TEXT:
                        $attributeClass = AttributeRichText::class;
                        break;
                    default:
                        throw new \Exception(sprintf('Can\'t generate mocked template: unknown attribute type "%s"', $attribute['type']));
                }

                $attributes[] = $attributeClass::create(
                    AttributeUuid::fromString($attribute['uuid']),
                    new AttributeCode($attribute['code']),
                    AttributeOrder::fromInteger((int) $attribute['order']),
                    AttributeIsRequired::fromBoolean((bool) $attribute['is_required']),
                    AttributeIsScopable::fromBoolean((bool) $attribute['is_scopable']),
                    AttributeIsLocalizable::fromBoolean((bool) $attribute['is_localizable']),
                    LabelCollection::fromArray($attribute['labels']),
                    TemplateUuid::fromString($attribute['template_uuid']),
                    AttributeAdditionalProperties::fromArray($attribute['additional_properties']),
                );
            }
            $templateAttributes = AttributeCollection::fromArray($attributes);
        }

        return new Template(
            $templateUuid,
            $templateCode,
            $templateLabels,
            $categoryTreeId,
            $templateAttributes,
        );
    }

    protected function getConfiguration()
    {
        return $this->catalog->useMinimalCatalog();
    }
}
