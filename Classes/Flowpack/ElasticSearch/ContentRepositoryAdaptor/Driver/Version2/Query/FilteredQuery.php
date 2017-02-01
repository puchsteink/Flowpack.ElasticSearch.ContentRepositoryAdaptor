<?php
namespace Flowpack\ElasticSearch\ContentRepositoryAdaptor\Driver\Version2\Query;

/*
 * This file is part of the Flowpack.ElasticSearch.ContentRepositoryAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Driver\AbstractQuery;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Exception;

class FilteredQuery extends AbstractQuery
{
    /**
     * The ElasticSearch request, as it is being built up.
     *
     * @var array
     */
    protected $request = [
        'query' => [
            'filtered' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match_all' => []
                            ]
                        ]
                    ]

                ],
                'filter' => [
                    'bool' => [
                        'must' => [],
                        'should' => [],
                        'must_not' => [
                            [
                                'term' => ['_hidden' => true]
                            ],
                            [
                                'range' => [
                                    '_hiddenBeforeDateTime' => [
                                        'gt' => 'now'
                                    ]
                                ]
                            ],
                            [
                                'range' => [
                                    '_hiddenAfterDateTime' => [
                                        'lt' => 'now'
                                    ]
                                ]
                            ],
                        ],
                    ]
                ]
            ]
        ],
        'fields' => ['__path']
    ];

    /**
     * {@inheritdoc}
     */
    public function getCountRequestAsJSON()
    {
        $request = $this->request;
        foreach ($this->unsupportedFieldsInCountRequest as $field) {
            if (isset($request[$field])) {
                unset($request[$field]);
            }
        }

        return json_encode($request);
    }

    /**
     * {@inheritdoc}
     */
    public function size($size)
    {
        $this->request['size'] = (integer)$size;
    }

    /**
     * {@inheritdoc}
     */
    public function from($size)
    {
        $this->request['from'] = (integer)$size;
    }

    /**
     * {@inheritdoc}
     */
    public function fulltext($searchWord)
    {
        $this->appendAtPath('query.filtered.query.bool.must', [
            'query_string' => [
                'query' => $searchWord
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function queryFilter($filterType, $filterOptions, $clauseType = 'must')
    {
        if (!in_array($clauseType, ['must', 'should', 'must_not'])) {
            throw new Exception\QueryBuildingException('The given clause type "' . $clauseType . '" is not supported. Must be one of "must", "should", "must_not".', 1383716082);
        }

        return $this->appendAtPath('query.filtered.filter.bool.' . $clauseType, [$filterType => $filterOptions]);
    }
}
