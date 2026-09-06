jQuery(document).ready(function ($) {
    // Save a new role
    $('#save-role').on('click', function () {
        const userName = $('#user_name').val();
        const contactNumber = $('#contact_number').val();
        const email = $('#email').val();
        const role = $('#role').val();

        if (!userName || !contactNumber || !email || !role) {
            alert('All fields are required!');
            return;
        }

        $.post(assignRolesParams.ajax_url, {
            action: 'save_role',
            user_name: userName,
            contact_number: contactNumber,
            email,
            role
        }, function (response) {
            if (response.success) {
                alert('Role saved successfully!');
                loadRoles();
                $('#assign-roles-form')[0].reset(); // Clear the form
            } else {
                alert('Error saving role.');
            }
        });
    });

    // Load roles
    function loadRoles() {
        console.log('Loading roles...');
    
        $.post(assignRolesParams.ajax_url, {
            action: 'load_roles'
        }, function (response) {
            console.log('AJAX response:', response);
    
            if (response.success) {
                const tableBody = $('#assigned-roles-table tbody');
                tableBody.empty(); // Clear the table before appending
    
                response.data.forEach(row => {
                    tableBody.append(`
                        <tr>
                            <td>${row.id}</td>
                            <td>${row.user_name}</td>
                            <td>${row.contact_number}</td>
                            <td>${row.email}</td>
                            <td>${row.role}</td>
                            <td>
                                <button class="edit-role button">Edit</button>
                                <button class="delete-role button">Delete</button>
                            </td>
                        </tr>
                    `);
                });
            } else {
                alert('No roles found!');
            }
        }).fail(function () {
            alert('Failed to load roles. Please try again.');
        });
    }
    
    

    // Delete role
    $(document).on('click', '.delete-role', function () {
        const row = $(this).closest('tr');
        const id = row.data('id');

        if (confirm('Are you sure you want to delete this role?')) {
            $.post(assignRolesParams.ajax_url, {
                action: 'delete_role',
                id
            }, function (response) {
                if (response.success) {
                    alert('Role deleted successfully!');
                    loadRoles();
                } else {
                    alert('Error deleting role.');
                }
            });
        }
    });

    // Load roles on page load
    loadRoles();
});
