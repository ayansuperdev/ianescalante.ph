<div class="admin-section">
    <h2><i class="fas fa-users-cog"></i> User Management</h2>
    
    <div class="search-container">
        <div class="search-bar">
            <input type="text" id="userSearch" placeholder="Search users..." 
                   autocomplete="off" aria-label="Search users">
            <button class="btn-search" id="searchButton"><i class="fas fa-search"></i></button>
        </div>
        <div id="searchResults" class="search-results-dropdown"></div>
    </div>
    
    <div class="users-table">
        <table id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php 
                $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                while ($user = $stmt->fetch()): 
                ?>
                <tr data-id="<?= $user['id'] ?>" 
                    data-username="<?= htmlspecialchars($user['username']) ?>"
                    data-email="<?= htmlspecialchars($user['email']) ?>">
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <form method="POST" class="role-form">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="new_role" class="role-select">
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            </select>
                            <button type="submit" name="update_role" class="btn-update">Update</button>
                        </form>
                    </td>
                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <a href="?page=users&action=edit&id=<?= $user['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                        <form method="POST" onsubmit="return confirm('Are you sure?')" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" class="btn-delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    const searchResults = document.getElementById('searchResults');
    const usersTableBody = document.getElementById('usersTableBody');
    const rows = Array.from(usersTableBody.querySelectorAll('tr'));
    
    // Store original table rows
    const originalRows = Array.from(rows);
    
    // Debounce function to limit API calls
    function debounce(func, delay) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }
    
    // Perform search
    function performSearch(query) {
        // Clear previous results
        searchResults.innerHTML = '';
        searchResults.style.display = 'none';
        
        // If query is empty, show all rows
        if (!query.trim()) {
            usersTableBody.innerHTML = '';
            originalRows.forEach(row => usersTableBody.appendChild(row.cloneNode(true)));
            return;
        }
        
        // Filter rows
        const filteredRows = originalRows.filter(row => {
            const username = row.dataset.username.toLowerCase();
            const email = row.dataset.email.toLowerCase();
            const searchTerm = query.toLowerCase();
            
            return username.includes(searchTerm) || email.includes(searchTerm);
        });
        
        // Update table
        usersTableBody.innerHTML = '';
        filteredRows.forEach(row => usersTableBody.appendChild(row.cloneNode(true)));
    }
    
    // Fetch suggestions
    function fetchSuggestions(query) {
        if (!query.trim()) {
            searchResults.style.display = 'none';
            return;
        }
        
        // Filter users for suggestions
        const suggestions = originalRows.filter(row => {
            const username = row.dataset.username.toLowerCase();
            const email = row.dataset.email.toLowerCase();
            const searchTerm = query.toLowerCase();
            
            return username.includes(searchTerm) || email.includes(searchTerm);
        }).slice(0, 5); // Limit to 5 suggestions
        
        if (suggestions.length === 0) {
            searchResults.innerHTML = '<div class="search-no-results">No matching users found</div>';
            searchResults.style.display = 'block';
            return;
        }
        
        // Display suggestions
        searchResults.innerHTML = '';
        suggestions.forEach(row => {
            const id = row.dataset.id;
            const username = row.dataset.username;
            const email = row.dataset.email;
            
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'search-suggestion';
            suggestionItem.innerHTML = `
                <div class="suggestion-username">${username}</div>
                <div class="suggestion-email">${email}</div>
            `;
            
            suggestionItem.addEventListener('click', () => {
                searchInput.value = username;
                performSearch(username);
                searchResults.style.display = 'none';
            });
            
            searchResults.appendChild(suggestionItem);
        });
        
        searchResults.style.display = 'block';
    }
    
    // Event listeners
    searchInput.addEventListener('input', debounce(function() {
        const query = this.value;
        fetchSuggestions(query);
    }, 300));
    
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            performSearch(this.value);
            searchResults.style.display = 'none';
        }
    });
    
    document.getElementById('searchButton').addEventListener('click', () => {
        performSearch(searchInput.value);
        searchResults.style.display = 'none';
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            searchResults.style.display = 'none';
        }
    });
    
    // Initialize table with all users
    performSearch('');
});
</script>
