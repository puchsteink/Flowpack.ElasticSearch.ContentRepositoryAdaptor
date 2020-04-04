<?php
declare(strict_types=1);

namespace Flowpack\ElasticSearch\ContentRepositoryAdaptor\Driver\Version6\Mapping;

/*
 * This file is part of the Flowpack.ElasticSearch.ContentRepositoryAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Service\NodeTypeIndexingConfiguration;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Driver\AbstractNodeTypeMappingBuilder;
use Flowpack\ElasticSearch\Domain\Model\Index;
use Flowpack\ElasticSearch\Domain\Model\Mapping;
use Flowpack\ElasticSearch\Mapping\MappingCollection;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Error\Messages\Result;
use Neos\Error\Messages\Warning;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeTypeMappingBuilder extends AbstractNodeTypeMappingBuilder
{
    /**
     * @var NodeTypeIndexingConfiguration
     * @Flow\Inject
     */
    protected $nodeTypeIndexingConfiguration;


    /**
     * Builds a Mapping Collection from the configured node types
     *
     * @param Index $index
     * @return MappingCollection<\Flowpack\ElasticSearch\Domain\Model\Mapping>
     * @throws \Flowpack\ElasticSearch\ContentRepositoryAdaptor\Exception
     */
    public function buildMappingInformation(Index $index): MappingCollection
    {
        $this->lastMappingErrors = new Result();

        $mappings = new MappingCollection(MappingCollection::TYPE_ENTITY);

        /** @var NodeType $nodeType */
        foreach ($this->nodeTypeManager->getNodeTypes() as $nodeTypeName => $nodeType) {
            if ($nodeTypeName === 'unstructured' || $nodeType->isAbstract()) {
                continue;
            }

            if ($this->nodeTypeIndexingConfiguration->isIndexable($nodeType) === false) {
                continue;
            }

            $type = $index->findType($this->convertNodeTypeNameToMappingName($nodeTypeName));
            $mapping = new Mapping($type);
            $fullConfiguration = $nodeType->getFullConfiguration();
            if (isset($fullConfiguration['search']['elasticSearchMapping'])) {
                $fullMapping = $fullConfiguration['search']['elasticSearchMapping'];
                $mapping->setFullMapping($fullMapping);
            }

            foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
                if (isset($propertyConfiguration['search']['elasticSearchMapping'])) {
                    if (is_array($propertyConfiguration['search']['elasticSearchMapping'])) {
                        $propertyMapping = $propertyConfiguration['search']['elasticSearchMapping'];
                        $mapping->setPropertyByPath($propertyName, $propertyMapping);
                    }
                } elseif (isset($propertyConfiguration['type'], $this->defaultConfigurationPerType[$propertyConfiguration['type']]['elasticSearchMapping'])) {
                    if (is_array($this->defaultConfigurationPerType[$propertyConfiguration['type']]['elasticSearchMapping'])) {
                        $mapping->setPropertyByPath($propertyName, $this->defaultConfigurationPerType[$propertyConfiguration['type']]['elasticSearchMapping']);
                    }
                } else {
                    $this->lastMappingErrors->addWarning(new Warning('Node Type "' . $nodeTypeName . '" - property "' . $propertyName . '": No ElasticSearch Mapping found.'));
                }
            }

            $mappings->add($mapping);
        }

        return $mappings;
    }
}
