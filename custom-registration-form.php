<?php
/**
 * Plugin Name: Custom Registration Form
 * Description: A custom registration form for subscription process. Place this shortcode [crf_shortcode] where you would like to add the form
 * Version: 1.0.0
 * Author: Mahbub
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Enqueue Styles and FontAwesome
function crf_enqueue_scripts() {
    // Register FontAwesome for icons
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    
    // Register the custom CSS file
    wp_enqueue_style('crf-styles', plugins_url('style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'crf_enqueue_scripts');

// Shortcode function
function crf_registration_form_shortcode() {
    ob_start(); // Start output buffering
    ?>
    <div class="registration-card">
        <div class="stepper">
            <div class="step active"><div class="circle">1</div><div class="label">Contacts and credentials</div></div>
            <div class="step"><div class="circle">2</div><div class="label">Terms and conditions</div></div>
            <div class="step"><div class="circle">3</div><div class="label">Email confirmation</div></div>
            <div class="step"><div class="circle">4</div><div class="label">Phone confirmation</div></div>
        </div>

        <form class="form-content" method="post">
            <div class="input-group">
                <label>Sponsor ID</label>
                <input type="text" name="sponsor_id">
            </div>
            <div class="input-group">
                <label>First name <span class="required">*</span></label>
                <input type="text" name="first_name" required>
            </div>
            <div class="input-group">
                <label>Last name <span class="required">*</span></label>
                <input type="text" name="last_name" required>
            </div>
            <div class="input-group">
                <label>E-mail <span class="required">*</span></label>
                <input type="email" name="user_email" required>
            </div>
            <div class="input-group">
                <label>Phone <span class="required">*</span></label>
                <div class="phone-input">
                    <div class="flag-select">
                        <img src="https://flagcdn.com/w20/bd.png" alt="BD Flag">
                        <i class="fas fa-caret-down"></i>
                    </div>
                    <input type="tel" name="phone" required>
                </div>
            </div>
            <div class="input-group">
                <label>Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="pwd" required>
                    <i class="fas fa-eye-slash toggle-password"></i>
                </div>
            </div>
            <div class="input-group">
                <label>Password confirm <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="pwd_confirm" required>
                    <i class="fas fa-eye-slash toggle-password"></i>
                </div>
            </div>
            <div class="button-container">
                <button type="submit" class="next-btn">NEXT</button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean(); // Return the buffered content
}
add_shortcode('crf_shortcode', 'crf_registration_form_shortcode');