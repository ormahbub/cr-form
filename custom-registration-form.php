<?php
/**
 * Plugin Name: Multi-Step Registration Form Pro
 * Description: Clean, maintainable 4-step registration form.
 * Version: 1.3
 */

if (!defined('ABSPATH')) exit;

function crf_enqueue_assets() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    wp_enqueue_style('crf-styles', plugins_url('style.css', __FILE__));
    wp_enqueue_script('crf-js', plugins_url('form-handler.js', __FILE__), array(), '1.0', true);
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
                <div class="input-group"><label>First name <span class="required">*</span></label><input type="text" required></div>
                <div class="input-group"><label>Last name <span class="required">*</span></label><input type="text" required></div>
                <div class="input-group"><label>E-mail <span class="required">*</span></label><input type="email" required></div>
                
                <div class="input-group">
                    <label>Phone <span class="required">*</span></label>
                    <div class="phone-input">
                        <div class="flag-select"><img src="https://flagcdn.com/w20/bd.png" alt="BD"><i class="fas fa-caret-down"></i></div>
                        <input type="tel" required>
                    </div>
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
            </div>

            <div class="form-section" id="section-2">
                <h3>Terms and Conditions</h3>
                <div class="terms-box">
                    <p>By registering, you agree to our policies...</p>
                    <label class="checkbox-label"><input type="checkbox" required> I agree to the terms</label>
                </div>
            </div>

            <div class="form-section" id="section-3">
                <h3>Email Verification</h3>
                <p>Enter the code sent to your email.</p>
                <div class="input-group"><label>Email Code</label><input type="text" placeholder="Enter code"></div>
            </div>

            <div class="form-section" id="section-4">
                <h3>Phone Verification</h3>
                <p>Final Step: Enter your SMS code.</p>
                <div class="input-group"><label>SMS Code <span class="required">*</span></label><input type="text" required></div>
            </div>

            <div class="button-container">
                <button type="button" id="prevBtn" class="next-btn btn-secondary" style="display:none;">BACK</button>
                <button type="button" id="nextBtn" class="next-btn">NEXT</button>
            </div>
        </form>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('crf_shortcode', 'crf_registration_form_shortcode');