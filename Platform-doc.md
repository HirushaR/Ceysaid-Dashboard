# Platform Documentation

## 1. High-Level Overview

### Platform Purpose
This platform is a CRM-like admin system for managing leads, customers, documents, group tours, and users, with dashboards and reporting for marketing and operations.

### Main Modules
- Leads Management
- Customer Management
- Document Management
- Group Tours
- User Management
- Dashboards
- Actions & Workflows
- Filters & Metrics


---

## 2. Detailed Feature List

### Leads
- Create, view, edit, and delete leads.
- Assign leads to users or operations.
- Change lead status (e.g., Info Gather Complete, Mark Completed, Mark Closed, Pricing In Progress, Sent to Customer).
- Upload attachments to leads.
- Set associated customer.
- Track lead source (platform, destination, status).
- Filter leads (by status, unassigned, etc.).
- Metrics: Conversion rate, average time to conversion, leads by day/destination/platform/status, lost/closed leads, uncontacted leads.

### Customers
- Create, view, edit, and delete customers.
- Associate customers with leads.

### Documents
- Upload, view, and manage documents.
- Associate documents with leads/customers.

### Group Tours
- Manage group tour data (CRUD).

### Users
- Manage users (CRUD).
- Assign users to departments.
- Two-factor authentication support.
- Assign leads to users.

### Departments
- Manage departments (CRUD).
- Assign users to departments.

### Dashboards
- Main dashboard.
- Marketing dashboard.
- Operations dashboard.
- My Sales dashboard.
- My Operation Leads dashboard.

### Actions
- Assign to me.
- Assign to operations.
- Change lead status.
- Mark as completed by operations.
- Mark lead as closed.
- Mark pricing in progress.
- Mark sent to customer.
- Set customer.
- Upload lead attachments.

---

## 3. User Stories

### As a Sales/Operations User:
- I want to view a list of leads assigned to me so that I can manage my workload.
- I want to filter leads by status, platform, or destination so I can focus on relevant leads.
- I want to update the status of a lead (e.g., mark as completed, closed, in progress) so that the team is aware of its current state.
- I want to assign a lead to myself or another user so that responsibilities are clear.
- I want to upload documents to a lead so that all relevant information is stored together.
- I want to view dashboards showing my performance and lead metrics so I can track my progress.

### As a Marketing User:
- I want to view marketing dashboards to see lead sources and conversion rates.
- I want to filter leads by marketing platform or campaign.

### As an Admin:
- I want to manage users and assign them to departments so that access and responsibilities are organized.
- I want to create, edit, and delete departments.
- I want to manage group tours and associate them with leads.
- I want to manage customers and associate them with leads.
- I want to view and manage all documents in the system.

### As a System:
- I want to enforce two-factor authentication for users for security.
- I want to log all actions on leads for audit purposes.

---

## 4. Entity/Model Overview

### Main Entities
- **User**: id, name, email, department_id, two_factor columns, etc.
- **Department**: id, name, etc.
- **Lead**: id, customer_id, assigned_to, assigned_operator, status, platform, destination, created_by, tour details, etc.
- **Customer**: id, name, contact info, etc.
- **Document**: id, lead_id, customer_id, file_path, etc.
- **GroupTour**: id, name, details, etc.

### Relationships
- A **Lead** belongs to a **Customer** and can have many **Documents**.
- A **Lead** is assigned to a **User** (sales/operations).
- A **User** belongs to a **Department**.
- A **GroupTour** can be associated with a **Lead**.
- **Documents** can be attached to **Leads** or **Customers**.

---

## 5. Suggested Next Steps

1. **Review and Confirm**: Validate this documentation with stakeholders.
2. **Prioritize Features**: Decide which features are MVP for the new platform.
3. **Choose Technology Stack**: E.g., Node.js + React, Django + React, etc.
4. **Design Database Schema**: Based on the entities above.
5. **Write User Stories as Tickets**: For your new dev team.
6. **Plan Migration**: For existing data (if needed).

---

**Note:** For a more detailed breakdown of any module, or a full list of all database fields and relationships, please request further documentation. 