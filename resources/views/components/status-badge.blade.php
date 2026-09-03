@props(['status', 'label' => null])
@php
    $raw = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $text = $label ?? ($status instanceof \App\Enums\LeadStatus ? $status->label() : ($status instanceof \App\Enums\QuoteStatus ? $status->label() : str_replace('_', ' ', ucfirst($raw))));
    $key = strtolower(str_replace([' ', '-'], '_', $raw));
    $tone = match (true) {
        in_array($key, ['confirmed', 'accepted', 'paid', 'completed', 'complete', 'document_upload_complete', 'operation_complete', 'approved', 'received', 'done'], true) => 'status-success',
        in_array($key, ['rejected', 'expired', 'closed', 'mark_closed', 'cancelled', 'canceled', 'failed', 'overdue'], true) => 'status-danger',
        in_array($key, ['pending', 'partial', 'rate_requested', 'amendment', 'waiting'], true) => 'status-warning',
        in_array($key, ['assigned_to_operations', 'pricing_in_progress', 'in_progress'], true) => 'status-violet',
        in_array($key, ['sent', 'sent_to_customer', 'converted'], true) => 'status-cyan',
        in_array($key, ['assigned_to_sales', 'info_gather_complete', 'open'], true) => 'status-info',
        default => 'status-neutral',
    };
@endphp
<span {{ $attributes->class(['status-badge', $tone]) }}><span class="status-dot" aria-hidden="true"></span>{{ $text }}</span>
