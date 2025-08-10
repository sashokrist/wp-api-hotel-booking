# HotelBookings — WordPress + Laravel API

A minimal WordPress site (custom **theme** + **plugin**) that consumes your **Laravel Hotel Booking API** to:

- List bookings
- Create a new booking (with customer/room dropdowns and total-price auto-calc)

This README shows how to **clone**,
**configure**,
and **run**
everything locally.

---

## Prerequisites

- **WordPress** 6.x installed (locally or on a dev server)
- **PHP** 8.1+ and **MySQL/MariaDB**
- A running **Laravel API** (your app), reachable at something like:
  http://wp-hotel-booking.test/api


- Laravel API has these endpoints:
- `GET /bookings` (public)
- `GET /rooms` (public)
- `GET /customers` (public)
- `POST /bookings` (auth or public — your choice)

> If `POST /bookings` requires auth, you’ll need a **Sanctum** token or valid login credentials.

---

## Repo layout

This project contains only the **theme** and **plugin**. WordPress core is not included.

wp-content/
themes/

hotelbookings/ # Custom theme (templates for Bookings + Create Booking pages)

plugins/

laravel-bookings/ # Plugin that talks to the Laravel API

You’ll copy these into your existing WordPress installation.

---

## 1) Clone and copy files

```bash
# 1) Clone somewhere on your machine
git clone git@github.com:sashokrist/wp-api-hotel-booking.git
cd hotelbookings-wp

# 2) Copy theme + plugin into your WordPress install
#    Replace /path/to/wordpress with your real WP root.
cp -r wp-content/themes/hotelbookings /path/to/wordpress/wp-content/themes/
cp -r wp-content/plugins/laravel-bookings /path/to/wordpress/wp-content/plugins/
2) Configure the plugin
Open:
/path/to/wordpress/wp-content/plugins/laravel-bookings/laravel-bookings.php

Adjust these:

// Base URL of your Laravel API (no trailing slash)
private $api_url = 'http://wp-hotel-booking.test/api';

// If you have a Sanctum token, put it here (and login will be skipped).
// Otherwise leave empty '' and the plugin will try to log in using email/password below.
private $api_token = ''; // e.g. '1|abcd...'

// Only used if $api_token is empty
'email'    => 'test@example.com',
'password' => 'secret123',
Optional (date display on the Bookings table):

php
Копиране
Редактиране
private $date_format = 'd M Y'; // e.g. "18 Aug 2025". Change to 'Y-m-d', 'd/m/Y', etc.
Security tip: Don’t commit real tokens/passwords. Use environment-specific values.

3) Activate theme & plugin
WordPress Admin → Plugins → activate Laravel Bookings Integration

WordPress Admin → Appearance → Themes → activate HotelBookings

The theme provides two page templates:

Bookings List (renders the bookings table)

Create Booking (renders the booking form)

Create the pages
Pages → Add New → Bookings

Page template: Bookings List

(content can be empty; the template renders the list)

Pages → Add New → New Booking

Page template: Create Booking

Set homepage (recommended)
Settings → Reading → Your homepage displays → A static page
Homepage: Bookings

Add both pages to your Primary Menu (Appearance → Menus).

4) Laravel API requirements
Routes (routes/api.php)

use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LoginRegisterController;

// Public list endpoints
Route::get('/bookings',  [BookingController::class, 'index']);
Route::get('/rooms',     [RoomController::class, 'index']);
Route::get('/customers', [CustomerController::class, 'index']);

// Protected (or keep POST public while developing)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('bookings', BookingController::class)->except(['index']);
    Route::post('/logout', [LoginRegisterController::class, 'logout']);
});

// If you want POST to be public while testing:
// Route::apiResource('bookings', BookingController::class)->except(['index'])->withoutMiddleware(['auth:sanctum']);
Controllers (minimal, for dropdowns)

// RoomController@index
public function index()
{
    // If "type" is your display field, normalize it to "name"
    return Room::select('id', DB::raw('type as name'), 'price_per_night')->get();
}

// CustomerController@index
public function index()
{
    return Customer::select('id', 'name')->get();
}
Booking creation (DB alignment)
Your bookings table should have (at least):

id (PK)

room_id (FK)

customer_id (FK)

check_in_date (DATE)

check_out_date (DATE)

total_price (DECIMAL) — either nullable or default 0.00 if computing server-side

created_at timestamp NULL / updated_at timestamp NULL (or disable timestamps)

Model App\Models\Booking:

protected $fillable = [
  'customer_id','room_id','check_in_date','check_out_date','total_price','status'
];

protected $casts = [
  'check_in_date' => 'date',
  'check_out_date'=> 'date',
];
Controller BookingController@store (accept what the plugin sends):

public function store(Request $request)
{
    $data = $request->validate([
        'customer_id'    => ['required','exists:customers,id'],
        'room_id'        => ['required','exists:rooms,id'],
        'check_in_date'  => ['required','date','before_or_equal:check_out_date'],
        'check_out_date' => ['required','date','after_or_equal:check_in_date'],
        'total_price'    => ['required','numeric','min:0'], // or compute server-side
    ]);

    $booking = Booking::create($data);
    return response()->json($booking, 201);
}
If you prefer, compute total_price server-side using the room’s nightly price × nights and make the field optional in validation.

5) Using the site
Visit /bookings → should list bookings from the Laravel API

Visit /new-booking → choose customer + room, set dates, Total Price auto-calculates if price_per_night is present in /rooms. Submit → a booking is created via POST /bookings.

6) Troubleshooting
Two bookings created at once

Ensure the page content does not also contain [laravel_create_booking] if the template already renders the form.

The plugin includes server-side guards + PRG redirect to avoid duplicates.

Create button “hangs”

The plugin uses a 12s timeout and Post/Redirect/Get.

If it still hangs, open DevTools → Network; confirm the POST completes.

Check Laravel storage/logs/laravel.log to ensure the request reached the API.

Dropdowns are empty

Ensure GET /customers and GET /rooms are public and return arrays like:

[{"id":1,"name":"John Doe"}, ...]
[{"id":10,"name":"Double Room","price_per_night":120.00}, ...]
If your rooms use type instead of name, either:

Map type as name in the controller (recommended), or

Use the plugin’s fallback (it tries name, then type, then common nested keys).

Dates show as ISO strings

The plugin formats dates with $date_format and the WP timezone. Adjust as needed.

Auth errors (401/403)

If POST /bookings is protected:

Put a Sanctum token in $api_token, or

Provide valid login credentials in authenticate() and leave $api_token empty.

422 validation errors

The plugin shows the exact JSON errors after form redirect.

Align request field names with your DB columns.

7) WP-CLI quick setup (optional)

# List pages and IDs
wp post list --post_type=page --fields=ID,post_title

# Set Bookings page as homepage (replace 123 with its ID)
wp option update show_on_front page
wp option update page_on_front 123

# Assign templates
wp post meta update 123 _wp_page_template page-templates/template-bookings.php
wp post meta update 456 _wp_page_template page-templates/template-create-booking.php
8) Security notes
Don’t commit real tokens/passwords.

Consider moving $api_url, $api_token to WP options or env vars.

If public endpoints expose sensitive data, switch them to auth and cache via the plugin.

9) License
MIT (or your preferred license).

10) Support
If you run into an error, copy the HTTP status and JSON body shown by the plugin after form submit, plus your Laravel log entry, and we’ll diagnose quickly.
