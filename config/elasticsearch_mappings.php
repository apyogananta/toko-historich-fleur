<?php

return [
    'products' => [
        'settings' => [
            'analysis' => [

                'filter' => [
                    'edge_ngram_filter' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 2,
                        'max_gram' => 15,
                        'side' => 'front'
                    ],
                    'synonym_filter' => [
                        'type' => 'synonym',
                        'synonyms' => [
                            "hitam, black",
                            "putih, white",
                            "merah, red",
                            "biru, blue",
                            "ungu, purple",
                            "coklat, cokelat, brown",
                            "kemeja, shirt",
                            "celana, pants, trousers",
                            "kaos, tee, t-shirt",
                            "jaket, jacket",
                            "sepatu, shoes",
                            "ramah lingkungan, eco friendly",
                            "wngm, wingman"
                        ],
                    ]
                ],

                'analyzer' => [
                    'edge_ngram_synonym_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'synonym_filter', 'edge_ngram_filter']
                    ],
                    'standard_lowercase' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase']
                    ]
                ],
            ]
        ],

        'mappings' => [
            'properties' => [

                'product_name' => [
                    'type' => 'text',
                    'analyzer' => 'edge_ngram_synonym_analyzer',
                    'search_analyzer' => 'standard_lowercase'
                ],
                'description' => [
                    'type' => 'text',
                    'analyzer' => 'edge_ngram_synonym_analyzer',
                    'search_analyzer' => 'standard_lowercase'
                ],
                'brand' => [
                    'type' => 'text',
                    'analyzer' => 'edge_ngram_synonym_analyzer',
                    'search_analyzer' => 'standard_lowercase',
                    'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                ],
                'category_name' => [
                    'type' => 'text',
                    'analyzer' => 'edge_ngram_synonym_analyzer',
                    'search_analyzer' => 'standard_lowercase',
                    'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                ],
                'color' => [
                    'type' => 'text',
                    'analyzer' => 'edge_ngram_synonym_analyzer',
                    'search_analyzer' => 'standard_lowercase',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256
                        ]
                    ]
                ],
                'size' => [
                    'type' => 'text',
                    'analyzer' => 'standard_lowercase',
                    'fields' => [
                        'keyword' => ['type' => 'keyword', 'ignore_above' => 256]
                    ]
                ],

                'id' => ['type' => 'keyword'],
                'category_id' => ['type' => 'integer'],
                'slug' => ['type' => 'keyword'],
                'original_price' => ['type' => 'integer'],
                'sale_price' => ['type' => 'integer'],
                'stock' => ['type' => 'integer', 'index' => false],
                'weight' => ['type' => 'integer', 'index' => false],
                'created_at' => ['type' => 'date'],
                'updated_at' => ['type' => 'date']

            ]
        ]

    ]

];
