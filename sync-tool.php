<?php
/**
 * Admin sync tool for URMembership integration
 */

if (!defined('ABSPATH')) exit;

// Add admin menu
add_action('admin_menu', 'crf_add_sync_page');
function crf_add_sync_page() {
    add_users_page(
        'Sync to URMembership',
        'Sync to URMembership',
        'manage_options',
        'crf-urm-sync',
        'crf_sync_page_content'
    );
}

// Sync page content
function crf_sync_page_content() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    $sync_count = 0;
    $error_count = 0;
    
    // Handle sync action
    if (isset($_POST['crf_sync_action']) && wp_verify_nonce($_POST['crf_sync_nonce'], 'crf_sync_users')) {
        $users = get_users(array(
            'role__in' => array('subscriber', 'customer', 'member'),
            'number' => 50 // Limit for safety
        ));
        
        foreach ($users as $user) {
            if (!URMembership_Integration::user_exists_in_urm($user->ID)) {
                $result = URMembership_Integration::complete_user_registration($user->ID);
                if ($result) {
                    $sync_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        echo '<div class="notice notice-success"><p>Sync completed! ' . $sync_count . ' users synced, ' . $error_count . ' errors.</p></div>';
    }
    
    // Check URMembership tables
    $tables_exist = URMembership_Integration::tables_exist();
    ?>
    <div class="wrap">
        <h1>URMembership Sync Tool</h1>
        
        <div class="card">
            <h2>Database Status</h2>
            <p>URMembership Tables Exist: <strong><?php echo $tables_exist ? 'Yes' : 'No'; ?></strong></p>
            
            <?php if (!$tables_exist): ?>
                <div class="notice notice-error">
                    <p>URMembership tables not found. Please ensure URMembership plugin is installed and activated.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Sync Existing Users</h2>
            <p>This will sync existing WordPress users to URMembership database with a free plan.</p>
            <form method="post">
                <?php wp_nonce_field('crf_sync_users', 'crf_sync_nonce'); ?>
                <input type="hidden" name="crf_sync_action" value="1">
                <input type="submit" class="button button-primary" value="Sync Users" <?php echo !$tables_exist ? 'disabled' : ''; ?>>
            </form>
        </div>
        
        <div class="card">
            <h2>Manual Integration Check</h2>
            <form method="post">
                <?php wp_nonce_field('crf_check_user', 'crf_check_nonce'); ?>
                <p>
                    <label>User ID to check:</label>
                    <input type="number" name="user_id_to_check" min="1">
                    <input type="submit" name="crf_check_action" class="button" value="Check Integration">
                </p>
            </form>
            
            <?php
            if (isset($_POST['crf_check_action']) && wp_verify_nonce($_POST['crf_check_nonce'], 'crf_check_user')) {
                $user_id = intval($_POST['user_id_to_check']);
                if ($user_id > 0) {
                    $exists = URMembership_Integration::user_exists_in_urm($user_id);
                    echo '<p>User ' . $user_id . ' exists in URMembership: <strong>' . ($exists ? 'Yes' : 'No') . '</strong></p>';
                }
            }
            ?>
        </div>
    </div>
    
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 20px 0;
            padding: 20px;
        }
        .card h2 {
            margin-top: 0;
        }
    </style>
    <?php
}