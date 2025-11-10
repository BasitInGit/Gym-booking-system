# Gym Booking System
  A web-based application that allows gym members to book fitness classes. Built using PHP and MySQL, this system dynamically manages gym sessions, user bookings, and session capacities.
  **Live Demo:** Available on the university server (restricted access).
  
  ## Features
  Dynamic Class Booking:
- Users can select from available gym classes via drop-down menus populated from the database. Only sessions with free places are displayed.

User Input Validation:

- Names must contain letters, spaces, hyphens, or apostrophes, with specific formatting rules enforced in PHP.

- Phone numbers must contain 9–10 digits, start with 0, and only include digits and spaces.

Capacity Management:
- Each gym session has a limited capacity. Successful bookings reduce available spots, and fully booked sessions are automatically removed from the menus.

Real-Time Booking Updates:
- On submitting a booking, users are shown a confirmation and a table listing all successful bookings, including name, phone, class, and session time.

Secure Database Interaction:
- Implemented using PHP PDO to prevent SQL injection and ensure safe database operations.

Concurrency Handling:
- Correctly handles simultaneous booking attempts for the same session, ensuring only one booking succeeds for the last available spot.
  
## How to Use
     1. Clone this repository.
     2. Set up the database using the provided SQL script.
     3. Update `db.php` with your local credentials.
     4. Open `gym.php` in a PHP-enabled server (e.g., XAMPP, MAMP, or WAMP).
