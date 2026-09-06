<div class="user-section">
    <h2><i class="fas fa-inbox"></i> Your Messages</h2>
    
    <div class="message-actions">
        <a href="?page=messages&action=compose" class="btn-compose">Compose New</a>
    </div>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'compose'): ?>
        <div class="compose-form">
            <form method="POST" action="send_message.php">
                <div class="form-group">
                    <label for="receiver">To:</label>
                    <select id="receiver" name="receiver_id" required>
                        <option value="">Select User</option>
                        <?php
                        $stmt = $pdo->query("SELECT id, username FROM users WHERE id != " . $_SESSION['user_id']);
                        while ($user = $stmt->fetch()):
                        ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <button type="submit" class="btn-send">Send Message</button>
            </form>
        </div>
    <?php else: ?>
        <div class="messages-list">
            <?php
            $stmt = $pdo->prepare("SELECT m.*, u.username as sender 
                                 FROM messages m
                                 JOIN users u ON m.sender_id = u.id
                                 WHERE m.receiver_id = ?
                                 ORDER BY m.created_at DESC");
            $stmt->execute([$_SESSION['user_id']]);
            while ($message = $stmt->fetch()):
            ?>
            <div class="message-card <?= $message['is_read'] ? 'read' : 'unread' ?>">
                <div class="message-header">
                    <span class="sender">From: <?= htmlspecialchars($message['sender']) ?></span>
                    <span class="date"><?= date('M j, Y g:i a', strtotime($message['created_at'])) ?></span>
                </div>
                <div class="message-subject">
                    <a href="?page=messages&action=view&id=<?= $message['id'] ?>">
                        <?= htmlspecialchars($message['subject']) ?>
                    </a>
                </div>
                <?php if (isset($_GET['action']) && $_GET['action'] === 'view' && $_GET['id'] == $message['id']): ?>
                    <div class="message-body"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
                    <div class="message-actions">
                        <a href="reply_message.php?id=<?= $message['id'] ?>" class="btn-reply">Reply</a>
                        <a href="delete_message.php?id=<?= $message['id'] ?>" class="btn-delete">Delete</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>