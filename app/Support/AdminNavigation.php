<?php

namespace App\Support;

use App\Models\User;

class AdminNavigation
{
    /** @return array<int, array{label:string,items:array<int,array{label:string,route:string,active:string}>> */
    public static function for(User $user): array
    {
        $groups = [[
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
                ['label' => 'Leads', 'route' => 'admin.leads.index', 'active' => 'admin.leads.*'],
            ],
        ]];
        if ($user->isAdmin() || $user->isAccount() || $user->hasAnyPermission(['quotes.view', 'invoices.view'])) {
            $groups[] = ['label' => 'Finance', 'items' => [
                ['label' => 'Quotes', 'route' => 'admin.quotes.index', 'active' => 'admin.quotes.*'],
                ['label' => 'Invoices', 'route' => 'admin.invoices.index', 'active' => 'admin.invoices.*'],
                ['label' => 'Suppliers', 'route' => 'admin.suppliers.index', 'active' => 'admin.suppliers.*'],
                ['label' => 'Payments', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*'],
            ]];
        }
        $groups[] = ['label' => 'Operations', 'items' => [
            ['label' => 'Customers', 'route' => 'admin.module', 'active' => 'admin.customers.*'],
            ['label' => 'Tours', 'route' => 'admin.module', 'active' => 'admin.tours.*'],
            ['label' => 'WhatsApp', 'route' => 'admin.module', 'active' => 'admin.whatsapp.*'],
        ]];
        if ($user->isAdmin() || $user->isHR()) {
            $groups[] = ['label' => 'Administration', 'items' => [
                ['label' => 'People & Leave', 'route' => 'admin.module', 'active' => 'admin.people.*'],
                ['label' => 'Access Control', 'route' => 'admin.module', 'active' => 'admin.access.*'],
            ]];
        }

        return $groups;
    }
}
