import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface Organization {
    id: number;
    name: string;
    slug: string;
    organizationName: string;
}

export interface OrganizationSwitcher {
    current: number | null;
    options: Array<{ id: number; organizationName: string }>;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    organization: Organization | null;
    organizationSwitcher: OrganizationSwitcher | null;
    ziggy: Config & { location: string };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    is_admin?: boolean;
    super_admin?: boolean;
    notify_expense_sheet_to_approval?: boolean;
    notify_receipt_expense_sheet?: boolean;
    notify_remind_approval?: boolean;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
