# SwiftBus: Online Bus Reservation System
PHP 8 + MySQL + vanilla HTML/CSS/JavaScript. No frameworks.

## 1. Install (XAMPP on Windows)
1. Unzip so you have `C:\xampp\htdocs\bus_reservation\` (the folder containing `index.php`).
2. Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. Open http://localhost/phpmyadmin, click **Import**, choose `database/schema.sql`, click **Import**.
4. Import `database/sample_data.sql` the same way.
5. Open http://localhost/bus_reservation/

If your XAMPP MySQL has a root password, edit `config/database.php`.
If you rename the folder, edit `BASE_URL` in `config/config.php`.

## 2. Test accounts (change these before any real use)
| Role      | Email                          | Password      |
|-----------|--------------------------------|---------------|
| Admin     | admin@busreservation.test      | Admin@12345   |
| Passenger | passenger@busreservation.test  | Passenger@123 |

The sample trips are dated relative to the day you import the data (tomorrow, +2, +3 days).
If you open the project much later, re-import `schema.sql` then `sample_data.sql`, or add new trips as admin.

## 3. Folder map
- `index.php, search.php, trip.php, login.php, register.php, logout.php, about.php, contact.php`: public pages
- `passenger/`: dashboard, book, confirmation, bookings, booking_details, cancel_booking, profile
- `admin/`: dashboard, buses, locations, routes, trips, trip_seats, bookings, payments, users, reports, messages, profile
- `includes/`: bootstrap (loads everything), functions (helpers/security), booking (booking, cancel, payment rules), header/footer, seat_map, ticket, search_form, profile_form
- `config/`: config.php (settings, session) and database.php (PDO connection)
- `database/`: schema.sql and sample_data.sql
- `assets/`: css, js, images

## 4. How the important parts work
- **No double booking**: `createBooking()` in `includes/booking.php` runs in a DB transaction, locks the trip row, re-checks the seat, and the table has `UNIQUE (trip_id, seat_id, seat_lock)`. `seat_lock` is NULL for cancelled bookings, so cancelling frees the seat.
- **Payments are simulated**: `processPayment()` only records a fake "paid" payment. Replace the inside of that one function to add Paystack, Flutterwave, Opay, etc.
- **Cancellation policy**: stored in the `settings` table (`cancellation_hours`, default 24). Admins can cancel any booking.
- **Security**: password_hash/verify, PDO prepared statements, CSRF tokens on every POST form, htmlspecialchars output escaping (`e()`), role checks (`requireLogin('admin')`), session id regenerated at login, safe redirects, login attempt lock.

## 5. Common problems
- Blank page or "service unavailable": MySQL not running, or the database was not imported.
- 404: folder is not `htdocs\bus_reservation`, or `BASE_URL` does not match.
- "No trips found": sample trips are in the past. Re-import, or add trips in Admin > Trips.
- Port 80 busy: change Apache to port 8080 and use http://localhost:8080/bus_reservation/
