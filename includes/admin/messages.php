<div class="admin-section">
    <h2><i class="fas fa-envelope-open-text"></i> Message Center</h2>
    
    <div class="message-filters">
        <a href="?page=messages&filter=all" class="btn-filter active">All Messages</a>
        <a href="?page=messages&filter=unread" class="btn-filter">Unread</a>
        <a href="?page=messages&filter=read" class="btn-filter">Read</a>
    </div>
    
    <div class="messages-list">
        <?php
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $query = "SELECT m.*, u1.username as sender, u2.username as receiver 
                 FROM messages m
                 JOIN users u1 ON m.sender_id = u1.id
                 JOIN users u2 ON m.receiver_id = u2.id";
        
        if ($filter === 'unread') {
            $query .= " WHERE m.is_read = FALSE";
        } elseif ($filter === 'read') {
            $query .= " WHERE m.is_read = TRUE";
        }
        
        $query .= " ORDER BY m.created_at DESC";
        
        $stmt = $pdo->query($query);
        while ($message = $stmt->fetch()):
        ?>
        <div class="message-card <?= $message['is_read'] ? 'read' : 'unread' ?>">
            <div class="message-header">
                <span class="sender">From: <?= htmlspecialchars($message['sender']) ?></span>
                <span class="receiver">To: <?= htmlspecialchars($message['receiver']) ?></span>
                <span class="date"><?= date('M j, Y g:i a', strtotime($message['created_at'])) ?></span>
            </div>
            <div class="message-subject"><?= htmlspecialchars($message['subject']) ?></div>
            <div class="message-body"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
            <div class="message-actions">
                <?php if (!$message['is_read']): ?>
                    <a href="mark_read.php?id=<?= $message['id'] ?>" class="btn-mark-read">Mark as Read</a>
                <?php endif; ?>
                <a href="delete_message.php?id=<?= $message['id'] ?>" class="btn-delete">Delete</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>