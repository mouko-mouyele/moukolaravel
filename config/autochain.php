<?php

return [
    'name' => 'AutoChain Emma+',
    'author' => 'Moïse',

    'blockchain' => [
        'enabled' => env('BLOCKCHAIN_ENABLED', false),
        'rpc_url' => env('BLOCKCHAIN_RPC_URL', 'http://127.0.0.1:8545'),
        'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
        'chain_id' => env('BLOCKCHAIN_CHAIN_ID', 31337),
        // false = signatures MetaMask acceptées en local (soutenance). true = ECDSA strict (production).
        'strict_signatures' => env('BLOCKCHAIN_STRICT_SIGNATURES', false),
    ],

    'ipfs' => [
        'enabled' => env('IPFS_ENABLED', false),
        'gateway' => env('IPFS_GATEWAY', 'https://ipfs.io/ipfs/'),
        'api_url' => env('IPFS_API_URL', 'http://127.0.0.1:5001'),
    ],

    'alerts' => [
        'insurance_days_before' => (int) env('ALERT_INSURANCE_DAYS', 30),
        'inspection_days_before' => (int) env('ALERT_INSPECTION_DAYS', 30),
        'oil_change_km_before' => (int) env('ALERT_OIL_CHANGE_KM', 500),
        'maintenance_km_before' => (int) env('ALERT_MAINTENANCE_KM', 1000),
    ],

    'documents' => [
        'disk' => env('DOCUMENTS_DISK', 'documents'),
        'max_size_kb' => (int) env('DOCUMENT_MAX_SIZE_KB', 10240),
    ],
];
