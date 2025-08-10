<?php
/*
Plugin Name: Laravel Bookings Integration
Description: Fetch and create bookings from a Laravel API.
Version: 1.3
Author: Aleksander
*/

if (!defined('ABSPATH')) exit;

class Laravel_Bookings_Integration {

	/**
	 * Base API URL of your Laravel app (no trailing slash).
	 * Example: http://wp-hotel-booking.test/api
	 */
	private $api_url = 'http://wp-hotel-booking.test/api';

	private $date_format = 'd M Y';   // "18 Aug 2025"


	/**
	 * Sanctum token. If you set a non-empty token here, we will SKIP login().
	 * You can still let authenticate() try to log in by leaving this empty.
	 */
	private $api_token = '2|0djWTcGu5b49dZ1qB0JB7FUZ7HJaEGijOUBqc8D8151dc541';

	/** Guard to ensure one POST handling per request (extra safety). */
	private static $handled_post = false;

	public function __construct() {
		add_shortcode('laravel_bookings',        [$this, 'show_bookings']);
		add_shortcode('laravel_create_booking',  [$this, 'booking_form']);
		$this->authenticate();
	}

	/**
	 * If a token is already set, skip login. Otherwise try email/password.
	 * TIP: For production, store creds in WP options and DO NOT hardcode tokens.
	 */
	private function authenticate() {
		if (!empty($this->api_token)) {
			return; // we already have a token -> skip login
		}

		$response = wp_remote_post("{$this->api_url}/login", [
			'headers' => ['Content-Type' => 'application/json'],
			'body'    => json_encode([
				'email'    => 'test@example.com', // TODO: replace with real user
				'password' => 'secret123'         // TODO: replace with real pass
			]),
			'timeout' => 12,
		]);

		if (!is_wp_error($response)) {
			$data = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($data['token'])) {
				$this->api_token = $data['token'];
			}
		} else {
			// Optional: error_log('Laravel login failed: ' . $response->get_error_message());
		}
	}

	private function hb_format_date($value) {
		if (empty($value)) return '';
		try {
			// If it's already a timestamp
			if (is_numeric($value)) {
				$ts = (int)$value;
			} else {
				$dt = new DateTimeImmutable($value); // handles "Z" ISO strings nicely
				$ts = $dt->getTimestamp();
			}
			// Use WP timezone + locale formatting
			return wp_date($this->date_format, $ts);
		} catch (Throwable $e) {
			// Fallback: if it starts with YYYY-MM-DD, return just the date part
			if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $m)) {
				return $m[0];
			}
			return esc_html((string)$value);
		}
	}

	/**
	 * Public Bookings List (relies on your Laravel GET /bookings being public).
	 */
	public function show_bookings() {
		$response = wp_remote_get("{$this->api_url}/bookings", ['timeout' => 12]);

		if (is_wp_error($response)) {
			return '<p style="color:#b00">Error fetching bookings: '
			       . esc_html($response->get_error_message()) . '</p>';
		}

		$bookings = json_decode(wp_remote_retrieve_body($response), true);
		if (!is_array($bookings) || empty($bookings)) {
			return '<p>No bookings found.</p>';
		}

		ob_start();
		echo '<h2>Bookings</h2>';
		echo '<table class="hb-table"><thead><tr>';
		echo '<th>ID</th><th>Customer</th><th>Total</th><th>Check-in</th><th>Check-out</th>';
		echo '</tr></thead><tbody>';
		foreach ($bookings as $b) {
			$id   = is_array($b) ? ($b['id'] ?? '') : (is_object($b) ? ($b->id ?? '') : '');
			$cid  = is_array($b) ? ($b['customer_id'] ?? '') : (is_object($b) ? ($b->customer_id ?? '') : '');
			$tot  = is_array($b) ? ($b['total_price'] ?? '') : (is_object($b) ? ($b->total_price ?? '') : '');
			$cinV = is_array($b) ? ($b['check_in_date'] ?? '') : (is_object($b) ? ($b->check_in_date ?? '') : '');
			$coutV= is_array($b) ? ($b['check_out_date'] ?? ''): (is_object($b) ? ($b->check_out_date ?? ''): '');

			// NEW: pretty format
			$cin  = $this->hb_format_date($cinV);
			$cout = $this->hb_format_date($coutV);

			echo '<tr>';
			echo '<td>'.esc_html($id).'</td>';
			echo '<td>'.esc_html($cid).'</td>';
			echo '<td>'.esc_html($tot).'</td>';
			echo '<td>'.esc_html($cin).'</td>';
			echo '<td>'.esc_html($cout).'</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		return ob_get_clean();
	}

	/**
	 * Create Booking Form (POST -> Laravel, then PRG redirect back with success/error).
	 * Expects Laravel columns: customer_id, room_id, check_in_date, check_out_date, total_price
	 */
	public function booking_form() {
		// Flash messages (PRG)
		if (isset($_GET['hb_success'])) {
			echo '<div style="color:green;margin-bottom:12px;">Booking created successfully.</div>';
		}
		if (isset($_GET['hb_error'])) {
			echo '<div style="color:#b00;margin-bottom:12px;"><strong>Booking failed:</strong><br><pre style="white-space:pre-wrap;">'
			     . esc_html(wp_unslash($_GET['hb_error'])) . '</pre></div>';
		}

		// Handle POST (once)
		if (
			$_SERVER['REQUEST_METHOD'] === 'POST' &&
			isset($_POST['hb_booking_nonce']) &&
			wp_verify_nonce($_POST['hb_booking_nonce'], 'hb_create_booking') &&
			!self::$handled_post
		) {
			self::$handled_post = true;

			$payload = [
				'customer_id'    => sanitize_text_field($_POST['customer_id'] ?? ''),
				'room_id'        => sanitize_text_field($_POST['room_id'] ?? ''),
				'check_in_date'  => sanitize_text_field($_POST['check_in_date'] ?? ''),
				'check_out_date' => sanitize_text_field($_POST['check_out_date'] ?? ''),
				'total_price'    => sanitize_text_field($_POST['total_price'] ?? ''),
			];

			$headers = [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			];
			if (!empty($this->api_token)) {
				$headers['Authorization'] = "Bearer {$this->api_token}";
			}

			$response = wp_remote_post("{$this->api_url}/bookings", [
				'headers'     => $headers,
				'body'        => json_encode($payload),
				'timeout'     => 12,
				'redirection' => 0,
				'blocking'    => true,
			]);

			$redirect = get_permalink();
			if (is_wp_error($response)) {
				$err = rawurlencode($response->get_error_message());
				wp_safe_redirect(add_query_arg('hb_error', $err, $redirect));
				exit;
			} else {
				$code = wp_remote_retrieve_response_code($response);
				$body = wp_remote_retrieve_body($response);

				if ($code >= 200 && $code < 300) {
					wp_safe_redirect(add_query_arg('hb_success', 1, $redirect));
					exit;
				} else {
					$err = rawurlencode($body ?: ("HTTP ".$code));
					wp_safe_redirect(add_query_arg('hb_error', $err, $redirect));
					exit;
				}
			}
		}

		// Fetch dropdown data AFTER handling POST
		$customers = $this->fetch_dropdown_data('customers');
		$rooms     = $this->fetch_dropdown_data('rooms');

		// Render form
		ob_start(); ?>
		<form method="POST" id="create-booking-form" action="">
			<?php wp_nonce_field('hb_create_booking', 'hb_booking_nonce'); ?>

			<p>
				Customer:
				<select name="customer_id" required>
					<option value="">-- Select Customer --</option>
					<?php foreach ($customers as $cust): ?>
						<?php
						$cid   = is_array($cust) ? ($cust['id'] ?? '') : (is_object($cust) ? ($cust->id ?? '') : '');
						$cname = is_array($cust) ? ($cust['name'] ?? '') : (is_object($cust) ? ($cust->name ?? '') : '');
						?>
						<option value="<?php echo esc_attr($cid); ?>"><?php echo esc_html($cname ?: ('Customer #'.$cid)); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				Room:
				<select name="room_id" id="room_id" required>
					<option value="">-- Select Room --</option>
					<?php foreach ($rooms as $room): ?>
						<?php
						// ID
						$rid = is_array($room) ? ($room['id'] ?? '') : (is_object($room) ? ($room->id ?? '') : '');

						// Label: prefer 'name', then 'type', then nested common keys
						$rlabel = is_array($room)
							? ($room['name'] ?? $room['type'] ?? ($room['room_type']['name'] ?? $room['type_name'] ?? ''))
							: ($room->name ?? $room->type ?? ($room->room_type->name ?? $room->type_name ?? ''));

						// Price for auto-calc: try common keys
						$rprice = '';
						if (is_array($room)) {
							$rprice = $room['price_per_night'] ?? $room['price'] ?? ($room['room_type']['price_per_night'] ?? '');
						} else if (is_object($room)) {
							$rprice = $room->price_per_night ?? $room->price ?? ($room->room_type->price_per_night ?? '');
						}
						?>
						<option value="<?php echo esc_attr($rid); ?>" <?php if ($rprice !== '') echo 'data-price="'.esc_attr($rprice).'"'; ?>>
							<?php echo esc_html($rlabel ?: ('Room #'.$rid)); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>Check In:  <input type="date" name="check_in_date"  id="check_in_date"  required></p>
			<p>Check Out: <input type="date" name="check_out_date" id="check_out_date" required></p>

			<p>
				Total Price:
				<input type="number" step="0.01" min="0" name="total_price" id="total_price" required>
				<small>You can type manually, or it will auto-calc if the room option has a price.</small>
			</p>

			<p><button type="submit" id="hb-submit">Create Booking</button></p>
		</form>

		<script>
            (function () {
                function parseNum(v){ var n=parseFloat(v); return isNaN(n)?0:n; }
                function daysBetween(a,b){
                    var ONE=24*60*60*1000;
                    var d1=new Date(a), d2=new Date(b);
                    if (isNaN(d1)||isNaN(d2)) return 0;
                    return Math.max(0, Math.round((d2-d1)/ONE));
                }
                function getSelectedPrice(){
                    var sel=document.getElementById('room_id');
                    if(!sel||!sel.selectedOptions||!sel.selectedOptions[0]) return 0;
                    return parseNum(sel.selectedOptions[0].getAttribute('data-price'));
                }
                function recalc(){
                    var price=getSelectedPrice();
                    var inD=document.getElementById('check_in_date')?.value;
                    var outD=document.getElementById('check_out_date')?.value;
                    var nights=daysBetween(inD,outD);
                    var total=document.getElementById('total_price');
                    if(!total) return;
                    if(price>0 && nights>0){
                        total.value=(price*nights).toFixed(2);
                    }
                }
                ['change','input'].forEach(function(evt){
                    ['room_id','check_in_date','check_out_date'].forEach(function(id){
                        var el=document.getElementById(id);
                        if(el) el.addEventListener(evt,recalc);
                    });
                });
                document.addEventListener('DOMContentLoaded', recalc);
            })();
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Fetch an endpoint as array (expects public GET /{endpoint}).
	 * Returns [] on failure. You can add auth header if those are protected.
	 */
	private function fetch_dropdown_data($endpoint) {
		$args = ['timeout' => 12];
		// If your /rooms or /customers require auth, uncomment:
		// if (!empty($this->api_token)) {
		//     $args['headers'] = ['Authorization' => "Bearer {$this->api_token}"];
		// }
		$res  = wp_remote_get("{$this->api_url}/{$endpoint}", $args);
		$data = is_wp_error($res) ? [] : json_decode(wp_remote_retrieve_body($res), true);
		return is_array($data) ? $data : [];
	}
}

new Laravel_Bookings_Integration();