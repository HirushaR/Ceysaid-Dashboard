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
                ['label' => 'Sales Pipeline', 'route' => 'admin.leads.pipeline', 'active' => 'admin.leads.pipeline'],
            ],
        ]];
        $dashboard = ['label' => 'Dashboard', 'items' => [
            ['label' => 'Leads', 'route' => 'admin.leads.index', 'active' => 'admin.leads.index'],
        ]];
        if ($user->isSales()) {
            array_push($dashboard['items'],
                ['label' => 'Other Leads', 'route' => 'admin.dashboard.other', 'active' => 'admin.dashboard.other'],
                ['label' => 'My Sales', 'route' => 'admin.dashboard.my-sales', 'active' => 'admin.dashboard.my-sales'],
                ['label' => 'Cruise Lead', 'route' => 'admin.dashboard.cruise', 'active' => 'admin.dashboard.cruise'],
                ['label' => 'Group Lead', 'route' => 'admin.dashboard.group', 'active' => 'admin.dashboard.group'],
            );
        }
        if ($user->isSales() || $user->isOperation() || $user->isAdmin()) {
            $dashboard['items'][] = ['label' => 'Confirm Lead', 'route' => 'admin.dashboard.confirmed', 'active' => 'admin.dashboard.confirmed'];
            $dashboard['items'][] = ['label' => 'Visa Leads', 'route' => 'admin.dashboard.visa', 'active' => 'admin.dashboard.visa'];
        }
        if ($user->isSales() || $user->isOperation()) {
            $dashboard['items'][] = ['label' => 'Internal Notes', 'route' => 'admin.dashboard.notes', 'active' => 'admin.dashboard.notes'];
        }
        $groups[] = $dashboard;
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
