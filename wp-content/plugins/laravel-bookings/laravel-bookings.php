<?php
/*
Plugin Name: Laravel Bookings Integration
Description: Fetch and create bookings from Laravel API.
Version: 1.2
Author: Aleksander
*/

if (!defined('ABSPATH')) exit;

class Laravel_Bookings_Integration {

	private $api_url = 'http://wp-hotel-booking.test/api';
	private $api_token = '2|0djWTcGu5b49dZ1qB0JB7FUZ7HJaEGijOUBqc8D8151dc541';


	public function __construct() {
		add_shortcode('laravel_bookings', [$this, 'show_bookings']);
		add_shortcode('laravel_create_booking', [$this, 'booking_form']);
		$this->authenticate();
	}

	private function authenticate() {
		$response = wp_remote_post("{$this->api_url}/login", [
			'headers' => ['Content-Type' => 'application/json'],
			'body'    => json_encode([
				'email'    => 'test@example.com', // change to your Laravel user's email
				'password' => 'secret123'         // change to your Laravel user's password
			])
		]);

		if (!is_wp_error($response)) {
			$data = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($data['token'])) {
				$this->api_token = $data['token'];
			}
		}
	}

	public function show_bookings() {
		$response = wp_remote_get("{$this->api_url}/bookings");
		if (is_wp_error($response)) return 'Error fetching bookings';
		$bookings = json_decode(wp_remote_retrieve_body($response), true);
		if (!is_array($bookings) || empty($bookings)) {
			return '<p>No bookings found.</p>';
		}

		ob_start();
		echo "<h2>Bookings</h2><ul>";
		foreach ($bookings as $booking) {
			echo "<li>Booking #{$booking['id']} - {$booking['customer_id']} - {$booking['total_price']} - {$booking['check_in_date']} - {$booking['check_out_date']}</li>";
		}
		echo "</ul>";
		return ob_get_clean();
	}

	public function booking_form() {
		// 1) (Optional) Check token if your POST /bookings is protected
		// if (!$this->api_token) return '<p>Auth failed...</p>';

		// 2) Fetch dropdown data (customers/rooms) the same way you already do
		$customers = $this->fetch_dropdown_data('customers');
		$rooms     = $this->fetch_dropdown_data('rooms');

		// 3) Handle POST (send check_in_date, check_out_date, total_price to Laravel)
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_id'])) {
			$payload = [
				'customer_id'    => sanitize_text_field($_POST['customer_id']),
				'room_id'        => sanitize_text_field($_POST['room_id']),
				'check_in_date'  => sanitize_text_field($_POST['check_in_date']),
				'check_out_date' => sanitize_text_field($_POST['check_out_date']),
				'total_price'    => sanitize_text_field($_POST['total_price']),
			];

			$headers = [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			];
			if (!empty($this->api_token)) {
				$headers['Authorization'] = "Bearer {$this->api_token}";
			}

			$response = wp_remote_post("{$this->api_url}/bookings", [
				'headers' => $headers,
				'body'    => json_encode($payload),
				'timeout' => 20,
			]);

			if (is_wp_error($response)) {
				echo '<div style="color:#b00">Request failed: ' . esc_html($response->get_error_message()) . '</div>';
			} else {
				$code = wp_remote_retrieve_response_code($response);
				$body = wp_remote_retrieve_body($response);
				if ($code >= 200 && $code < 300) {
					echo '<div style="color:green">Booking created successfully.</div>';
				} else {
					echo '<div style="color:#b00"><strong>API error ' . esc_html($code) . ':</strong><br><pre style="white-space:pre-wrap;">' . esc_html($body) . '</pre></div>';
				}
			}
		}

		// 4) OUTPUT THE FORM (this is your snippet)
		ob_start();
		?>
		<!-- your form starts here -->
		<form method="POST" id="create-booking-form">
			<p>
				Customer:
				<select name="customer_id" required>
					<option value="">-- Select Customer --</option>
					<?php foreach ($customers as $cust): ?>
						<?php
						$cid   = is_array($cust) ? ($cust['id'] ?? '') : (is_object($cust) ? ($cust->id ?? '') : '');
						$cname = is_array($cust) ? ($cust['name'] ?? '') : (is_object($cust) ? ($cust->name ?? '') : '');
						?>
						<option value="<?php echo esc_attr($cid); ?>"><?php echo esc_html($cname); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				Room:
				<select name="room_id" id="room_id" required>
					<option value="">-- Select Room --</option>
					<?php foreach ($rooms as $room): ?>
						<?php
						$rid   = is_array($room) ? ($room['id'] ?? '') : (is_object($room) ? ($room->id ?? '') : '');
						$rname = is_array($room) ? ($room['name'] ?? '') : (is_object($room) ? ($room->name ?? '') : '');
						$rprice = is_array($room) && isset($room['price_per_night']) ? $room['price_per_night'] : '';
						?>
						<option value="<?php echo esc_attr($rid); ?>" <?php if ($rprice!=='') echo 'data-price="'.esc_attr($rprice).'"'; ?>>
							<?php echo esc_html($rname); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>Check In:  <input type="date" name="check_in_date"  id="check_in_date"  required></p>
			<p>Check Out: <input type="date" name="check_out_date" id="check_out_date" required></p>

			<p>
				Total Price:
				<input type="number" step="0.01" min="0" name="total_price" id="total_price" required>
				<small>You can type manually, or it will auto-calc if room price is available.</small>
			</p>

			<p><input type="submit" value="Create Booking"></p>
		</form>

		<script>
            // Optional: auto-calc total price = nights * price_per_night if available
            (function(){
                function daysBetween(a, b) {
                    const ONE = 24*60*60*1000;
                    const d1 = new Date(a);
                    const d2 = new Date(b);
                    if (isNaN(d1) || isNaN(d2)) return 0;
                    return Math.max(0, Math.round((d2 - d1) / ONE));
                }
                function recalc() {
                    const room    = document.querySelector('#room_id');
                    const priceEl = room ? room.selectedOptions[0] : null;
                    const p = priceEl ? parseFloat(priceEl.getAttribute('data-price') || '0') : 0;
                    const inD  = document.querySelector('#check_in_date')?.value;
                    const outD = document.querySelector('#check_out_date')?.value;
                    const nights = daysBetween(inD, outD);
                    if (p > 0 && nights > 0) {
                        document.querySelector('#total_price').value = (p * nights).toFixed(2);
                    }
                }
                ['change','input'].forEach(evt => {
                    document.querySelector('#room_id')?.addEventListener(evt, recalc);
                    document.querySelector('#check_in_date')?.addEventListener(evt, recalc);
                    document.querySelector('#check_out_date')?.addEventListener(evt, recalc);
                });
            })();
		</script>
		<!-- your form ends here -->
		<?php
		return ob_get_clean();
	}

	private function fetch_dropdown_data($endpoint) {
		$res  = wp_remote_get("{$this->api_url}/{$endpoint}");
		$data = is_wp_error($res) ? [] : json_decode(wp_remote_retrieve_body($res), true);
		return is_array($data) ? $data : [];
	}
}

new Laravel_Bookings_Integration();
