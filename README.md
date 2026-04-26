# FleetSimplify Web App Walkthrough

This document explains how to set up and use the FleetSimplify roadside assistance web app.

## 1. What This App Does

FleetSimplify connects:

- Drivers (users) who need roadside help
- Mechanics (service providers) who can accept and complete jobs
- Admins who manage mechanic approvals and monitor platform activity

Main features:

- Role-based registration and login
- Breakdown request creation
- Mechanic assignment and task lifecycle (Pending -> Accepted -> In Progress -> Completed)
- Live location tracking between driver and mechanic
- In-app chat per request
- Invoice amount on completion and Paystack payment verification
- Ratings and feedback after service completion

## 2. Quick Local Setup (XAMPP)

### Prerequisites

- XAMPP installed (Apache + MySQL)
- Project inside htdocs: `c:/xampp/htdocs/Fleetsimplify`

### Step-by-step

1. Start Apache and MySQL from XAMPP Control Panel.
2. Create the database by importing `database.sql` in phpMyAdmin.
3. Configure environment variables:
	- Copy `.env.example` to `.env`
	- Set:
	  - `PAYSTACK_PUBLIC_KEY`
	  - `PAYSTACK_SECRET_KEY`
	  - Optional mechanic notification toggles:
		 - `NOTIFICATION_NEARBY_ONLY` (`1` or `0`)
		 - `NOTIFICATION_RADIUS_KM`
4. Open the app:
	- `http://localhost/Fleetsimplify`

Notes:

- Database connection defaults are in `backend/config/db.php` (host `127.0.0.1`, db `roadside_assistance`, user `root`, blank password).
- If Paystack keys are not set, payment verification uses a local mock success path for testing.

## 3. Default Test Accounts

From `database.sql` seed data:

- Admin:
  - Email: `admin@roadside.com`
  - Password: `password123`
- Many test users and mechanics are pre-seeded, also using password `password123`.

## 4. App Entry Points

- Root redirect: `/index.php` -> `/frontend/`
- Public pages:
  - `/frontend/index.php`
  - `/frontend/login.php`
  - `/frontend/register.php`

## 5. Driver (User) Walkthrough

### A. Register as Driver

1. Open Sign Up.
2. Keep role as **Driver (User)**.
3. Enter name, email, phone, password.
4. Submit and log in.

### B. Log In

1. Go to login page.
2. Enter email/password.
3. You are redirected to `frontend/user/dashboard.php`.

### C. Create a Breakdown Request

1. In dashboard, go to request form.
2. Enter:
	- Vehicle type
	- Location address
	- Problem description
	- Coordinates (captured from location, or defaults to `0,0` if unavailable)
3. Submit.
4. Request status starts as **Pending**.

### D. Wait for Mechanic Acceptance

1. Mechanic sees pending requests.
2. Once accepted, your request status becomes **Accepted**.

### E. Track Mechanic Live

1. Open tracking for that request.
2. See:
	- Your location
	- Mechanic location (updated every ~5 seconds)
	- Estimated distance and ETA

### F. Chat with Mechanic

1. Open request chat.
2. Send/receive real-time polled messages (about every 2 seconds).
3. Chat is only available when request is assigned and not in Pending state.

### G. Pay After Job Completion

1. Mechanic marks request as **Completed** and sets invoice amount (KES).
2. Go to Payment page.
3. Choose payment method option and continue to checkout.
4. Pay in Paystack inline modal.
5. Backend verifies reference, amount, and marks request `payment_status = Paid` on success.

### H. Rate Service

1. Open rating page for completed request.
2. Submit stars (1-5), optional feedback, and repair time.
3. One rating per request.

## 6. Mechanic Walkthrough

### A. Register as Mechanic

1. On register page, switch role to **Service Provider**.
2. Enter required mechanic details:
	- Service location
	- Services offered
	- License number
3. Submit registration.
4. Account remains **PENDING APPROVAL** until admin action.

### B. Log In (After Approval)

1. Once admin approves, login works normally.
2. You are redirected to `frontend/mechanic/dashboard.php`.

### C. Accept a Request

1. View unassigned pending requests.
2. Accept one.
3. Request is atomically locked/updated to avoid two mechanics taking same request.

### D. Manage Task Status

1. Move task through:
	- `Accepted`
	- `In Progress`
	- `Completed`
2. When setting `Completed`, you must enter a valid amount > 0.

### E. Share Live Location

1. Open track page for assigned request.
2. Browser asks for geolocation permission.
3. App sends mechanic coordinates every ~5 seconds.
4. Driver sees live movement and ETA.

### F. Chat and Feedback

1. Chat with driver from request context.
2. Review customer ratings/feedback in mechanic feedback page.
3. Update business profile details in mechanic profile page.

## 7. Admin Walkthrough

### A. Log In as Admin

1. Use admin credentials.
2. You are redirected to `frontend/admin/dashboard.php`.

### B. Approve/Reject Mechanics

1. Open pending approvals section.
2. Approve or reject each mechanic.
3. Approval status updates immediately.

### C. Monitor Platform Activity

Admin dashboard/reports provide:

- Total users, mechanics, and requests
- Pending approvals
- Recent request table
- Charts and analytics (status, trends, vehicle mix, breakdown types)

## 8. Request Lifecycle Reference

Canonical request flow:

1. Driver submits request -> `Pending`
2. Mechanic accepts -> `Accepted`
3. Mechanic starts work -> `In Progress`
4. Mechanic finishes and sets amount -> `Completed`
5. Driver pays -> `payment_status = Paid`
6. Driver leaves rating/feedback

## 9. Important Operational Notes

- Mechanic login is blocked unless approval status is `APPROVED`.
- Chat is request-scoped and participant-validated (only assigned driver/mechanic).
- Payment verification checks duplicate references and expected amount before marking paid.
- Environment variables are loaded from `.env` by `backend/config/db.php`.

## 10. Troubleshooting

### App loads but login/register fails

- Confirm database imported successfully.
- Verify MySQL is running.
- Check DB credentials in `backend/config/db.php`.

### Payment says not configured

- Add valid Paystack keys in `.env`.
- Ensure `PAYSTACK_PUBLIC_KEY` and `PAYSTACK_SECRET_KEY` are present.

### Mechanic cannot log in

- Confirm mechanic approval status is `APPROVED` in `mechanics` table.

### Tracking is not updating

- Confirm browser location permission is allowed.
- Test with HTTPS or localhost where geolocation is supported.

