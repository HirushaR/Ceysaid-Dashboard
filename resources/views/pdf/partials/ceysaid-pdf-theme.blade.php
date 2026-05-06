{{-- Shared PDF styling (aligned with quote / invoice documents) --}}
<style>
    * { box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
        font-size: 10px;
        color: #222;
        margin: 0;
        padding: 28px 32px;
        line-height: 1.5;
        background: #fff;
    }
    .top-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .top-table td { vertical-align: top; padding: 0; }
    .top-table td.meta { text-align: right; width: 42%; }
    .logo-wrap { margin-bottom: 10px; }
    .logo-wrap img {
        max-height: 48px;
        width: auto;
        max-width: 220px;
        display: block;
    }
    .doc-title {
        font-size: 26px;
        font-weight: bold;
        color: #1f3c88;
        margin: 0 0 8px 0;
    }
    .company-details p,
    .invoice-meta p,
    .bill-to p,
    .notes p,
    .bank-details p {
        margin: 3px 0;
        line-height: 1.5;
    }
    .company-details strong { font-size: 11px; }
    .highlight-box {
        margin-top: 14px;
        background: #f1f5ff;
        border-left: 5px solid #1f3c88;
        padding: 12px 16px;
        border-radius: 4px;
    }
    .highlight-box h2 {
        margin: 0 0 6px 0;
        font-size: 16px;
        color: #1a1a1a;
    }
    .section { margin-top: 22px; }
    .section h3 {
        margin: 0 0 10px 0;
        color: #1f3c88;
        font-size: 12px;
        border-bottom: 2px solid #eee;
        padding-bottom: 6px;
    }
    table.lines {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }
    table.lines th,
    table.lines td {
        border: 1px solid #ddd;
        padding: 10px 8px;
        text-align: left;
        vertical-align: top;
    }
    table.lines th {
        background: #f4f6fb;
        font-weight: bold;
    }
    table.lines th.num, table.lines td.num { text-align: right; white-space: nowrap; }
    table.lines th.ix, table.lines td.ix { width: 28px; text-align: center; }
    .item-title { font-weight: bold; display: block; margin-bottom: 3px; }
    table.totals {
        margin-top: 20px;
        width: 300px;
        margin-left: auto;
        border-collapse: collapse;
        border: 1px solid #ddd;
        border-radius: 6px;
        overflow: hidden;
    }
    table.totals td {
        padding: 10px 14px;
        border-bottom: 1px solid #ddd;
    }
    table.totals tr:last-child td {
        border-bottom: none;
        background: #1f3c88;
        color: #fff;
        font-weight: bold;
    }
    table.totals td:last-child { text-align: right; }
    .footer-note {
        margin-top: 22px;
        font-size: 9px;
        color: #444;
        background: #fff8e8;
        border-left: 4px solid #f0b429;
        padding: 10px 12px;
        border-radius: 4px;
    }
    .doc-notice {
        margin-top: 14px;
        font-size: 9px;
        color: #555;
        font-style: italic;
    }
</style>
