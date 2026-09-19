<?php
// ============================================================
//  Arandia College eLMS — EduBot AI Chatbot Include
//  File: includes/chatbot.php
//  HISTORY: Dropdown overlay from the header History button.
//           Fits the existing single-column panel layout.
// ============================================================

if (!isset($role))
    $role = $_SESSION['role'] ?? 'Student';
if (!isset($user_id))
    $user_id = (int) ($_SESSION['user_id'] ?? 0);

$botColor = $role === 'Teacher' ? '#003087' : ($role === 'Admin' ? '#7b2cbf' : '#003087');
$botColorLt = $role === 'Teacher' ? '#e6fff4' : ($role === 'Admin' ? '#f5eeff' : '#e8f0ff');
$botName = 'EduBot';
$storageKey = "edubot_history_{$user_id}_{$role}";
?>
<style>
    /* ── FAB ──────────────────────────────────────────────────── */
    .chat-fab {
        position: fixed;
        bottom: 1.75rem;
        right: 1.75rem;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: <?= $botColor ?>;
        color: white;
        border: none;
        cursor: pointer;
        font-size: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .25);
        z-index: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform .2s, box-shadow .2s
    }

    .chat-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 28px rgba(0, 0, 0, .3)
    }

    .chat-fab-badge {
        position: absolute;
        top: -3px;
        right: -3px;
        background: #e74c3c;
        color: white;
        font-size: .6rem;
        font-weight: 800;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid white
    }

    .chat-fab-badge.show {
        display: flex
    }

    /* ── FLOATING WINDOW ─────────────────────────────────────── */
    .chat-window {
        position: fixed;
        bottom: 5.5rem;
        right: 1.75rem;
        width: 370px;
        max-height: 520px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, .18);
        z-index: 800;
        display: none;
        flex-direction: column;
        overflow: hidden
    }

    .chat-window.open {
        display: flex
    }

    .chat-win-head {
        background: <?= $botColor ?>;
        color: white;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: .75rem
    }

    .chat-bot-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0
    }

    .chat-bot-info strong {
        display: block;
        font-family: 'Nunito', sans-serif;
        font-size: .92rem;
        font-weight: 900
    }

    .chat-bot-info span {
        font-size: .7rem;
        opacity: .8
    }

    .chat-win-close {
        margin-left: auto;
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        opacity: .8
    }

    .chat-win-close:hover {
        opacity: 1
    }

    .chat-expand-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        opacity: .8;
        margin-right: .25rem
    }

    .chat-expand-btn:hover {
        opacity: 1
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: .65rem;
        background: #f8f9fc
    }

    /* ── MESSAGES ────────────────────────────────────────────── */
    .chat-msg {
        max-width: 82%;
        padding: .65rem .9rem;
        border-radius: 14px;
        font-size: .82rem;
        line-height: 1.55;
        word-break: break-word
    }

    .chat-msg.bot {
        background: white;
        color: #1a1a2e;
        border-radius: 4px 14px 14px 14px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
        align-self: flex-start
    }

    .chat-msg.user {
        background: <?= $botColor ?>;
        color: white;
        border-radius: 14px 4px 14px 14px;
        align-self: flex-end
    }

    .chat-msg.typing {
        background: white;
        color: #aaa;
        font-style: italic;
        align-self: flex-start;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .08)
    }

    .chat-msg-time {
        font-size: .62rem;
        opacity: .55;
        margin-top: .2rem;
        text-align: right
    }

    /* ── DATE DIVIDER ────────────────────────────────────────── */
    .chat-date-divider {
        text-align: center;
        font-size: .68rem;
        color: #aaa;
        font-weight: 600;
        margin: .4rem 0;
        display: flex;
        align-items: center;
        gap: .5rem
    }

    .chat-date-divider::before,
    .chat-date-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e8eaf0
    }

    /* ── QUICK PROMPTS ───────────────────────────────────────── */
    .chat-quick {
        padding: .6rem 1rem;
        display: flex;
        gap: .4rem;
        flex-wrap: wrap;
        border-top: 1px solid #f0f2f5;
        background: white
    }

    .chat-quick-btn {
        font-size: .72rem;
        font-weight: 600;
        padding: .3rem .7rem;
        border-radius: 100px;
        border: 1.5px solid
            <?= $botColor ?>
        ;
        color: <?= $botColor ?>;
        background: <?= $botColorLt ?>;
        cursor: pointer;
        transition: all .2s;
        white-space: nowrap
    }

    .chat-quick-btn:hover {
        background: <?= $botColor ?>;
        color: white
    }

    .chat-input-wrap {
        padding: .75rem 1rem;
        border-top: 1px solid #f0f2f5;
        display: flex;
        gap: .5rem;
        background: white
    }

    .chat-input {
        flex: 1;
        padding: .55rem .85rem;
        border: 1.5px solid #e0e4ee;
        border-radius: 100px;
        font-size: .83rem;
        outline: none;
        font-family: 'Open Sans', sans-serif;
        transition: border-color .2s
    }

    .chat-input:focus {
        border-color: <?= $botColor ?>
    }

    .chat-send-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: <?= $botColor ?>;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0
    }

    .chat-send-btn:hover {
        opacity: .85
    }

    .chat-send-btn:disabled {
        opacity: .4;
        cursor: not-allowed
    }

    /* ── FULL PANEL ───────────────────────────────────────────── */
    .chat-panel-wrap {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 220px);
        min-height: 400px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, .07);
        overflow: visible;
        position: relative
    }

    /* ── PANEL HEADER ────────────────────────────────────────── */
    .chat-panel-head {
        background: <?= $botColor ?>;
        color: white;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        border-radius: 16px 16px 0 0;
        flex-shrink: 0;
        position: relative;
        z-index: 10;
        flex-wrap: wrap
    }

    .chat-panel-head strong {
        font-family: 'Nunito', sans-serif;
        font-size: 1rem;
        font-weight: 900
    }

    .chat-panel-head span {
        font-size: .75rem;
        opacity: .8
    }

    .chat-hist-btn {
        background: rgba(255, 255, 255, .15);
        border: 1.5px solid rgba(255, 255, 255, .4);
        color: white;
        font-size: .73rem;
        font-weight: 700;
        padding: .3rem .85rem;
        border-radius: 100px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: .35rem;
        transition: all .2s
    }

    .chat-hist-btn:hover,
    .chat-hist-btn.active {
        background: rgba(255, 255, 255, .3)
    }

    .chat-hist-count {
        background: rgba(255, 255, 255, .3);
        border-radius: 100px;
        padding: .05rem .45rem;
        font-size: .65rem;
        font-weight: 800;
        min-width: 16px;
        text-align: center
    }

    .chat-new-btn {
        background: rgba(255, 255, 255, .15);
        border: 1.5px solid rgba(255, 255, 255, .4);
        color: white;
        font-size: .73rem;
        font-weight: 700;
        padding: .3rem .85rem;
        border-radius: 100px;
        cursor: pointer;
        transition: all .2s
    }

    .chat-new-btn:hover {
        background: rgba(255, 255, 255, .25)
    }

    .chat-clear-btn {
        background: none;
        border: 1.5px solid rgba(255, 255, 255, .4);
        color: white;
        font-size: .72rem;
        font-weight: 700;
        padding: .25rem .75rem;
        border-radius: 100px;
        cursor: pointer;
        margin-left: auto
    }

    .chat-clear-btn:hover {
        background: rgba(255, 255, 255, .2)
    }

    .chat-session-title {
        font-size: .8rem;
        color: rgba(255, 255, 255, .9);
        background: none;
        border: none;
        outline: none;
        font-family: 'Open Sans', sans-serif;
        cursor: text;
        border-bottom: 1px dashed rgba(255, 255, 255, .4);
        min-width: 60px;
        max-width: 180px
    }

    .chat-session-title:focus {
        border-bottom-color: white;
        color: white
    }

    /* ── HISTORY DROPDOWN ────────────────────────────────────── */
    .chat-history-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        width: 310px;
        max-height: 400px;
        background: white;
        border-radius: 14px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, .18);
        border: 1px solid #e8eaf0;
        display: none;
        flex-direction: column;
        z-index: 500;
        overflow: hidden
    }

    .chat-history-dropdown.open {
        display: flex;
        animation: hdrop .15s ease
    }

    @keyframes hdrop {
        from {
            opacity: 0;
            transform: translateY(-6px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    .chat-hd-head {
        padding: .75rem 1rem;
        background: <?= $botColor ?>;
        color: white;
        font-size: .78rem;
        font-weight: 800;
        font-family: 'Nunito', sans-serif;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0
    }

    .chat-hd-head button {
        background: none;
        border: 1px solid rgba(255, 255, 255, .4);
        color: white;
        font-size: .65rem;
        font-weight: 700;
        padding: .2rem .6rem;
        border-radius: 100px;
        cursor: pointer;
        margin-left: .35rem
    }

    .chat-hd-head button:hover {
        background: rgba(255, 255, 255, .2)
    }

    .chat-hd-search {
        padding: .6rem .75rem;
        border-bottom: 1px solid #f0f2f5;
        flex-shrink: 0
    }

    .chat-hd-search input {
        width: 100%;
        padding: .4rem .75rem;
        border: 1.5px solid #e0e4ee;
        border-radius: 100px;
        font-size: .78rem;
        outline: none;
        font-family: 'Open Sans', sans-serif;
        box-sizing: border-box
    }

    .chat-hd-search input:focus {
        border-color: <?= $botColor ?>
    }

    .chat-hd-list {
        flex: 1;
        overflow-y: auto;
        padding: .5rem;
        display: flex;
        flex-direction: column;
        gap: .2rem
    }

    .chat-hd-group-label {
        font-size: .62rem;
        font-weight: 800;
        color: #aaa;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: .4rem .6rem .1rem
    }

    .chat-hd-item {
        padding: .55rem .8rem;
        border-radius: 10px;
        cursor: pointer;
        border: 1.5px solid transparent;
        transition: all .15s;
        display: flex;
        align-items: flex-start;
        gap: .5rem
    }

    .chat-hd-item:hover {
        background: <?= $botColorLt ?>;
        border-color: <?= $botColor ?>
    }

    .chat-hd-item.active {
        background: <?= $botColorLt ?>;
        border-color: <?= $botColor ?>
    }

    .chat-hd-item-body {
        flex: 1;
        min-width: 0
    }

    .chat-hd-item-title {
        font-size: .78rem;
        font-weight: 700;
        color: #1a1a2e;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis
    }

    .chat-hd-item-meta {
        font-size: .65rem;
        color: #999;
        margin-top: .1rem
    }

    .chat-hd-item-del {
        color: #ddd;
        font-size: .8rem;
        cursor: pointer;
        padding: .1rem .25rem;
        border-radius: 5px;
        flex-shrink: 0;
        margin-top: .05rem
    }

    .chat-hd-item-del:hover {
        color: #e74c3c;
        background: #fff0f0
    }

    .chat-hd-empty {
        text-align: center;
        color: #bbb;
        font-size: .75rem;
        padding: 2rem 1rem;
        line-height: 1.7
    }

    /* ── MESSAGES + INPUTS ───────────────────────────────────── */
    .chat-panel-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: .65rem;
        background: #f8f9fc
    }

    .chat-panel-quick {
        padding: .75rem 1.25rem;
        display: flex;
        gap: .4rem;
        flex-wrap: wrap;
        border-top: 1px solid #f0f2f5;
        background: white;
        flex-shrink: 0
    }

    .chat-panel-input {
        padding: 1rem 1.25rem;
        border-top: 1px solid #f0f2f5;
        display: flex;
        gap: .5rem;
        background: white;
        flex-shrink: 0;
        border-radius: 0 0 16px 16px
    }

    .chat-panel-input textarea {
        flex: 1;
        padding: .65rem .9rem;
        border: 1.5px solid #e0e4ee;
        border-radius: 12px;
        font-size: .83rem;
        outline: none;
        font-family: 'Open Sans', sans-serif;
        resize: none;
        height: 70px;
        transition: border-color .2s;
        line-height: 1.4
    }

    .chat-panel-input textarea:focus {
        border-color: <?= $botColor ?>
    }

    .chat-panel-send {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: <?= $botColor ?>;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        align-self: flex-end
    }

    .chat-panel-send:hover {
        opacity: .85
    }

    .chat-panel-send:disabled {
        opacity: .4;
        cursor: not-allowed
    }
</style>

<!-- ── FAB ──────────────────────────────────────────────────────── -->
<button class="chat-fab" id="chatFab" onclick="toggleChatWindow()" title="Ask EduBot">
    🤖
    <span class="chat-fab-badge" id="chatBadge">1</span>
</button>

<!-- ── FLOATING WINDOW ──────────────────────────────────────────── -->
<div class="chat-window" id="chatWindow">
    <div class="chat-win-head">
        <div class="chat-bot-avatar">🤖</div>
        <div class="chat-bot-info">
            <strong><?= $botName ?></strong>
            <span>AI Study Assistant · Online</span>
        </div>
        <button class="chat-expand-btn" onclick="goToFullChat()" title="Open full chat">⛶</button>
        <button class="chat-win-close" onclick="toggleChatWindow()">✕</button>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div class="chat-msg bot">
            Hi <?= htmlspecialchars($_SESSION['first_name'] ?? 'there') ?>! 👋 I'm <strong><?= $botName ?></strong>,
            your AI study assistant. How can I help you today?
            <div class="chat-msg-time"><?= date('h:i A') ?></div>
        </div>
    </div>
    <div class="chat-quick" id="chatQuickWin">
        <?php
        $quickPrompts = $role === 'Student'
            ? ['Explain a topic', 'Help with Math', 'Science question', 'What is ABM?']
            : ($role === 'Teacher'
                ? ['Create quiz questions', 'Lesson plan ideas', 'Explain a concept', 'Classroom tips']
                : ['System help', 'User management', 'Report issue', 'Best practices']);
        foreach ($quickPrompts as $qp): ?>
            <button class="chat-quick-btn"
                onclick="sendQuick('<?= htmlspecialchars(addslashes($qp)) ?>')"><?= htmlspecialchars($qp) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="chat-input-wrap">
        <input class="chat-input" type="text" id="chatInputWin" placeholder="Ask anything..."
            onkeydown="if(event.key==='Enter')sendChatWin()">
        <button class="chat-send-btn" id="chatSendWin" onclick="sendChatWin()">➤</button>
    </div>
</div>

<!-- ── FULL CHAT PANEL ───────────────────────────────────────────── -->
<div id="panel-chatbot" class="section-panel">
    <div style="margin-bottom:1.5rem">
        <div class="page-title">🤖 EduBot — AI Assistant</div>
        <div class="page-sub">Ask anything about your subjects, homework, or the school system.</div>
    </div>

    <div class="chat-panel-wrap">

        <!-- HEADER -->
        <div class="chat-panel-head">
            <div style="background:rgba(255,255,255,.25);width:38px;height:38px;border-radius:50%;
                        display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">🤖</div>
            <div style="flex-shrink:0">
                <strong><?= $botName ?></strong><br>
                <span>Powered by Groq AI · Always here to help</span>
            </div>

            <!-- History button -->
            <button class="chat-hist-btn" id="histToggleBtn" onclick="toggleHistoryDropdown()">
                📚 History <span class="chat-hist-count" id="histCount">0</span>
            </button>

            <!-- New Chat -->
            <button class="chat-new-btn" onclick="startNewSession()">＋ New Chat</button>

            <!-- Editable session title -->
            <input class="chat-session-title" id="sessionTitleInput" value="New Chat" title="Click to rename"
                onchange="renameCurrentSession(this.value)" onblur="renameCurrentSession(this.value)">

            <!-- Clear -->
            <button class="chat-clear-btn" onclick="clearCurrentSession()">🗑 Clear</button>

            <!-- HISTORY DROPDOWN (child of header so it inherits z-index stacking) -->
            <div class="chat-history-dropdown" id="historyDropdown">
                <div class="chat-hd-head">
                    📚 Chat History
                    <div>
                        <button onclick="clearAllHistory()">🗑 Clear All</button>
                        <button onclick="closeHistoryDropdown()">✕</button>
                    </div>
                </div>
                <div class="chat-hd-search">
                    <input type="text" id="histSearchInput" placeholder="🔍 Search chats…"
                        oninput="renderHistoryList(this.value)">
                </div>
                <div class="chat-hd-list" id="historyList"></div>
            </div>
        </div>

        <!-- MESSAGES -->
        <div class="chat-panel-messages" id="chatPanelMessages"></div>

        <!-- QUICK PROMPTS -->
        <div class="chat-panel-quick">
            <?php
            $fullPrompts = $role === 'Student'
                ? ['Explain photosynthesis', 'What is a polynomial?', 'Help me with Statistics', 'What strand should I choose?', 'Summarize Philippine history']
                : ($role === 'Teacher'
                    ? ['Generate 5 multiple choice questions', 'Create a lesson plan outline', 'Explain differentiated instruction', 'Tips for classroom management', 'Assessment strategies for SHS']
                    : ['Best practices for school admin?', 'How to manage student records?', 'Tips for data privacy', 'Create an announcement template', 'School year planning tips']);
            foreach ($fullPrompts as $fp): ?>
                <button class="chat-quick-btn"
                    onclick="sendFullQuick('<?= htmlspecialchars(addslashes($fp)) ?>')"><?= htmlspecialchars($fp) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- INPUT -->
        <div class="chat-panel-input">
            <textarea id="chatPanelInput"
                placeholder="Type your question here… (Enter to send, Shift+Enter for new line)"
                onkeydown="handlePanelKey(event)"></textarea>
            <button class="chat-panel-send" id="chatPanelSend" onclick="sendFullChat()">➤</button>
        </div>
    </div>
</div>

<script>
    // ════════════════════════════════════════════════════════════════
    //  EduBot — persistent history via localStorage + dropdown UI
    // ════════════════════════════════════════════════════════════════

    const STORAGE_KEY = <?= json_encode($storageKey) ?>;
    const BOT_NAME = <?= json_encode($botName) ?>;
    const USER_NAME = <?= json_encode(htmlspecialchars($_SESSION['first_name'] ?? 'there')) ?>;

    let winHistory = [];
    let winBusy = false;
    let fullBusy = false;
    let allSessions = [];
    let currentSession = null;

    // ── Persistence ──────────────────────────────────────────────────
    function loadSessions() {
        try { allSessions = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
        catch (e) { allSessions = []; }
    }
    function saveSessions() {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(allSessions)); }
        catch (e) { console.warn('EduBot localStorage error', e); }
    }
    function makeId() {
        return Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    }

    // ── Session CRUD ─────────────────────────────────────────────────
    function createSession(title) {
        const s = {
            id: makeId(), title: title || 'New Chat',
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString(),
            messages: []
        };
        allSessions.unshift(s);
        saveSessions();
        return s;
    }
    function startNewSession() {
        closeHistoryDropdown();
        currentSession = createSession('New Chat');
        updateHistCount();
        renderHistoryList();
        renderFullMessages();
        document.getElementById('sessionTitleInput').value = 'New Chat';
    }
    function switchSession(id) {
        const s = allSessions.find(x => x.id === id);
        if (!s) return;
        currentSession = s;
        closeHistoryDropdown();
        renderHistoryList();
        renderFullMessages();
        document.getElementById('sessionTitleInput').value = currentSession.title;
    }
    function deleteSession(id, e) {
        e && e.stopPropagation();
        allSessions = allSessions.filter(x => x.id !== id);
        saveSessions();
        if (currentSession && currentSession.id === id)
            currentSession = allSessions[0] || createSession('New Chat');
        updateHistCount();
        renderHistoryList();
        renderFullMessages();
        document.getElementById('sessionTitleInput').value = currentSession.title;
    }
    function clearCurrentSession() {
        if (!currentSession) return;
        currentSession.messages = [];
        currentSession.updatedAt = new Date().toISOString();
        saveSessions();
        renderFullMessages();
    }
    function clearAllHistory() {
        if (!confirm('Delete ALL chat history? This cannot be undone.')) return;
        allSessions = [];
        saveSessions();
        currentSession = createSession('New Chat');
        updateHistCount();
        renderHistoryList();
        renderFullMessages();
        document.getElementById('sessionTitleInput').value = 'New Chat';
        closeHistoryDropdown();
    }
    function renameCurrentSession(title) {
        if (!currentSession || !title.trim()) return;
        currentSession.title = title.trim();
        currentSession.updatedAt = new Date().toISOString();
        saveSessions();
        renderHistoryList();
    }
    function autoTitleSession(text) {
        if (!currentSession || currentSession.title !== 'New Chat') return;
        currentSession.title = text.length > 38 ? text.slice(0, 38) + '…' : text;
        document.getElementById('sessionTitleInput').value = currentSession.title;
        saveSessions();
        renderHistoryList();
    }

    // ── Dropdown ─────────────────────────────────────────────────────
    function toggleHistoryDropdown() {
        const dd = document.getElementById('historyDropdown');
        dd.classList.contains('open') ? closeHistoryDropdown() : openHistoryDropdown();
    }
    function openHistoryDropdown() {
        const dd = document.getElementById('historyDropdown');
        dd.classList.add('open');
        document.getElementById('histToggleBtn').classList.add('active');
        document.getElementById('histSearchInput').value = '';
        renderHistoryList();
        setTimeout(() => document.addEventListener('click', outsideHistClose), 10);
    }
    function closeHistoryDropdown() {
        document.getElementById('historyDropdown').classList.remove('open');
        document.getElementById('histToggleBtn').classList.remove('active');
        document.removeEventListener('click', outsideHistClose);
    }
    function outsideHistClose(e) {
        const dd = document.getElementById('historyDropdown');
        const btn = document.getElementById('histToggleBtn');
        if (!dd.contains(e.target) && !btn.contains(e.target)) closeHistoryDropdown();
    }
    function updateHistCount() {
        document.getElementById('histCount').textContent = allSessions.length;
    }

    // ── Render history list ───────────────────────────────────────────
    function renderHistoryList(filter) {
        const list = document.getElementById('historyList');
        list.innerHTML = '';

        const q = (filter || '').toLowerCase().trim();
        const sessions = q
            ? allSessions.filter(s =>
                s.title.toLowerCase().includes(q) ||
                s.messages.some(m => m.content.toLowerCase().includes(q)))
            : allSessions;

        if (sessions.length === 0) {
            const e = document.createElement('div');
            e.className = 'chat-hd-empty';
            e.innerHTML = q
                ? `No chats matching "<strong>${escHtml(q)}</strong>"`
                : 'No saved chats yet.<br>Start a conversation!';
            list.appendChild(e);
            return;
        }

        // Group by relative day
        const groups = {};
        sessions.forEach(s => {
            const d = relativeDay(s.updatedAt);
            (groups[d] = groups[d] || []).push(s);
        });

        Object.entries(groups).forEach(([day, items]) => {
            const lbl = document.createElement('div');
            lbl.className = 'chat-hd-group-label';
            lbl.textContent = day;
            list.appendChild(lbl);

            items.forEach(s => {
                const row = document.createElement('div');
                row.className = 'chat-hd-item' +
                    (currentSession && s.id === currentSession.id ? ' active' : '');
                row.innerHTML =
                    `<div class="chat-hd-item-body">
                    <div class="chat-hd-item-title">${escHtml(s.title)}</div>
                    <div class="chat-hd-item-meta">
                        ${fmtTime(s.updatedAt)} · ${s.messages.length} msg${s.messages.length !== 1 ? 's' : ''}
                    </div>
                 </div>
                 <span class="chat-hd-item-del"
                       onclick="deleteSession('${s.id}',event)" title="Delete">✕</span>`;
                row.onclick = () => switchSession(s.id);
                list.appendChild(row);
            });
        });
    }

    // ── Render full panel messages ────────────────────────────────────
    function renderFullMessages() {
        const el = document.getElementById('chatPanelMessages');
        el.innerHTML = '';
        appendMsgEl(el, 'bot',
            `Hello ${USER_NAME}! 👋 I'm <strong>${BOT_NAME}</strong>. I can help you with your subjects, ` +
            `explain concepts, assist with homework, or answer any school-related questions. What would you like to know?`,
            timeNow(), true);

        if (!currentSession || currentSession.messages.length === 0) {
            scrollChat('chatPanelMessages');
            return;
        }

        let lastDate = null;
        currentSession.messages.forEach(msg => {
            const d = relativeDay(msg.ts || new Date().toISOString());
            if (d !== lastDate) {
                const div = document.createElement('div');
                div.className = 'chat-date-divider';
                div.textContent = d;
                el.appendChild(div);
                lastDate = d;
            }
            appendMsgEl(el, msg.role === 'user' ? 'user' : 'bot', msg.content, fmtTime(msg.ts), true);
        });
        scrollChat('chatPanelMessages');
    }

    // ── Send — full panel ─────────────────────────────────────────────
    async function sendFullChat() {
        if (fullBusy || !currentSession) return;
        const input = document.getElementById('chatPanelInput');
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        fullBusy = true;
        document.getElementById('chatPanelSend').disabled = true;

        const ts = new Date().toISOString();
        currentSession.messages.push({ role: 'user', content: text, ts });
        saveSessions();
        autoTitleSession(text);
        updateHistCount();

        appendMsgEl(document.getElementById('chatPanelMessages'), 'user', text, timeNow());
        const typing = appendTyping('chatPanelMessages');

        const apiMsgs = currentSession.messages
            .filter(m => m.role === 'user' || m.role === 'assistant')
            .map(m => ({ role: m.role, content: m.content }));

        const reply = await callBot(apiMsgs);
        typing.remove();

        currentSession.messages.push({ role: 'assistant', content: reply, ts: new Date().toISOString() });
        currentSession.updatedAt = new Date().toISOString();
        saveSessions();

        fullBusy = false;
        document.getElementById('chatPanelSend').disabled = false;
        appendMsgEl(document.getElementById('chatPanelMessages'), 'bot', reply, timeNow());
        scrollChat('chatPanelMessages');
    }
    function sendFullQuick(text) { document.getElementById('chatPanelInput').value = text; sendFullChat(); }
    function handlePanelKey(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendFullChat(); } }

    // ── Send — floating window (ephemeral) ───────────────────────────
    async function sendChatWin() {
        if (winBusy) return;
        const input = document.getElementById('chatInputWin');
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        winBusy = true;
        document.getElementById('chatSendWin').disabled = true;
        document.getElementById('chatQuickWin').style.display = 'none';
        appendMsg('chatMessages', 'user', text);
        winHistory.push({ role: 'user', content: text });
        const typing = appendTyping('chatMessages');
        const reply = await callBot(winHistory);
        typing.remove();
        winBusy = false;
        document.getElementById('chatSendWin').disabled = false;
        appendMsg('chatMessages', 'bot', reply);
        winHistory.push({ role: 'assistant', content: reply });
        scrollChat('chatMessages');
    }
    function sendQuick(text) { document.getElementById('chatInputWin').value = text; sendChatWin(); }

    // ── Toggle / navigate ─────────────────────────────────────────────
    function toggleChatWindow() {
        const win = document.getElementById('chatWindow');
        const badge = document.getElementById('chatBadge');
        win.classList.toggle('open');
        badge.classList.remove('show');
        if (win.classList.contains('open')) scrollChat('chatMessages');
    }
    function goToFullChat() {
        document.getElementById('chatWindow').classList.remove('open');
        if (typeof showPanel === 'function') {
            const btn = [...document.querySelectorAll('.sidebar-link')]
                .find(l => l.textContent.includes('EduBot') || l.textContent.includes('AI'));
            showPanel('chatbot', btn || null);
        }
    }

    // ── API ───────────────────────────────────────────────────────────
    async function callBot(history) {
        try {
            const res = await fetch('api/chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ messages: history })
            });
            const data = await res.json();
            return data.success ? data.reply : '⚠️ ' + (data.message || 'Something went wrong.');
        } catch (e) {
            return '⚠️ Could not reach the AI service. Please check your connection.';
        }
    }

    // ── DOM helpers ───────────────────────────────────────────────────
    function appendMsg(containerId, type, text) {
        appendMsgEl(document.getElementById(containerId), type, text, timeNow());
    }
    function appendMsgEl(container, type, html, time, raw) {
        const div = document.createElement('div');
        div.className = 'chat-msg ' + type;
        div.innerHTML = (raw ? html : formatMd(html)) + `<div class="chat-msg-time">${time}</div>`;
        container.appendChild(div);
        return div;
    }
    function appendTyping(containerId) {
        const el = document.getElementById(containerId);
        const div = document.createElement('div');
        div.className = 'chat-msg typing';
        div.innerHTML = `🤖 ${BOT_NAME} is typing<span id="typingDots">...</span>`;
        el.appendChild(div);
        scrollChat(containerId);
        let d = 0;
        div._interval = setInterval(() => {
            const dots = document.getElementById('typingDots');
            if (dots) dots.textContent = ['...', '.. ', '. ', ' . '][d++ % 4];
        }, 400);
        return { remove: () => { clearInterval(div._interval); div.remove(); } };
    }
    function formatMd(text) {
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code style="background:#f0f2f5;padding:.1rem .3rem;border-radius:4px;font-size:.85em">$1</code>')
            .replace(/\n/g, '<br>');
    }
    function escHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function scrollChat(id) {
        const el = document.getElementById(id);
        if (el) setTimeout(() => el.scrollTop = el.scrollHeight, 50);
    }
    function timeNow() {
        const d = new Date(); let h = d.getHours(), m = d.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12; return h + ':' + String(m).padStart(2, '0') + ' ' + ap;
    }
    function fmtTime(iso) {
        if (!iso) return '';
        try {
            const d = new Date(iso); let h = d.getHours(), m = d.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12; return h + ':' + String(m).padStart(2, '0') + ' ' + ap;
        } catch (e) { return ''; }
    }
    function relativeDay(iso) {
        if (!iso) return 'Unknown';
        try {
            const d = new Date(iso), now = new Date();
            const diff = Math.floor((now - d) / 86400000);
            if (diff === 0) return 'Today';
            if (diff === 1) return 'Yesterday';
            if (diff < 7) return d.toLocaleDateString('en-PH', { weekday: 'long' });
            return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
        } catch (e) { return 'Earlier'; }
    }

    // ── Boot ──────────────────────────────────────────────────────────
    (function init() {
        loadSessions();
        currentSession = allSessions.length > 0 ? allSessions[0] : createSession('New Chat');
        updateHistCount();
        renderHistoryList();
        renderFullMessages();
        document.getElementById('sessionTitleInput').value = currentSession.title;
        setTimeout(() => document.getElementById('chatBadge')?.classList.add('show'), 2000);
    })();
</script>