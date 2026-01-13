<?php
/**
 * Plugin Name: Custom Registration Form
 * Description: Clean, maintainable 4-step registration form with URMembership integration.
 * Version: 1.4
 * Author: Mahbub
 */

if (!defined('ABSPATH')) exit;

// Include URMembership integration
require_once plugin_dir_path(__FILE__) . 'urm-integration.php';

// Include sync tool in admin
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'sync-tool.php';
}

function crf_enqueue_assets() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    wp_enqueue_style('crf-styles', plugins_url('style.css', __FILE__));
    wp_enqueue_script('crf-js', plugins_url('form-handler.js', __FILE__), array(), '1.1', true);
    
    // Pass AJAX URL and nonce to JavaScript
    wp_localize_script('crf-js', 'crf_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('crf_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'crf_enqueue_assets');

function crf_registration_form_shortcode() {
    ob_start(); ?>
    <div class="registration-card">
        <div class="stepper" id="stepper">
            <div class="step active" data-step="1"><div class="circle">1</div><div class="label">Contacts and credentials</div></div>
            <div class="step" data-step="2"><div class="circle">2</div><div class="label">Terms and conditions</div></div>
            <div class="step" data-step="3"><div class="circle">3</div><div class="label">Email confirmation</div></div>
            <div class="step" data-step="4"><div class="circle">4</div><div class="label">Phone confirmation</div></div>
        </div>

        <form id="multi-step-form" class="form-content">
            <div class="form-section active" id="section-1">
                <div class="input-group"><label>Sponsor ID</label><input type="number" name="sponsor"></div>
                <div class="input-group"><label>First name <span class="required">*</span></label><input name="first_name" type="text" required></div>
                <div class="input-group"><label>Last name <span class="required">*</span></label><input name="last_name" type="text" required></div>
                <div class="input-group"><label>E-mail <span class="required">*</span></label><input type="email" id="user_email" required></div>
                
                <div class="input-group">
                    <label>Phone <span class="required">*</span></label>
                    <div class="phone-input">
                        <div class="flag-select">
                            <img src="https://flagcdn.com/w20/bd.png" alt="BD">
                            <i class="fas fa-caret-down"></i>
                        </div>
                        <input type="tel" id="user_phone" name="phone" placeholder="01XXXXXXXXX" required>
                    </div>
                    <span id="phone-error" style="color: #ff4d4d; font-size: 11px; margin-top: 5px; display: none;">Please enter a valid phone number</span>
                </div>

                <div class="input-group">
                    <label>Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="main_pwd" class="password-field" required>
                        <i class="fas fa-eye-slash toggle-password"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password confirm <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_pwd" class="password-field" required>
                        <i class="fas fa-eye-slash toggle-password"></i>
                    </div>
                    <span id="pass-error" style="color: #ff4d4d; font-size: 11px; margin-top: 5px; display: none;">Passwords do not match</span>
                </div>
                
                <!-- Membership Plan Selection (Add this new section) -->
                <div class="input-group" style="display: none;" id="plan-selection">
                    <label>Membership Plan</label>
                    <select name="membership_plan" id="membership_plan">
                        <option value="1">Basic Plan</option>
                        <option value="2">Pro Plan</option>
                        <option value="3">Premium Plan</option>
                    </select>
                </div>
            </div>

            <div class="form-section" id="section-2">
                <h3>Terms and Conditions</h3>
                <div class="terms-box">
                    <p>By registering, you agree to our policies...</p>
                    <label class="checkbox-label"><input type="checkbox" id="terms_agree" required> I agree to the terms</label>
                </div>
            </div>

            <div class="form-section" id="section-3">
                <h3>Email Verification</h3>
                <p>Enter the code sent to your email.</p>
                <div class="input-group"><label>Email Code</label><input type="text" id="email_code" placeholder="Enter code"></div>
            </div>

            <div class="form-section" id="section-4">
                <h3>Phone Verification</h3>
                <p>Final Step: Enter your SMS code.</p>
                <div class="input-group"><label>SMS Code <span class="required">*</span></label><input type="text" id="sms_code" required></div>
            </div>

            <div class="button-container">
                <button type="button" id="prevBtn" class="next-btn btn-secondary" style="display:none;">BACK</button>
                <button type="button" id="nextBtn" class="next-btn">NEXT</button>
            </div>

            <input type="hidden" name="action" value="register_user_action">
            <?php wp_nonce_field('registration_nonce', 'security'); ?>

        </form>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('crf_shortcode', 'crf_registration_form_shortcode');

// Handle the AJAX Request
add_action('wp_ajax_nopriv_register_user_action', 'crf_handle_registration');
add_action('wp_ajax_register_user_action', 'crf_handle_registration');

function crf_handle_registration() {
    // 1. Security Check
    check_ajax_referer('registration_nonce', 'security');
    
    // 2. Collect and Sanitize Data
    $email    = sanitize_email($_POST['email']);
    $first    = sanitize_text_field($_POST['first_name']);
    $last     = sanitize_text_field($_POST['last_name']);
    $password = $_POST['password']; // wp_create_user hashes this for us
    $phone    = sanitize_text_field($_POST['phone']);
    $sponsor  = sanitize_text_field($_POST['sponsor']);
    $plan_id  = isset($_POST['membership_plan']) ? intval($_POST['membership_plan']) : 1;
    $terms    = isset($_POST['terms_agree']) ? true : false;
    
    // 3. Validation
    if (!$terms) {
        wp_send_json_error('You must agree to the terms and conditions.');
    }
    
    if (email_exists($email)) {
        wp_send_json_error('This email is already registered.');
    }
    
    // 4. Create WordPress User
    $user_id = wp_create_user($email, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }
    
    // 5. Save Extra Meta
    update_user_meta($user_id, 'first_name', $first);
    update_user_meta($user_id, 'last_name', $last);
    update_user_meta($user_id, 'phone_number', $phone);
    update_user_meta($user_id, 'sponsor_id', $sponsor);
    
    // 6. Integrate with URMembership
    $urm_result = URMembership_Integration::complete_user_registration($user_id, $plan_id, 0);
    
    // 7. Set user role (optional)
    $user = new WP_User($user_id);
    $user->set_role('subscriber'); // or 'member' if URMembership uses a custom role
    
    // 8. Optional: Log the user in immediately
    // wp_set_auth_cookie($user_id);
    
    // 9. Send success response
    $response = array(
        'message' => 'Registration complete!',
        'user_id' => $user_id,
        'urm_integration' => $urm_result ? 'success' : 'failed'
    );
    
    wp_send_json_success($response);
}

// Add activation hook
register_activation_hook(__FILE__, 'crf_plugin_activation');
function crf_plugin_activation() {
    URMembership_Integration::check_urm_tables();
}