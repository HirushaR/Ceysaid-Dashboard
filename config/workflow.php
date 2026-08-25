<?php

return [
    'flags' => [
        'schema_ready' => ['enabled' => env('WORKFLOW_SCHEMA_READY', false)],
        'shadow_events' => ['enabled' => env('WORKFLOW_SHADOW_EVENTS', false)],
        'dual_write' => ['enabled' => env('WORKFLOW_DUAL_WRITE', false)],
        'shadow_compare' => ['enabled' => env('WORKFLOW_SHADOW_COMPARE', false)],
        'canonical_read' => ['enabled' => env('WORKFLOW_CANONICAL_READ', false)],
        'enforce_engine_writes' => ['enabled' => env('WORKFLOW_ENFORCE_ENGINE_WRITES', false)],
        'outbox_processing' => ['enabled' => env('WORKFLOW_OUTBOX_PROCESSING', false)],
    ],
    'ui' => [
        'new_shell' => ['enabled' => env('UI_NEW_SHELL', false)],
        'lead_workspace' => ['enabled' => env('UI_LEAD_WORKSPACE', false)],
        'sales_pipeline' => ['enabled' => env('UI_SALES_PIPELINE', false)],
        'operations' => ['enabled' => env('UI_OPERATIONS', false)],
        'inbox' => ['enabled' => env('UI_INBOX', false)],
        'finance_context' => ['enabled' => env('UI_FINANCE_CONTEXT', false)],
    ],
];
