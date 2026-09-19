<?php
// ============================================================
//  Arandia College eLMS — Profile Panel Include
//  File: includes/profile_panel.php
//  Usage: include this file inside the section-panel div
//  Requires: $user_id, $conn already set in parent
// ============================================================

$profile = $conn->query(
  "SELECT id, school_id, username, role, first_name, last_name, middle_name,
            email, contact, section_dept, profile_picture, status,
            DATE_FORMAT(created_at,'%b %d, %Y') AS member_since
     FROM users WHERE id = $user_id LIMIT 1"
)->fetch_assoc();

$pic = $profile['profile_picture'] ?? null;
$initials = strtoupper(($profile['first_name'][0] ?? '') . (($profile['last_name'][0]) ?? ''));
$roleColor = $profile['role'] === 'Teacher' ? '#00875a' : '#003087';
$roleBg = $profile['role'] === 'Teacher' ? '#e6fff4' : '#e8f0ff';
?>

<style>
  .profile-wrap {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem
  }

  .profile-card {
    background: white;
    border-radius: 18px;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem
  }

  .avatar-wrap {
    position: relative;
    width: 110px;
    height: 110px
  }

  .avatar-img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e8eaf0
  }

  .avatar-initials {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: linear-gradient(135deg, <?= $roleColor ?>, <?= $roleColor ?>aa);
    color: white;
    font-family: 'Nunito', sans-serif;
    font-size: 2.2rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center
  }

  .avatar-edit-btn {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 30px;
    height: 30px;
    background: <?= $roleColor ?>;
    color: white;
    border-radius: 50%;
    border: 2px solid white;
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .2s
  }

  .avatar-edit-btn:hover {
    transform: scale(1.1)
  }

  .profile-name {
    font-family: 'Nunito', sans-serif;
    font-size: 1.15rem;
    font-weight: 900;
    color: #1a1a2e;
    text-align: center
  }

  .profile-role {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.2rem 0.75rem;
    border-radius: 100px;
    background: <?= $roleBg ?>;
    color: <?= $roleColor ?>
  }

  .profile-info-row {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fc;
    font-size: 0.82rem
  }

  .profile-info-row:last-child {
    border-bottom: none
  }

  .profile-info-icon {
    width: 28px;
    height: 28px;
    background: #f0f2f5;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0
  }

  .profile-info-label {
    font-size: 0.68rem;
    font-weight: 700;
    color: #aaa;
    display: block
  }

  .profile-info-val {
    font-weight: 600;
    color: #1a1a2e
  }

  .edit-card {
    background: white;
    border-radius: 18px;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
    padding: 2rem
  }

  .edit-card h3 {
    font-family: 'Nunito', sans-serif;
    font-size: 1rem;
    font-weight: 900;
    color: #1a1a2e;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f0f2f5
  }

  .pf-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem
  }

  .pf-grp {
    display: flex;
    flex-direction: column;
    gap: 0.35rem
  }

  .pf-grp.full {
    grid-column: 1/-1
  }

  .pf-lbl {
    font-size: 0.75rem;
    font-weight: 700;
    color: #555
  }

  .pf-input {
    padding: 0.6rem 0.9rem;
    border: 1.5px solid #e0e4ee;
    border-radius: 9px;
    font-family: 'Open Sans', sans-serif;
    font-size: 0.85rem;
    background: #fafbff;
    outline: none;
    transition: border-color .2s;
    color: #1a1a2e
  }

  .pf-input:focus {
    border-color: <?= $roleColor ?>;
    box-shadow: 0 0 0 3px
      <?= $roleColor ?>
      18;
    background: white
  }

  .pf-input:disabled {
    background: #f5f5f5;
    color: #999;
    cursor: not-allowed
  }

  .pf-actions {
    grid-column: 1/-1;
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 0.5rem
  }

  .pf-btn {
    font-family: 'Nunito', sans-serif;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.55rem 1.2rem;
    border-radius: 9px;
    border: none;
    cursor: pointer;
    transition: all .2s
  }

  .pf-btn-save {
    background: <?= $roleColor ?>;
    color: white
  }

  .pf-btn-save:hover {
    opacity: 0.88
  }

  .pf-btn-cancel {
    background: #f0f2f5;
    color: #555
  }

  .pf-btn-cancel:hover {
    background: #e0e4ee
  }

  .pf-success {
    display: none;
    background: #e6fff4;
    border: 1px solid #b7f5d8;
    color: #00875a;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.6rem 0.9rem;
    border-radius: 8px;
    grid-column: 1/-1
  }

  .pf-error {
    display: none;
    background: #fff0ec;
    border: 1px solid #ffcfbf;
    color: #c0392b;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.6rem 0.9rem;
    border-radius: 8px;
    grid-column: 1/-1
  }

  .pic-upload-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 700;
    align-items: center;
    justify-content: center
  }

  .pic-upload-overlay.show {
    display: flex
  }

  .pic-upload-box {
    background: white;
    border-radius: 18px;
    padding: 2rem;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    text-align: center
  }

  .pic-upload-box h3 {
    font-family: 'Nunito', sans-serif;
    font-size: 1rem;
    font-weight: 900;
    margin-bottom: 1rem;
    color: #1a1a2e
  }

  .pic-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e8eaf0;
    margin: 0 auto 1rem;
    display: block
  }

  .pic-drop-zone {
    border: 2px dashed #e0e4ee;
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all .2s;
    margin-bottom: 1rem
  }

  .pic-drop-zone:hover {
    border-color: <?= $roleColor ?>;
    background: #f8f9fc
  }

  .pic-drop-text {
    font-size: 0.82rem;
    color: #aaa;
    margin-top: 0.4rem
  }

  .pic-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center
  }

  @media(max-width:900px) {
    .profile-wrap {
      grid-template-columns: 1fr
    }

    .pf-grid {
      grid-template-columns: 1fr
    }
  }

  /* ── Password toggle button (matches login.php) ── */
  .toggle-pw {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: color 0.2s, background 0.2s;
    outline: none;
  }
  .toggle-pw:hover { color: #003087; background: rgba(0,48,135,0.07); }
  .toggle-pw:focus-visible { box-shadow: 0 0 0 3px rgba(0,48,135,0.15); }
  .toggle-pw svg { width: 18px; height: 18px; display: block; }
  .toggle-pw .eye-slash { display: none; }
  .toggle-pw.is-visible .eye-open  { display: none; }
  .toggle-pw.is-visible .eye-slash { display: block; }

</style>

<div class="profile-wrap">
  <!-- LEFT: Profile card -->
  <div class="profile-card">
    <!-- Avatar -->
    <div class="avatar-wrap">
      <?php if ($pic): ?>
        <img src="<?= htmlspecialchars($pic) ?>" alt="Profile" class="avatar-img" id="avatar-display">
      <?php else: ?>
        <div class="avatar-initials" id="avatar-display"><?= $initials ?></div>
      <?php endif; ?>
      <button class="avatar-edit-btn" onclick="openPicModal()" title="Change photo">📷</button>
    </div>

    <div class="profile-name">
      <?= htmlspecialchars($profile['last_name'] . ', ' . $profile['first_name'] . ($profile['middle_name'] ? ' ' . $profile['middle_name'][0] . '.' : '')) ?>
    </div>
    <span class="profile-role"><?= $profile['role'] ?></span>

    <!-- Info rows -->
    <div style="width:100%;margin-top:0.5rem">
      <div class="profile-info-row">
        <div class="profile-info-icon">🪪</div>
        <div><span class="profile-info-label">LRN / School ID</span><span
            class="profile-info-val"><?= htmlspecialchars($profile['school_id']) ?></span></div>
      </div>
      <div class="profile-info-row">
        <div class="profile-info-icon">👤</div>
        <div><span class="profile-info-label">Username</span><span
            class="profile-info-val"><?= htmlspecialchars($profile['username']) ?></span></div>
      </div>
      <?php if ($profile['section_dept']): ?>
        <div class="profile-info-row">
          <div class="profile-info-icon">🏫</div>
          <div><span
              class="profile-info-label"><?= $profile['role'] === 'Teacher' ? 'Department' : 'Section / Grade' ?></span><span
              class="profile-info-val"><?= htmlspecialchars($profile['section_dept']) ?></span></div>
        </div>
      <?php endif; ?>
      <div class="profile-info-row">
        <div class="profile-info-icon">📧</div>
        <div><span class="profile-info-label">Email</span><span
            class="profile-info-val"><?= htmlspecialchars($profile['email'] ?: '—') ?></span></div>
      </div>
      <div class="profile-info-row">
        <div class="profile-info-icon">📱</div>
        <div><span class="profile-info-label">Contact</span><span
            class="profile-info-val"><?= htmlspecialchars($profile['contact'] ?: '—') ?></span></div>
      </div>
      <div class="profile-info-row">
        <div class="profile-info-icon">🗓️</div>
        <div><span class="profile-info-label">Member Since</span><span
            class="profile-info-val"><?= $profile['member_since'] ?></span></div>
      </div>
      <div class="profile-info-row">
        <div class="profile-info-icon">✅</div>
        <div><span class="profile-info-label">Status</span><span class="profile-info-val"
            style="color:<?= $profile['status'] === 'Active' ? '#00875a' : '#c0392b' ?>"><?= $profile['status'] ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: Edit form -->
  <div class="edit-card">
    <h3>✏️ Edit Profile</h3>
    <div class="pf-grid">
      <div class="pf-success" id="pf-success"></div>
      <div class="pf-error" id="pf-error"></div>
      <div class="pf-grp">
        <label class="pf-lbl">First Name *</label>
        <input class="pf-input" type="text" id="pf-fname" value="<?= htmlspecialchars($profile['first_name']) ?>">
      </div>
      <div class="pf-grp">
        <label class="pf-lbl">Last Name *</label>
        <input class="pf-input" type="text" id="pf-lname" value="<?= htmlspecialchars($profile['last_name']) ?>">
      </div>
      <div class="pf-grp">
        <label class="pf-lbl">Middle Name</label>
        <input class="pf-input" type="text" id="pf-mname"
          value="<?= htmlspecialchars($profile['middle_name'] ?? '') ?>">
      </div>
      <div class="pf-grp">
        <label class="pf-lbl">Username <span style="color:#aaa;font-weight:400">(cannot change)</span></label>
        <input class="pf-input" type="text" value="<?= htmlspecialchars($profile['username']) ?>" disabled>
      </div>
      <div class="pf-grp">
        <label class="pf-lbl">LRN / School ID <span style="color:#aaa;font-weight:400">(cannot change)</span></label>
        <input class="pf-input" type="text" value="<?= htmlspecialchars($profile['school_id']) ?>" disabled>
      </div>
      <div class="pf-grp">
        <label class="pf-lbl">Section / Grade <span style="color:#aaa;font-weight:400">(set by admin)</span></label>
        <input class="pf-input" type="text" value="<?= htmlspecialchars($profile['section_dept'] ?? '—') ?>" disabled>
      </div>
      <div class="pf-grp">
        <label class="pf-lbl">Email Address</label>
        <input class="pf-input" type="email" id="pf-email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>"
          placeholder="your@email.com">
      </div>
      <div class="pf-grp">
        <label class="pf-lbl">Contact Number</label>
        <input class="pf-input" type="tel" id="pf-contact" value="<?= htmlspecialchars($profile['contact'] ?? '') ?>"
          placeholder="09XXXXXXXXX" maxlength="11" inputmode="numeric"
          oninput="this.value=this.value.replace(/\D/g,'').slice(0,11)">
      </div>
      <!-- Divider -->
      <div class="pf-grp full" style="border-top:1.5px solid #f0f2f5;margin-top:.5rem;padding-top:1.25rem">
        <div style="font-family:'Nunito',sans-serif;font-size:.95rem;font-weight:900;color:#1a1a2e;margin-bottom:.1rem">&#128274; Change Password</div>
        <div style="font-size:.75rem;color:#aaa">Leave blank if you do not want to change your password.</div>
      </div>
      <div class="pf-success" id="pw-success"></div>
      <div class="pf-error"   id="pw-error"></div>

      <div class="pf-grp full">
        <label class="pf-lbl">Current Password</label>
        <div style="position:relative">
          <input class="pf-input" type="password" id="pw-current" placeholder="Enter current password" style="width:100%;padding-right:2.5rem">
          <button type="button" class="toggle-pw" onclick="togglePw('pw-current',this)" aria-label="Show password">
              <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <svg class="eye-slash" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
        </div>
      </div>

      <div class="pf-grp">
        <label class="pf-lbl">New Password</label>
        <div style="position:relative">
          <input class="pf-input" type="password" id="pw-new" placeholder="At least 8 characters" style="width:100%;padding-right:2.5rem">
          <button type="button" class="toggle-pw" onclick="togglePw('pw-new',this)" aria-label="Show password">
              <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <svg class="eye-slash" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
        </div>

      </div>

      <div class="pf-grp">
        <label class="pf-lbl">Confirm New Password</label>
        <div style="position:relative">
          <input class="pf-input" type="password" id="pw-confirm" placeholder="Re-enter new password" style="width:100%;padding-right:2.5rem">
          <button type="button" class="toggle-pw" onclick="togglePw('pw-confirm',this)" aria-label="Show password">
              <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <svg class="eye-slash" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
        </div>
      </div>

      <div class="pf-actions">
        <button class="pf-btn pf-btn-cancel" onclick="resetPfForm()">Reset</button>
        <button class="pf-btn pf-btn-save" onclick="savePfProfile()">&#10003; Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Profile picture upload modal -->
<div class="pic-upload-overlay" id="picModal">
  <div class="pic-upload-box">
    <h3>📷 Change Profile Picture</h3>
    <img src="<?= $pic ? htmlspecialchars($pic) : '' ?>" id="pic-preview-img" style="<?= $pic ? '' : 'display:none' ?>"
      class="pic-preview" alt="Preview">
    <div class="pic-drop-zone" onclick="document.getElementById('pic-file-input').click()">
      <div style="font-size:2rem">📁</div>
      <div class="pic-drop-text">Click to browse or drag & drop<br><span style="font-size:0.72rem">JPG, PNG, GIF, WEBP —
          max 2MB</span></div>
    </div>
    <input type="file" id="pic-file-input" accept="image/*" style="display:none" onchange="previewPic(this)">
    <div id="pic-err" style="display:none;color:#c0392b;font-size:0.78rem;font-weight:600;margin-bottom:0.75rem"></div>
    <div class="pic-actions">
      <button class="pf-btn pf-btn-cancel" onclick="closePicModal()">Cancel</button>
      <button class="pf-btn pf-btn-save" id="pic-upload-btn" onclick="uploadPic()" style="display:none">✓
        Upload</button>
    </div>
  </div>
</div>

<script>
  // Profile form original values for reset
  const _pfOrig = {
    fname: '<?= addslashes($profile['first_name']) ?>',
    lname: '<?= addslashes($profile['last_name']) ?>',
    mname: '<?= addslashes($profile['middle_name'] ?? '') ?>',
    email: '<?= addslashes($profile['email'] ?? '') ?>',
    contact: '<?= addslashes($profile['contact'] ?? '') ?>',
  };

  function resetPfForm() {
    document.getElementById('pf-fname').value = _pfOrig.fname;
    document.getElementById('pf-lname').value = _pfOrig.lname;
    document.getElementById('pf-mname').value = _pfOrig.mname;
    document.getElementById('pf-email').value = _pfOrig.email;
    document.getElementById('pf-contact').value = _pfOrig.contact;
    ['pw-current','pw-new','pw-confirm'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('pf-success').style.display = 'none';
    document.getElementById('pf-error').style.display   = 'none';
    document.getElementById('pw-success').style.display = 'none';
    document.getElementById('pw-error').style.display   = 'none';
  }

  async function savePfProfile() {
    const succEl = document.getElementById('pf-success');
    const errEl  = document.getElementById('pf-error');
    const pwSucc = document.getElementById('pw-success');
    const pwErr  = document.getElementById('pw-error');
    succEl.style.display = 'none'; errEl.style.display  = 'none';
    pwSucc.style.display = 'none'; pwErr.style.display  = 'none';

    const payload = {
      first_name:  document.getElementById('pf-fname').value.trim(),
      last_name:   document.getElementById('pf-lname').value.trim(),
      middle_name: document.getElementById('pf-mname').value.trim(),
      email:       document.getElementById('pf-email').value.trim(),
      contact:     document.getElementById('pf-contact').value.trim(),
    };

    if (!payload.first_name || !payload.last_name) {
      errEl.textContent = '\u26A0\uFE0F First and last name are required.';
      errEl.style.display = 'block'; return;
    }

    // ── Save profile info ──────────────────────────────────────
    try {
      const res  = await fetch('api/profile.php?action=update', {
        method: 'POST', body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        succEl.textContent = '\u2705 ' + data.message;
        succEl.style.display = 'block';
      } else {
        errEl.textContent = '\u26A0\uFE0F ' + data.message;
        errEl.style.display = 'block'; return;
      }
    } catch (e) {
      errEl.textContent = '\u26A0\uFE0F Network error. Try again.';
      errEl.style.display = 'block'; return;
    }

    // ── Change password (only if fields are filled) ────────────
    const current = document.getElementById('pw-current').value;
    const newPw   = document.getElementById('pw-new').value;
    const confirm = document.getElementById('pw-confirm').value;

    if (current || newPw || confirm) {
      if (!current || !newPw || !confirm) {
        pwErr.textContent = '\u26A0\uFE0F Fill in all three password fields to change your password.';
        pwErr.style.display = 'block'; return;
      }
      if (newPw.length < 8) {
        pwErr.textContent = '\u26A0\uFE0F New password must be at least 8 characters.';
        pwErr.style.display = 'block'; return;
      }
      if (newPw !== confirm) {
        pwErr.textContent = '\u26A0\uFE0F New passwords do not match.';
        pwErr.style.display = 'block'; return;
      }
      try {
        const res2  = await fetch('api/profile.php?action=change_password', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ current_password: current, new_password: newPw })
        });
        const data2 = await res2.json();
        if (data2.success) {
          pwSucc.textContent = '\u2705 Password changed successfully!';
          pwSucc.style.display = 'block';
          ['pw-current','pw-new','pw-confirm'].forEach(id => document.getElementById(id).value = '');
        } else {
          pwErr.textContent = '\u26A0\uFE0F ' + data2.message;
          pwErr.style.display = 'block'; return;
        }
      } catch (e) {
        pwErr.textContent = '\u26A0\uFE0F Network error. Try again.';
        pwErr.style.display = 'block'; return;
      }
    }

    setTimeout(() => location.reload(), 1500);
  }

  // Picture upload
  function openPicModal() { document.getElementById('picModal').classList.add('show'); }
  function closePicModal() { document.getElementById('picModal').classList.remove('show'); }

  function previewPic(input) {
    const errEl = document.getElementById('pic-err');
    errEl.style.display = 'none';
    const file = input.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
      errEl.textContent = '⚠️ File too large. Max 2MB.';
      errEl.style.display = 'block'; return;
    }
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('pic-preview-img');
      img.src = e.target.result;
      img.style.display = 'block';
      document.getElementById('pic-upload-btn').style.display = '';
    };
    reader.readAsDataURL(file);
  }

  async function uploadPic() {
    const errEl = document.getElementById('pic-err');
    const btn = document.getElementById('pic-upload-btn');
    const file = document.getElementById('pic-file-input').files[0];
    if (!file) return;

    btn.textContent = 'Uploading...'; btn.disabled = true;
    errEl.style.display = 'none';

    const fd = new FormData();
    fd.append('picture', file);

    try {
      const res = await fetch('api/profile.php?action=upload_pic', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        closePicModal();
        // Update avatar on page without full reload
        const avatarEl = document.getElementById('avatar-display');
        if (avatarEl.tagName === 'IMG') {
          avatarEl.src = data.path + '?t=' + Date.now();
        } else {
          const img = document.createElement('img');
          img.src = data.path; img.className = 'avatar-img'; img.id = 'avatar-display'; img.alt = 'Profile';
          avatarEl.replaceWith(img);
        }
      } else {
        errEl.textContent = '⚠️ ' + data.message; errEl.style.display = 'block';
      }
    } catch (e) {
      errEl.textContent = '⚠️ Upload failed.'; errEl.style.display = 'block';
    }
    btn.textContent = '✓ Upload'; btn.disabled = false;
  }

  // ── Change Password ──────────────────────────────────────────
  function togglePw(id, btn) {
    const inp     = document.getElementById(id);
    const showing = inp.type === 'text';
    inp.type      = showing ? 'password' : 'text';
    btn.classList.toggle('is-visible', !showing);
    btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
  }



  function clearPwForm() {
    ['pw-current', 'pw-new', 'pw-confirm'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('pw-success').style.display = 'none';
    document.getElementById('pw-error').style.display   = 'none';
  }

  async function changePassword() {
    const succEl = document.getElementById('pw-success');
    const errEl  = document.getElementById('pw-error');
    succEl.style.display = 'none'; errEl.style.display = 'none';

    const current = document.getElementById('pw-current').value;
    const newPw   = document.getElementById('pw-new').value;
    const confirm = document.getElementById('pw-confirm').value;

    if (!current || !newPw || !confirm) {
      errEl.textContent = '\u26A0\uFE0F All password fields are required.';
      errEl.style.display = 'block'; return;
    }
    if (newPw.length < 8) {
      errEl.textContent = '\u26A0\uFE0F New password must be at least 8 characters.';
      errEl.style.display = 'block'; return;
    }
    if (newPw !== confirm) {
      errEl.textContent = '\u26A0\uFE0F New passwords do not match.';
      errEl.style.display = 'block'; return;
    }
    if (newPw === current) {
      errEl.textContent = '\u26A0\uFE0F New password must be different from your current password.';
      errEl.style.display = 'block'; return;
    }

    try {
      const res  = await fetch('api/profile.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ current_password: current, new_password: newPw })
      });
      const data = await res.json();
      if (data.success) {
        succEl.textContent = '\u2705 Password changed successfully!';
        succEl.style.display = 'block';
        clearPwForm();
      } else {
        errEl.textContent = '\u26A0\uFE0F ' + data.message;
        errEl.style.display = 'block';
      }
    } catch (e) {
      errEl.textContent = '\u26A0\uFE0F Network error. Try again.';
      errEl.style.display = 'block';
    }
  }

  // Drag & drop
  const dropZone = document.querySelector('.pic-drop-zone');
  if (dropZone) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '<?= $roleColor ?>'; });
    dropZone.addEventListener('dragleave', () => dropZone.style.borderColor = '');
    dropZone.addEventListener('drop', e => {
      e.preventDefault();
      dropZone.style.borderColor = '';
      const file = e.dataTransfer.files[0];
      if (file) {
        const dt = new DataTransfer(); dt.items.add(file);
        document.getElementById('pic-file-input').files = dt.files;
        previewPic(document.getElementById('pic-file-input'));
      }
    });
  }
</script>