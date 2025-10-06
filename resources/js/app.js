import './bootstrap';

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
