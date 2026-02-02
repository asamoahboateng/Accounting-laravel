<div>
    <style>
        :root {
            --sidebar-bg: {{ $theme['sidebar_bg'] ?? '#1e293b' }};
            --sidebar-text: {{ $theme['sidebar_text'] ?? '#e2e8f0' }};
            --sidebar-text-muted: {{ $theme['sidebar_text_muted'] ?? '#94a3b8' }};
            --sidebar-hover-bg: {{ $theme['sidebar_hover_bg'] ?? '#334155' }};
            --sidebar-active-bg: {{ $theme['sidebar_active_bg'] ?? '#0f172a' }};
            --sidebar-border: {{ $theme['sidebar_border'] ?? '#334155' }};
            --sidebar-brand-bg: {{ $theme['sidebar_brand_bg'] ?? '#0f172a' }};
            --sidebar-accent-color: {{ $theme['sidebar_accent_color'] ?? '#10b981' }};
        }

        /* Sidebar container */
        .fi-sidebar {
            background-color: var(--sidebar-bg) !important;
            border-right-color: var(--sidebar-border) !important;
        }

        /* Sidebar header/brand area */
        .fi-sidebar-header {
            background-color: var(--sidebar-brand-bg) !important;
            border-bottom: 1px solid var(--sidebar-border) !important;
        }

        /* Brand text */
        .fi-sidebar-header a,
        .fi-sidebar-header span {
            color: var(--sidebar-text) !important;
        }

        /* Navigation items */
        .fi-sidebar-nav {
            background-color: transparent !important;
        }

        /* Navigation group labels */
        .fi-sidebar-group-label {
            color: var(--sidebar-text-muted) !important;
            font-size: 0.7rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }

        /* Navigation links */
        .fi-sidebar-item-button {
            color: var(--sidebar-text) !important;
            transition: all 0.15s ease-in-out !important;
        }

        .fi-sidebar-item-button:hover {
            background-color: var(--sidebar-hover-bg) !important;
            color: white !important;
        }

        /* Active navigation item */
        .fi-sidebar-item-active .fi-sidebar-item-button {
            background-color: var(--sidebar-active-bg) !important;
            color: white !important;
            border-left: 3px solid var(--sidebar-accent-color) !important;
        }

        /* Navigation icons */
        .fi-sidebar-item-icon {
            color: var(--sidebar-text-muted) !important;
        }

        .fi-sidebar-item-button:hover .fi-sidebar-item-icon,
        .fi-sidebar-item-active .fi-sidebar-item-icon {
            color: var(--sidebar-accent-color) !important;
        }

        /* Collapse button */
        .fi-sidebar-nav-collapse-btn {
            color: var(--sidebar-text-muted) !important;
        }

        .fi-sidebar-nav-collapse-btn:hover {
            color: white !important;
            background-color: var(--sidebar-hover-bg) !important;
        }

        /* Tenant/Company switcher in sidebar */
        .fi-tenant-menu-trigger {
            background-color: var(--sidebar-hover-bg) !important;
            color: var(--sidebar-text) !important;
            border-color: var(--sidebar-border) !important;
        }

        .fi-tenant-menu-trigger:hover {
            background-color: var(--sidebar-active-bg) !important;
        }

        /* User menu in sidebar */
        .fi-sidebar-footer {
            background-color: var(--sidebar-brand-bg) !important;
            border-top: 1px solid var(--sidebar-border) !important;
        }

        /* Sub-navigation items */
        .fi-sidebar-group-items {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }

        /* Badge styling in sidebar */
        .fi-sidebar-item-badge {
            background-color: var(--sidebar-accent-color) !important;
            color: white !important;
        }

        /* Scrollbar styling for sidebar */
        .fi-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .fi-sidebar::-webkit-scrollbar-track {
            background: var(--sidebar-bg);
        }

        .fi-sidebar::-webkit-scrollbar-thumb {
            background: var(--sidebar-border);
            border-radius: 3px;
        }

        .fi-sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--sidebar-text-muted);
        }

        /* Mobile sidebar overlay */
        @media (max-width: 1023px) {
            .fi-sidebar {
                background-color: var(--sidebar-bg) !important;
            }
        }

        /* Group toggle buttons */
        .fi-sidebar-group-button {
            color: var(--sidebar-text-muted) !important;
        }

        .fi-sidebar-group-button:hover {
            color: var(--sidebar-text) !important;
        }
    </style>
</div>
