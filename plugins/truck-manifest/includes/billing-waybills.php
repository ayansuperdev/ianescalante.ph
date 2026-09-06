<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Billing_Waybills {
    public function display_billing_page() {
        global $wpdb;

        // Fetch unique roles and assigned user names for the dropdowns
        $roles = $wpdb->get_col("SELECT DISTINCT role FROM {$wpdb->prefix}assignroles");
        $user_names = $wpdb->get_col("SELECT DISTINCT user_name FROM {$wpdb->prefix}assignroles");

        // Capture selected filters
        $selected_role = isset($_POST['role_filter']) ? sanitize_text_field($_POST['role_filter']) : '';
        $selected_user = isset($_POST['user_name_filter']) ? sanitize_text_field($_POST['user_name_filter']) : '';

        ?>
        <div class="wrap">
            <h1>Billing Waybills</h1>
            <p>Filter waybills per role (Customer/Collector) or by assigned user.</p>

            <!-- Filter Form -->
            <form id="billing-filter-form" method="post">
                <label for="role_filter">Filter by Role:</label>
                <select id="role_filter" name="role_filter">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo esc_attr($role); ?>" <?php selected($selected_role, $role); ?>>
                            <?php echo esc_html($role); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="user_name_filter">Filter by User Name:</label>
                <select id="user_name_filter" name="user_name_filter">
                    <option value="">All Users</option>
                    <?php foreach ($user_names as $user_name): ?>
                        <option value="<?php echo esc_attr($user_name); ?>" <?php selected($selected_user, $user_name); ?>>
                            <?php echo esc_html($user_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="button button-secondary">Filter</button>
            </form>

            <?php
            // Build the query dynamically based on filters
            $where_conditions = ["(tm.status = 'Delivered' OR tm.status = 'Uncollected')", "tm.collect > 0", "tm.prepaid = 0"];
            $query_params = [];

            if (!empty($selected_role)) {
                $where_conditions[] = "ar.role = %s";
                $query_params[] = $selected_role;
            }

            if (!empty($selected_user)) {
                $where_conditions[] = "ar.user_name = %s";
                $query_params[] = $selected_user;
            }

            // Construct the WHERE clause
            $where_clause = implode(" AND ", $where_conditions);

            // Fetch filtered results
            $query = $wpdb->prepare(
                "SELECT tm.*, ar.user_name, ar.role, bn.billing_number 
                 FROM {$wpdb->prefix}truck_manifest tm
                 LEFT JOIN {$wpdb->prefix}assignroles ar 
                 ON tm.role_id = ar.id
                 LEFT JOIN {$wpdb->prefix}billing_numbers bn ON FIND_IN_SET(tm.id, bn.waybill_ids) > 0
                 WHERE $where_clause",
                $query_params
            );

            $results = $wpdb->get_results($query);

            if (empty($results)) {
                echo '<p>No waybills found for the selected filters.</p>';
            } else {
                ?>
                <!-- Table -->
                <table id="waybills-table" class="widefat striped">
                    <thead>
                        <tr>
                            <th>Date Delivered</th>
                            <th>Waybill No</th>
                            <th>Consignee</th>
                            <th>Consignor</th>
                            <th>Description</th>
                            <th>Collect</th>
                            <th>Prepaid</th>
                            <th>Status</th>
                            <th>Assigned User</th>
                            <th>Billing Number</th>
                            <th>Select<input type="checkbox" id="select-all"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row->delivery_date); ?></td>
                                <td><?php echo esc_html($row->waybill_no); ?></td>
                                <td><?php echo esc_html($row->consignee); ?></td>
                                <td><?php echo esc_html($row->consignor); ?></td>
                                <td><?php echo esc_html($row->descriptions); ?></td>
                                <td><?php echo esc_html(number_format($row->collect, 2)); ?></td>
                                <td><?php echo esc_html(number_format($row->prepaid, 2)); ?></td>
                                <td><?php echo esc_html($row->status); ?></td>
                                <td>
                                    <?php 
                                    if ($row->user_name) {
                                        echo esc_html($row->user_name . ' (' . $row->role . ')');
                                    } else {
                                        echo 'Unassigned';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($row->billing_number ?? '-'); ?></td>
                                <td>
                                    <input type="checkbox" class="waybill-checkbox" name="selected_waybills[]" value="<?php echo esc_attr($row->id); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2>BILLING STATEMENT</h2>
                <p>Generate your selected waybills for PDF Preview.</p>
                    <button type="button" id="generate-bill-btn" disabled>Generate Billing</button>
                                        <!-- Generate Bill Button -->
                                        <!-- <button id="generate-bill-btn" >Generate Bill</button>-->

                                        <div id="pdf-preview-container" style="display:none;">
                                            <h3>PDF Preview</h3>
                                            <iframe id="pdf-preview" style="width: 100%; height: 600px;" frameborder="0"></iframe>
                                        </div>

                                                
                    <?php
                }

                // Handle billing generation
                if (isset($_POST['generate-bill-btn']) && !empty($_POST['selected_waybills'])) {
                    $selected_waybills = array_map('intval', $_POST['selected_waybills']);
                
                    // Verify all selected waybills exist
                    $valid_waybills = $wpdb->get_results($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}truck_manifest WHERE id IN (" . implode(',', $selected_waybills) . ")"
                    ));
                
                    if (count($valid_waybills) !== count($selected_waybills)) {
                        echo '<p>Error: Invalid waybills selected.</p>';
                        return;
                    }
                
                    $billing_number = 'BILL-' . time();
                    foreach ($selected_waybills as $waybill_id) {
                        $wpdb->update(
                            "{$wpdb->prefix}truck_manifest",
                            ['billing_number' => $billing_number],
                            ['id' => $waybill_id],
                            ['%s'],
                            ['%d']
                        );
                    }
                
                    echo '<p>Billing number ' . esc_html($billing_number) . ' has been assigned.</p>';
                }
                
                ?>
            </form>
        </div>
        <?php
    }
}
