<?php
$title = 'Direct Messages';
require __DIR__ . '/../partials/header.php';
$me = auth_user();
$myId = (int)current_user_id();
$selectedId = $selectedUser ? (int)$selectedUser['id'] : 0;
?>
<main id="main-content" class="container messages-container">

<div class="chat-shell card">
  <!-- Left Column: Conversations Sidebar -->
  <aside class="chat-sidebar">
    <div class="chat-sidebar-header">
      <h2>Messages</h2>
      <button type="button" class="mini" id="new-chat-toggle-btn" title="Start new conversation">+ New</button>
    </div>

    <!-- Quick search / filter -->
    <div class="chat-search-wrap">
      <input type="text" id="chat-filter-input" placeholder="Search conversations..." aria-label="Search conversations">
    </div>

    <!-- New Chat Contacts Picker (hidden by default) -->
    <div id="new-chat-picker" class="new-chat-picker" style="display: none;">
      <div class="picker-header">
        <small><strong>Start a chat with:</strong></small>
        <button type="button" class="mini danger-text" id="close-picker-btn">✕</button>
      </div>
      <div class="picker-list">
        <?php foreach ($allContacts as $contact): ?>
          <a href="index.php?page=messages&user_id=<?= (int)$contact['id'] ?>" class="picker-item">
            <?= avatar_markup($contact, 'avatar small') ?>
            <span>
              <strong><?= e($contact['full_name']) ?></strong>
              <small class="muted">@<?= e($contact['username']) ?></small>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Existing Conversation List -->
    <div class="conversations-list" id="conversations-list">
      <?php if (empty($conversations)): ?>
        <div class="chat-empty-sidebar muted">
          <p>No active conversations yet.</p>
          <small>Click "+ New" above to message a classmate!</small>
        </div>
      <?php else: ?>
        <?php foreach ($conversations as $conv): ?>
          <?php 
            $convId = (int)$conv['id'];
            $isActive = ($selectedId === $convId);
            $unread = (int)($conv['unread_count'] ?? 0);
          ?>
          <a href="index.php?page=messages&user_id=<?= $convId ?>" class="conv-item <?= $isActive ? 'active' : '' ?>" data-name="<?= strtolower(e($conv['full_name'] . ' ' . $conv['username'])) ?>">
            <?= avatar_markup($conv, 'avatar small') ?>
            <div class="conv-info">
              <div class="conv-top">
                <strong class="conv-name"><?= e($conv['full_name']) ?></strong>
                <span class="conv-time muted"><?= time_ago($conv['last_message_at']) ?></span>
              </div>
              <div class="conv-preview muted">
                <?= (int)$conv['last_sender_id'] === $myId ? '<span class="you-prefix">You: </span>' : '' ?>
                <?= e(mb_strimwidth($conv['last_message'] ?? '', 0, 36, '...')) ?>
              </div>
            </div>
            <?php if ($unread > 0): ?>
              <span class="badge-pill conv-unread-badge"><?= $unread ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </aside>

  <!-- Right Column: Active Chat Area -->
  <section class="chat-main">
    <?php if ($selectedUser): ?>
      <!-- Chat Header -->
      <div class="chat-header">
        <a href="index.php?page=profile&id=<?= $selectedId ?>" class="chat-header-user">
          <?= avatar_markup($selectedUser, 'avatar small') ?>
          <div>
            <strong><?= e($selectedUser['full_name']) ?></strong>
            <small class="muted">@<?= e($selectedUser['username']) ?> · View Profile</small>
          </div>
        </a>
      </div>

      <!-- Messages Thread -->
      <div class="chat-messages" id="chat-messages" data-user-id="<?= $selectedId ?>" data-last-id="<?= !empty($chatMessages) ? (int)end($chatMessages)['id'] : 0 ?>">
        <?php if (empty($chatMessages)): ?>
          <div class="chat-start-notice muted">
            <p>This is the start of your message history with <?= e($selectedUser['full_name']) ?>.</p>
            <small>Say hello and break the ice! 👋</small>
          </div>
        <?php else: ?>
          <?php foreach ($chatMessages as $msg): ?>
            <?php 
              $isMe = ((int)$msg['sender_id'] === $myId);
            ?>
            <div class="msg-bubble-wrap <?= $isMe ? 'msg-outgoing' : 'msg-incoming' ?>" data-msg-id="<?= (int)$msg['id'] ?>">
              <div class="msg-bubble">
                <div class="msg-text"><?= nl2br(e($msg['message'])) ?></div>
                <div class="msg-meta muted"><?= friendly_date($msg['created_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Message Composer -->
      <form method="post" action="index.php?page=message_send" class="chat-composer" id="chat-composer-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="receiver_id" value="<?= $selectedId ?>">
        <input type="text" name="message" id="chat-message-input" placeholder="Type a message..." maxlength="2000" required autocomplete="off">
        <button type="submit" class="primary" id="chat-send-btn">Send ↗</button>
      </form>

    <?php else: ?>
      <div class="chat-placeholder">
        <div class="placeholder-icon">✉</div>
        <h3>Select a conversation</h3>
        <p class="muted">Choose a classmate from the list or click "+ New" to start a direct message.</p>
      </div>
    <?php endif; ?>
  </section>
</div>

</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
