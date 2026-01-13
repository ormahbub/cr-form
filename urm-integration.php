<?php
/**
 * URMembership Integration for Multi-Step Registration Form Pro
 */

if (!defined('ABSPATH')) exit;

class URMembership_Integration {
    
    /**
     * Initialize integration
     */
    public static function init() {
        // Check if URMembership tables exist on plugin activation
        register_activation_hook(__FILE__, array(__CLASS__, 'check_urm_tables'));
    }
    
    /**
     * Get URMembership table names
     */
    private static function get_urm_tables() {
        global $wpdb;
        
        return array(
            'subscriptions' => $wpdb->prefix . 'ur_membership_subscriptions',
            'orders' => $wpdb->prefix . 'ur_membership_orders',
            'orders_meta' => $wpdb->prefix . 'ur_membership_ordermeta',
        );
    }
    
    /**
     * Check if URMembership tables exist
     */
    public static function tables_exist() {
        $tables = self::get_urm_tables();
        global $wpdb;
        
        foreach ($tables as $table) {
            $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($result !== $table) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Create URMembership subscription for a user
     */
    public static function create_subscription($user_id, $membership_data = array()) {
        global $wpdb;
        
        if (!self::tables_exist()) {
            return false;
        }
        
        $tables = self::get_urm_tables();
        
        $default_subscription = array(
            'item_id' => isset($membership_data['plan_id']) ? intval($membership_data['plan_id']) : 1,
            'user_id' => intval($user_id),
            'start_date' => current_time('mysql'),
            'expiry_date' => isset($membership_data['expiry_date']) ? $membership_data['expiry_date'] : date('Y-m-d H:i:s', strtotime('+1 year')),
            'next_billing_date' => isset($membership_data['next_billing_date']) ? $membership_data['next_billing_date'] : NULL,
            'billing_cycle' => isset($membership_data['billing_cycle']) ? $membership_data['billing_cycle'] : 'month',
            'trial_start_date' => NULL,
            'trial_end_date' => NULL,
            'billing_amount' => isset($membership_data['amount']) ? floatval($membership_data['amount']) : 0.00,
            'cancel_sub' => 'immediately',
            'status' => isset($membership_data['status']) ? $membership_data['status'] : 'active',
            'coupon' => isset($membership_data['coupon']) ? sanitize_text_field($membership_data['coupon']) : NULL,
            'subscription_id' => self::generate_subscription_id($user_id),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Merge with provided data
        $subscription_data = wp_parse_args($membership_data, $default_subscription);
        
        // Insert into subscriptions table
        $result = $wpdb->insert(
            $tables['subscriptions'],
            $subscription_data,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('URMembership Subscription Error: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Create order in URMembership
     */
    public static function create_order($user_id, $subscription_id, $order_data = array()) {
        global $wpdb;
        
        if (!self::tables_exist()) {
            return false;
        }
        
        $tables = self::get_urm_tables();
        
        $default_order = array(
            'item_id' => isset($order_data['plan_id']) ? intval($order_data['plan_id']) : 1,
            'user_id' => intval($user_id),
            'subscription_id' => intval($subscription_id),
            'created_by' => intval($user_id),
            'transaction_id' => self::generate_transaction_id(),
            'payment_method' => isset($order_data['payment_method']) ? $order_data['payment_method'] : 'free',
            'total_amount' => isset($order_data['amount']) ? floatval($order_data['amount']) : 0.00,
            'status' => 'completed',
            'order_type' => (isset($order_data['amount']) && $order_data['amount'] > 0) ? 'paid' : 'free',
            'trial_status' => 'off',
            'notes' => 'Registration via Multi-Step Registration Form',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $order_data = wp_parse_args($order_data, $default_order);
        
        $result = $wpdb->insert(
            $tables['orders'],
            $order_data,
            array('%d', '%d', '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('URMembership Order Error: ' . $wpdb->last_error);
            return false;
        }
        
        $order_id = $wpdb->insert_id;
        
        // Add order meta if provided
        if ($order_id && isset($order_data['meta']) && is_array($order_data['meta'])) {
            foreach ($order_data['meta'] as $key => $value) {
                $wpdb->insert(
                    $tables['orders_meta'],
                    array(
                        'order_id' => $order_id,
                        'meta_key' => sanitize_key($key),
                        'meta_value' => maybe_serialize($value)
                    ),
                    array('%d', '%s', '%s')
                );
            }
        }
        
        return $order_id;
    }
    
    /**
     * Complete URMembership registration for a user
     */
    public static function complete_user_registration($user_id, $plan_id = 1, $amount = 0) {
        $membership_data = array(
            'plan_id' => $plan_id,
            'amount' => $amount,
            'billing_cycle' => 'month',
            'status' => 'active'
        );
        
        // Create subscription
        $subscription_id = self::create_subscription($user_id, $membership_data);
        
        if (!$subscription_id) {
            return false;
        }
        
        // Create order
        $order_data = array(
            'plan_id' => $plan_id,
            'amount' => $amount,
            'payment_method' => 'free'
        );
        
        $order_id = self::create_order($user_id, $subscription_id, $order_data);
        
        return array(
            'subscription_id' => $subscription_id,
            'order_id' => $order_id
        );
    }
    
    /**
     * Check if user exists in URMembership
     */
    public static function user_exists_in_urm($user_id) {
        global $wpdb;
        
        if (!self::tables_exist()) {
            return false;
        }
        
        $tables = self::get_urm_tables();
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['subscriptions']} WHERE user_id = %d",
            $user_id
        ));
        
        return $exists > 0;
    }
    
    /**
     * Helper: Generate subscription ID
     */
    private static function generate_subscription_id($user_id) {
        return 'SUB-' . $user_id . '-' . time() . '-' . wp_rand(1000, 9999);
    }
    
    /**
     * Helper: Generate transaction ID
     */
    private static function generate_transaction_id() {
        return 'TXN-' . strtoupper(wp_generate_password(12, false)) . '-' . time();
    }
    
    /**
     * Check URMembership tables on activation
     */
    public static function check_urm_tables() {
        if (!self::tables_exist()) {
            // Log warning but don't prevent activation
            error_log('URMembership tables not found. Integration may not work.');
        }
    }
}