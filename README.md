# Lumidexx Logistics — Simple SaaS

A self-contained, multi-tenant logistics platform in plain PHP + MySQL. No frameworks, no build step — matches your existing stack.

## What it does

- **Multi-tenant accounts** — each signup is its own "company" (their own clients, shipments, invoices)
- **Shipments** — create, assign to a client, generate a tracking number, update status through a defined flow, full status history/timeline
- **Clients (CRM-lite)** — add/remove clients, see shipment counts per client
- **Invoices** — generate against a shipment, mark paid, auto-flag overdue based on due date
- **Public tracking page** (`track.php`) — no login required; a customer enters their tracking number and sees live status + timeline. This is the page you'd link from outreach emails / a company site.
- **Dashboard** — shipment counts by state, outstanding invoice total, recent shipments

## Setup

1. **Create the database**
   ```bash
   mysql -u root -p < schema.sql
   ```

2. **Configure the connection**
   Edit `config/db.php` with your MySQL credentials (host/db/user/pass).

3. **Deploy**
   Drop the whole folder into your Apache document root (e.g. `/var/www/html/logistics/`). No composer, no npm — it just runs.

4. **Visit `index.php`**, click "Sign up", create your first account. You're in.

## Typical flow

1. Sign up → creates your company account
2. Add a client (`clients.php`)
3. Create a shipment for that client (`shipments.php`) → get a tracking number like `LX9F2A1B7C`
4. Update status as it moves (Pending → Picked Up → In Transit → Out for Delivery → Delivered)
5. Generate an invoice against the shipment, mark it paid when settled
6. Share `track.php?t=LX9F2A1B7C` with the client so they can self-serve tracking

## Structure

```
logistics-saas/
├── config/db.php          # DB credentials
├── includes/
│   ├── auth.php           # session, auth helpers, ID generators
│   ├── header.php         # shared shell (sidebar nav when logged in)
│   └── footer.php
├── assets/css/style.css   # dark glassmorphism theme
├── assets/js/app.js       # modal open/close helper
├── index.php               # login
├── register.php            # signup
├── logout.php
├── dashboard.php
├── clients.php
├── shipments.php           # list + detail/status-update view
├── invoices.php
├── track.php                # public, no-login tracking page
└── schema.sql
```

## Notes / where to extend next

- **Security**: passwords are hashed with `password_hash()`, all queries are parameterized (PDO prepared statements), tenant isolation is enforced by `user_id` on every query. Still add HTTPS in production and consider rate-limiting the login form.
- **Multi-user per company**: currently one login = one company. If you want staff logins under one company account, add a `company_id` layer above `users`.
- **Notifications**: no email/SMS is wired up yet. Natural next step would be firing a notification on status change (you could reuse the same SMTP setup you've used for other Lumidexx projects).
- **Driver assignment**: not included — easy to bolt on with a `drivers` table + a `driver_id` column on `shipments` if you need dispatch features later.
