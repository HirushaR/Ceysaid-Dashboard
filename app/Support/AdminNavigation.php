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
            $dashboard['items'][] = ['label' => 'Document Complete', 'route' => 'admin.dashboard.documents', 'active' => 'admin.dashboard.documents'];
            $dashboard['items'][] = ['label' => 'Visa Leads', 'route' => 'admin.dashboard.visa', 'active' => 'admin.dashboard.visa'];
        }
        if ($user->isOperation()) {
            $dashboard['items'][] = ['label' => 'My Operation Leads', 'route' => 'admin.dashboard.operations', 'active' => 'admin.dashboard.operations'];
        }
        if ($user->isCallCenter()) {
            $dashboard['items'][] = ['label' => 'My Call Centre Leads', 'route' => 'admin.dashboard.call-centre', 'active' => 'admin.dashboard.call-centre'];
        }
        if ($user->isAdmin() || $user->isManager()) {
            $dashboard['items'][] = ['label' => 'Archived Leads', 'route' => 'admin.dashboard.archived', 'active' => 'admin.dashboard.archived'];
        }
        if ($user->isSales() || $user->isOperation()) {
            $dashboard['items'][] = ['label' => 'Internal Notes', 'route' => 'admin.dashboard.notes', 'active' => 'admin.dashboard.notes'];
        }
        $groups[] = $dashboard;
        if ($user->isAdmin() || $user->isAccount() || $user->hasAnyPermission(['quotes.view', 'invoices.view'])) {
            $groups[] = ['label' => 'Finance', 'items' => [
                ['label' => 'Quotes', 'route' => 'admin.quotes.index', 'active' => 'admin.quotes.*'],
                ['label' => 'Invoices', 'route' => 'admin.invoices.index', 'active' => 'admin.invoices.*'],
                ['label' => 'Receivables', 'route' => 'admin.receivables.index', 'active' => 'admin.receivables.*'],
                ['label' => 'Vendor Bills', 'route' => 'admin.vendor-bills.index', 'active' => 'admin.vendor-bills.*'],
                ['label' => 'Suppliers', 'route' => 'admin.suppliers.index', 'active' => 'admin.suppliers.*'],
                ['label' => 'Payments', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*'],
            ]];
        }
        $operations = [];
        if ($user->isAdmin() || $user->hasPermission('customers.view')) {
            array_unshift($operations, ['label' => 'Customers', 'route' => 'admin.customers.index', 'active' => 'admin.customers.*']);
        }
        if ($user->isAdmin() || $user->isAccount()) {
            $operations[] = ['label' => 'Tours', 'route' => 'admin.tours.index', 'active' => 'admin.tours.*'];
        }
        if ($user->isAdmin() || $user->isCallCenter()) {
            $operations[] = ['label' => 'Arrivals', 'route' => 'admin.arrivals.index', 'active' => 'admin.arrivals.*'];
            $operations[] = ['label' => 'Departures', 'route' => 'admin.departures.index', 'active' => 'admin.departures.*'];
            $operations[] = ['label' => 'Assigned Calls', 'route' => 'admin.calls.index', 'active' => 'admin.calls.*'];
        }
        if ($user->isAdmin() || $user->isSales()) {
            $operations[] = ['label' => 'WhatsApp Inbox', 'route' => 'admin.whatsapp.index', 'active' => 'admin.whatsapp.*'];
        }
        $groups[] = ['label' => 'Operations', 'items' => $operations];
        $employeeItems = [
            ['label' => 'My Leave Requests', 'route' => 'admin.leave.index', 'active' => 'admin.leave.index'],
            ['label' => 'Leave Calendar', 'route' => 'admin.leave.calendar', 'active' => 'admin.leave.calendar'],
        ];
        if ($user->isAdmin() || $user->isHR()) {
            $employeeItems[] = ['label' => 'Office Closures', 'route' => 'admin.closures.index', 'active' => 'admin.closures.*'];
        }
        $groups[] = ['label' => 'Employee Services', 'items' => $employeeItems];
        if ($user->isAdmin() || $user->isAccount() || $user->isManager()) {
            $groups[] = ['label' => 'Analytics', 'items' => [
                ['label' => 'Business Performance', 'route' => 'admin.analytics.index', 'active' => 'admin.analytics.index'],
                ...(($user->isAdmin() || ($user->isSales() && $user->isManager())) ? [['label' => 'Staff Performance', 'route' => 'admin.analytics.staff', 'active' => 'admin.analytics.staff']] : []),
            ]];
        }
        if ($user->isAdmin() || $user->isHR()) {
            $groups[] = ['label' => 'Administration', 'items' => [
                ['label' => 'Users & Teams', 'route' => 'admin.users.index', 'active' => 'admin.users.*'],
                ...($user->isAdmin() ? [['label' => 'Permission Groups', 'route' => 'admin.permission-groups.index', 'active' => 'admin.permission-groups.*']] : []),
            ]];
        }

        return $groups;
    }
}
