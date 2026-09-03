import './bootstrap';

const statusToneClasses = [
    'status-neutral', 'status-info', 'status-violet', 'status-warning',
    'status-success', 'status-danger', 'status-cyan',
];

function statusTone(label) {
    const value = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');

    if (/(rejected|expired|closed|cancelled|canceled|failed|overdue|declined)/.test(value)) return 'status-danger';
    if (/(confirmed|accepted|approved|paid|received|completed|complete|successful|done)/.test(value)) return 'status-success';
    if (/(pending|partial|waiting|rate_requested|amendment|on_hold)/.test(value)) return 'status-warning';
    if (/(operation|pricing|in_progress|processing)/.test(value)) return 'status-violet';
    if (/(sent|converted|issued)/.test(value)) return 'status-cyan';
    if (/(assigned|info_gather|open|active|scheduled)/.test(value)) return 'status-info';

    return 'status-neutral';
}

function applyStatusColors(root = document) {
    const badges = root.matches?.('.status-badge') ? [root] : root.querySelectorAll?.('.status-badge') ?? [];

    badges.forEach((badge) => {
        const label = badge.textContent.trim();
        badge.classList.remove(...statusToneClasses);
        badge.classList.add(statusTone(label));

        if (/[a-z]/i.test(label) && !badge.querySelector('.status-dot')) {
            const dot = document.createElement('span');
            dot.className = 'status-dot';
            dot.setAttribute('aria-hidden', 'true');
            badge.prepend(dot);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    applyStatusColors();

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) applyStatusColors(node);
        }));
    });
    observer.observe(document.body, { childList: true, subtree: true });
});

document.addEventListener('livewire:navigated', () => applyStatusColors());

// Enhanced tooltip functionality for info icons
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth fade-in effect for tooltips
    const style = document.createElement('style');
    style.textContent = `
        .cursor-help[data-tooltip] {
            position: relative;
            transition: color 0.2s ease;
        }
        
        .cursor-help[data-tooltip]:hover {
            color: #3b82f6 !important;
        }
        
        .cursor-help[data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            max-width: 300px;
            white-space: normal;
            text-align: center;
            animation: tooltipFadeIn 0.2s ease-in-out;
        }
        
        .cursor-help[data-tooltip]:hover::before {
            content: '';
            position: absolute;
            bottom: 115%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1f2937;
            z-index: 1000;
            animation: tooltipFadeIn 0.2s ease-in-out;
        }
        
        @keyframes tooltipFadeIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
});
